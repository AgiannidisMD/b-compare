<?php

namespace App\Services\Scoring;

use App\Models\Supplement;

/**
 * Clinical Scoring Engine
 *
 * Evaluates supplements on pure clinical quality:
 * 1. Dose Adequacy — is the dose in the therapeutic range?
 * 2. Form Quality — is the ingredient form well-absorbed?
 * 3. Red Flag Detection — are there signs of a low-quality product?
 *
 * No price. No reviews. Just clinical science.
 */
class ClinicalScorer
{
    private array $clinicalConfig;

    public function __construct()
    {
        $this->clinicalConfig = config('clinical_doses', []);
    }

    /**
     * Score a supplement using its category's clinical reference data.
     *
     * Returns:
     * - clinical_dose_score (0-10): how well the dose meets therapeutic needs
     * - efficacy_score (0-10): overall clinical efficacy (dose + form + formulation)
     * - bioavailability_score (0-10): ingredient form + delivery method quality
     * - quality_score (0-10): certifications + data completeness + safety
     * - formulation_score (0-10): ingredient synergy + cofactors + completeness
     * - overall_recommendation_score (0-10): weighted composite
     * - red_flags_detected (array): list of clinical red flags found
     */
    public function score(Supplement $supplement, ?string $categorySlug = null): array
    {
        $config = $this->getCategoryConfig($categorySlug, $supplement);

        $doseScore = $this->scoreDoseAdequacy($supplement, $config);
        $formScore = $this->scoreFormQuality($supplement, $config);
        $redFlags = $this->detectRedFlags($supplement, $config);
        $cofactorScore = $this->scoreCofactors($supplement, $config);
        $certScore = $this->scoreCertifications($supplement);
        $formulationScore = $this->scoreFormulation($supplement, $config, $cofactorScore);

        // Bioavailability = form quality (70%) + delivery method (30%)
        $deliveryScore = $this->scoreDeliveryMethod($supplement);
        $bioavailability = ($formScore * 0.70) + ($deliveryScore * 0.30);

        // Efficacy = dose adequacy (50%) + form quality (30%) + cofactors (20%)
        $efficacy = ($doseScore * 0.50) + ($formScore * 0.30) + ($cofactorScore * 0.20);

        // Quality = certifications (50%) + data completeness (30%) + safety (20%)
        $safetyScore = max(0, 10.0 - (count($redFlags) * 2.0));
        $completenessScore = $this->scoreDataCompleteness($supplement);
        $quality = ($certScore * 0.50) + ($completenessScore * 0.30) + ($safetyScore * 0.20);

        // Overall = Efficacy (40%) + Bioavailability (25%) + Quality (20%) + Formulation (15%)
        $overall = (
            ($efficacy * 0.40) +
            ($bioavailability * 0.25) +
            ($quality * 0.20) +
            ($formulationScore * 0.15)
        );

        // Red flag penalty on overall score
        $flagPenalty = min(3.0, count($redFlags) * 0.75);
        $overall = max(0, $overall - $flagPenalty);

        return [
            'clinical_dose_score' => round(min(10, max(0, $doseScore)), 2),
            'efficacy_score' => round(min(10, max(0, $efficacy)), 2),
            'bioavailability_score' => round(min(10, max(0, $bioavailability)), 2),
            'quality_score' => round(min(10, max(0, $quality)), 2),
            'formulation_score' => round(min(10, max(0, $formulationScore)), 2),
            'overall_recommendation_score' => round(min(10, max(0, $overall)), 2),
            'red_flags_detected' => $redFlags,
        ];
    }

    /**
     * Score how well the product's dose meets the therapeutic range.
     * 0 = no dose info or severely underdosed
     * 5 = minimum therapeutic dose
     * 10 = optimal dose
     */
    private function scoreDoseAdequacy(Supplement $supplement, array $config): float
    {
        if (empty($config['therapeutic_range'])) {
            return 5.0; // no reference data, neutral
        }

        $range = $config['therapeutic_range'];
        $detectedDose = $this->extractPrimaryDose($supplement, $config);

        if ($detectedDose === null) {
            // No dose info — check serving_size_value as fallback
            if ($supplement->serving_size_value > 0) {
                $detectedDose = (float) $supplement->serving_size_value;
            } else {
                return 3.0; // can't determine dose, below neutral
            }
        }

        $min = $range['min'];
        $optimal = $range['optimal'];
        $max = $range['max'];

        if ($detectedDose < $min * 0.25) {
            return 1.0; // severely underdosed
        } elseif ($detectedDose < $min * 0.50) {
            return 2.5;
        } elseif ($detectedDose < $min) {
            // Approaching minimum
            return 3.0 + (($detectedDose / $min) * 2.0);
        } elseif ($detectedDose <= $optimal) {
            // Between min and optimal — scale from 5 to 10
            $ratio = ($detectedDose - $min) / max(1, $optimal - $min);
            return 5.0 + ($ratio * 5.0);
        } elseif ($detectedDose <= $max) {
            // Above optimal but within safe range — still great
            return 9.5;
        } else {
            // Above max — overdosed, slight penalty
            return 7.0;
        }
    }

    /**
     * Score the quality of the ingredient form used.
     */
    private function scoreFormQuality(Supplement $supplement, array $config): float
    {
        $searchText = $this->buildSearchText($supplement);
        $bestPreferred = 0.0;
        $worstPenalty = 0.0;
        $foundForm = false;

        // Check preferred forms
        if (!empty($config['preferred_forms'])) {
            foreach ($config['preferred_forms'] as $form => $rating) {
                if (str_contains($searchText, strtolower($form))) {
                    $bestPreferred = max($bestPreferred, $rating);
                    $foundForm = true;
                }
            }
        }

        // Check penalized forms
        if (!empty($config['penalized_forms'])) {
            foreach ($config['penalized_forms'] as $form => $penalty) {
                if (str_contains($searchText, strtolower($form))) {
                    $worstPenalty = min($worstPenalty, $penalty);
                    $foundForm = true;
                }
            }
        }

        if (!$foundForm) {
            return 5.0; // can't determine form, neutral
        }

        // Best preferred form score (0-10) + any penalty
        $score = ($bestPreferred * 10.0) + $worstPenalty;

        return min(10.0, max(0.0, $score));
    }

    /**
     * Detect clinical red flags in the product.
     */
    private function detectRedFlags(Supplement $supplement, array $config): array
    {
        $flags = [];
        $searchText = $this->buildSearchText($supplement);

        // Category-specific red flags
        if (!empty($config['red_flags'])) {
            foreach ($config['red_flags'] as $pattern) {
                $regex = '/' . str_replace('/', '\/', strtolower($pattern)) . '/';
                if (@preg_match($regex, $searchText)) {
                    $flags[] = $pattern;
                }
            }
        }

        // Universal red flags
        if (str_contains($searchText, 'proprietary blend')) {
            if (!in_array('proprietary blend', $flags)) {
                $flags[] = 'Proprietary blend — actual doses hidden';
            }
        }

        // Check for severely underdosed
        if (!empty($config['therapeutic_range'])) {
            $dose = $this->extractPrimaryDose($supplement, $config);
            $min = $config['therapeutic_range']['min'];
            if ($dose !== null && $dose < $min * 0.25) {
                $flags[] = "Severely underdosed — {$dose} vs minimum {$min} {$config['therapeutic_range']['unit']}";
            }
        }

        // Check for penalized form marketed as premium
        if (!empty($config['penalized_forms'])) {
            foreach ($config['penalized_forms'] as $form => $penalty) {
                $cleanForm = preg_replace('/\.\*.*$/', '', strtolower($form));
                if ($penalty <= -1.5 && str_contains($searchText, $cleanForm)) {
                    // Check if marketed with positive language
                    $marketingTerms = ['premium', 'advanced', 'high absorption', 'superior', 'best'];
                    foreach ($marketingTerms as $term) {
                        if (str_contains($searchText, $term)) {
                            $flags[] = "Poor form ({$cleanForm}) marketed as {$term}";
                            break;
                        }
                    }
                }
            }
        }

        // No active ingredients data at all
        if (empty($supplement->active_ingredients) && empty($supplement->nutritional_facts)) {
            $flags[] = 'No ingredient data available — cannot verify formula';
        }

        return array_unique($flags);
    }

    /**
     * Score presence of beneficial cofactors.
     */
    private function scoreCofactors(Supplement $supplement, array $config): float
    {
        if (empty($config['cofactors'])) {
            return 5.0;
        }

        $searchText = $this->buildSearchText($supplement);
        $found = 0;
        $total = count($config['cofactors']);

        foreach ($config['cofactors'] as $cofactor) {
            if (str_contains($searchText, strtolower($cofactor))) {
                $found++;
            }
        }

        if ($found === 0) {
            return 4.0; // no cofactors, slightly below neutral
        }

        // Scale: 1 cofactor = 6, 2 = 7.5, 3+ = 9+
        return min(10.0, 4.0 + ($found / $total) * 6.0);
    }

    /**
     * Score certifications and third-party testing.
     */
    private function scoreCertifications(Supplement $supplement): float
    {
        $score = 3.0; // base

        $certFlags = $supplement->certification_flags;
        if (empty($certFlags) || !is_array($certFlags)) {
            // Check description for certification mentions
            $searchText = strtolower($supplement->product_description ?? '');
            $certKeywords = [
                'usp verified' => 2.5, 'usp' => 2.0,
                'nsf certified' => 2.5, 'nsf' => 2.0,
                'consumerlab' => 2.0,
                'third-party tested' => 1.5, 'third party tested' => 1.5,
                'cgmp' => 1.0, 'gmp certified' => 1.0, 'gmp' => 0.75,
                'pharmaceutical grade' => 1.5,
                'independently tested' => 1.5,
            ];

            foreach ($certKeywords as $keyword => $points) {
                if (str_contains($searchText, $keyword)) {
                    $score += $points;
                }
            }

            return min(10.0, $score);
        }

        $certWeights = [
            'usp verified' => 2.5, 'usp' => 2.0,
            'nsf certified' => 2.5, 'nsf' => 2.0,
            'consumerlab' => 2.0, 'consumer lab' => 2.0,
            'labdoor' => 1.5,
            'third-party tested' => 1.5, 'third party tested' => 1.5,
            'independently tested' => 1.5,
            'cgmp' => 1.0, 'gmp certified' => 1.0, 'gmp' => 0.75,
            'pharmaceutical grade' => 1.5,
            'itested' => 1.0,
            'heavy metal tested' => 1.0,
            'non-gmo' => 0.25, 'organic' => 0.25,
        ];

        $found = [];
        foreach ($certFlags as $cert) {
            $certLower = strtolower($cert);
            foreach ($certWeights as $key => $weight) {
                if (str_contains($certLower, $key) && !isset($found[$key])) {
                    $score += $weight;
                    $found[$key] = true;
                }
            }
        }

        return min(10.0, $score);
    }

    /**
     * Score formulation quality — ingredient synergy, completeness, delivery.
     */
    private function scoreFormulation(Supplement $supplement, array $config, float $cofactorScore): float
    {
        $score = 4.0; // base

        // Active ingredients completeness (0-2.5 points)
        if (!empty($supplement->active_ingredients) && is_array($supplement->active_ingredients)) {
            $count = count($supplement->active_ingredients);
            $score += min(2.5, $count * 0.5);
        }

        // Cofactor bonus incorporated (0-1.5 points)
        $score += max(0, ($cofactorScore - 5.0) * 0.3);

        // Serving precision (0-1 point)
        if ($supplement->serving_size_value > 0) {
            $score += 0.5;
        }
        if ($supplement->servings_per_container > 0) {
            $score += 0.5;
        }

        // Days of supply (0-0.5 points)
        if ($supplement->days_of_supply >= 60) {
            $score += 0.5;
        } elseif ($supplement->days_of_supply >= 30) {
            $score += 0.25;
        }

        return min(10.0, max(0.0, $score));
    }

    /**
     * Score delivery method absorption efficiency.
     */
    private function scoreDeliveryMethod(Supplement $supplement): float
    {
        $scores = [
            'liposomal' => 9.8,
            'sublingual' => 9.5,
            'liquid' => 9.0,
            'softgel' => 8.5,
            'powder' => 8.0,
            'capsule' => 7.5,
            'tablet' => 6.0,
            'gummy' => 5.0,
            'lozenge' => 7.0,
        ];

        // Check title for special delivery methods first
        $titleLower = strtolower($supplement->title ?? '');
        if (str_contains($titleLower, 'liposomal')) return 9.8;
        if (str_contains($titleLower, 'sublingual')) return 9.5;

        $dosageForm = strtolower($supplement->dosage_form ?? 'unknown');
        foreach ($scores as $form => $score) {
            if (str_contains($dosageForm, $form) || str_contains($titleLower, $form)) {
                return $score;
            }
        }

        return 6.5; // unknown form, below neutral
    }

    /**
     * Score data completeness — more complete data = more trustworthy product.
     */
    private function scoreDataCompleteness(Supplement $supplement): float
    {
        $score = 0.0;
        $checks = [
            !empty($supplement->product_description) => 1.5,
            !empty($supplement->active_ingredients) => 2.5,
            !empty($supplement->suggested_use) => 1.0,
            !empty($supplement->other_ingredients) => 1.0,
            !empty($supplement->nutritional_facts) => 1.5,
            $supplement->serving_size_value > 0 => 1.0,
            $supplement->servings_per_container > 0 => 0.75,
            !empty($supplement->certification_flags) => 0.75,
        ];

        foreach ($checks as $condition => $points) {
            if ($condition) {
                $score += $points;
            }
        }

        return min(10.0, $score);
    }

    /**
     * Extract the primary active ingredient dose from the supplement.
     */
    private function extractPrimaryDose(Supplement $supplement, array $config): ?float
    {
        if (empty($config['key_ingredients'])) {
            return null;
        }

        // Try active_ingredients JSON first
        if (!empty($supplement->active_ingredients) && is_array($supplement->active_ingredients)) {
            foreach ($supplement->active_ingredients as $ingredient) {
                $name = strtolower($ingredient['name'] ?? '');
                $amount = (float) ($ingredient['amount'] ?? 0);

                foreach ($config['key_ingredients'] as $key) {
                    if (str_contains($name, strtolower($key)) && $amount > 0) {
                        return $amount;
                    }
                }
            }
        }

        // Fallback to serving_size_value if it exists
        if ($supplement->serving_size_value > 0) {
            return (float) $supplement->serving_size_value;
        }

        return null;
    }

    /**
     * Build search text from all text fields for pattern matching.
     */
    private function buildSearchText(Supplement $supplement): string
    {
        $parts = [
            $supplement->title ?? '',
            $supplement->product_description ?? '',
            $supplement->other_ingredients ?? '',
            $supplement->suggested_use ?? '',
            $supplement->nutritional_facts ?? '',
        ];

        // Include active ingredient names
        if (!empty($supplement->active_ingredients) && is_array($supplement->active_ingredients)) {
            foreach ($supplement->active_ingredients as $ingredient) {
                $parts[] = $ingredient['name'] ?? '';
            }
        }

        // Include certification flags
        if (!empty($supplement->certification_flags) && is_array($supplement->certification_flags)) {
            $parts = array_merge($parts, $supplement->certification_flags);
        }

        return strtolower(implode(' ', $parts));
    }

    /**
     * Get the clinical config for a category.
     */
    private function getCategoryConfig(?string $categorySlug, Supplement $supplement): array
    {
        if ($categorySlug && isset($this->clinicalConfig[$categorySlug])) {
            return $this->clinicalConfig[$categorySlug];
        }

        // Try to resolve from supplement's category
        if ($supplement->category) {
            $slug = $supplement->category->slug ?? '';
            if (isset($this->clinicalConfig[$slug])) {
                return $this->clinicalConfig[$slug];
            }
        }

        // Try comparison_group as fallback
        $group = strtolower($supplement->comparison_group ?? '');
        foreach ($this->clinicalConfig as $slug => $config) {
            if (str_contains($slug, $group) || str_contains($group, str_replace('-', ' ', $slug))) {
                return $config;
            }
        }

        return [];
    }
}
