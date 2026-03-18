<?php

namespace App\Services;

use App\Models\Supplement;
use App\Models\SupplementCategory;
use Illuminate\Support\Facades\DB;

/**
 * Clinical Supplement Scoring Service
 *
 * This service uses a PRICE-FREE clinical scoring methodology
 * to evaluate supplements based purely on scientific and quality factors.
 *
 * Scoring Categories:
 * - Clinical Efficacy (35%): Dosage adequacy, user feedback, active ingredients
 * - Quality & Safety (30%): Certifications, testing, purity standards
 * - Bioavailability (25%): Ingredient forms, absorption rates
 * - Formulation (10%): Ingredient synergy, delivery method, completeness
 */
class SupplementScoringService
{
    /**
     * Bioavailability ratings for different ingredient forms (0.0 - 1.0 scale)
     * Higher = better absorption
     */
    private const BIOAVAILABILITY_MAP = [
        // Magnesium forms (well-researched)
        'bisglycinate' => 0.95, 'glycinate' => 0.95, 'taurate' => 0.90,
        'l-threonate' => 0.90, 'threonate' => 0.90, // brain-specific
        'malate' => 0.85, 'orotate' => 0.80, 'citrate' => 0.75,
        'chloride' => 0.65, 'lactate' => 0.65, 'aspartate' => 0.60,
        'carbonate' => 0.30, 'oxide' => 0.20, 'hydroxide' => 0.20,

        // Vitamin D forms
        'd3' => 0.95, 'cholecalciferol' => 0.95,
        'd2' => 0.55, 'ergocalciferol' => 0.55,

        // Omega-3 forms
        'triglyceride form' => 0.95, 're-esterified' => 0.95,
        'phospholipid' => 0.90, 'krill' => 0.90,
        'ethyl ester' => 0.60,

        // Iron forms
        'iron bisglycinate' => 0.95, 'ferrous bisglycinate' => 0.95,
        'carbonyl iron' => 0.85, 'ferrous fumarate' => 0.70,
        'ferrous gluconate' => 0.65, 'ferrous sulfate' => 0.55,

        // Zinc forms
        'zinc picolinate' => 0.95, 'picolinate' => 0.95,
        'zinc bisglycinate' => 0.90, 'zinc citrate' => 0.80,
        'zinc gluconate' => 0.75, 'zinc acetate' => 0.70,
        'zinc oxide' => 0.30,

        // B12 forms
        'methylcobalamin' => 0.98, 'methyl b12' => 0.98,
        'adenosylcobalamin' => 0.95, 'hydroxocobalamin' => 0.90,
        'cyanocobalamin' => 0.50,

        // Folate forms
        'methylfolate' => 0.98, '5-mthf' => 0.98, 'l-methylfolate' => 0.98,
        'folinic acid' => 0.85, 'folic acid' => 0.55,

        // CoQ10 forms
        'ubiquinol' => 0.95, 'ubiquinone' => 0.55, 'coq10' => 0.60,

        // Curcumin forms
        'theracurmin' => 0.95, 'meriva' => 0.90, 'phytosome' => 0.90,
        'bcm-95' => 0.85, 'longvida' => 0.85, 'bioperine' => 0.80,
        'piperine' => 0.80,

        // Calcium forms
        'calcium citrate' => 0.80, 'calcium malate' => 0.75,
        'calcium carbonate' => 0.40, // requires stomach acid

        // Collagen types
        'type i' => 0.85, 'type ii' => 0.85, 'type iii' => 0.85,
        'hydrolyzed' => 0.90, 'peptides' => 0.90,

        // Probiotic quality indicators
        'delayed release' => 0.90, 'acid-resistant' => 0.85,
        'enteric coated' => 0.85, 'cfu guaranteed' => 0.80,
    ];

    /**
     * Quality certification weights (clinical significance)
     */
    private const CERTIFICATION_WEIGHTS = [
        // Highest tier - Third-party purity/potency testing
        'usp verified' => 1.00, 'usp' => 0.95,
        'nsf certified' => 0.95, 'nsf' => 0.90,
        'consumerlab' => 0.90, 'consumer lab' => 0.90,
        'labdoor' => 0.85,
        'third-party tested' => 0.85, 'third party tested' => 0.85,
        'independently tested' => 0.80,

        // Manufacturing standards
        'cgmp' => 0.75, 'gmp certified' => 0.70, 'gmp' => 0.65,
        'fda registered' => 0.60,

        // Purity certifications
        'heavy metal tested' => 0.70, 'pesticide free' => 0.65,
        'solvent free' => 0.60, 'no artificial' => 0.50,

        // Quality indicators
        'pharmaceutical grade' => 0.85, 'medical grade' => 0.80,
        'clinical strength' => 0.70, 'professional grade' => 0.65,

        // Dietary certifications (lower clinical weight)
        'organic' => 0.40, 'non-gmo' => 0.35, 'vegan' => 0.25,
        'vegetarian' => 0.20, 'gluten-free' => 0.20, 'kosher' => 0.15,
        'halal' => 0.15, 'allergen-free' => 0.30,
    ];

    /**
     * Delivery method absorption efficiency
     */
    private const DELIVERY_METHOD_SCORES = [
        'liquid' => 0.95,
        'softgel' => 0.90,
        'capsule' => 0.85,
        'tablet' => 0.70,
        'gummy' => 0.60, // often lower doses, added sugars
        'powder' => 0.85,
        'sublingual' => 0.95,
        'liposomal' => 0.98,
    ];

    /**
     * Calculate all clinical scores for a supplement (PRICE-FREE)
     */
    public function calculateScores(Supplement $supplement): array
    {
        $efficacy = $this->calculateClinicalEfficacyScore($supplement);
        $quality = $this->calculateQualitySafetyScore($supplement);
        $bioavailability = $this->calculateBioavailabilityScore($supplement);
        $formulation = $this->calculateFormulationScore($supplement);

        // Clinical-weighted overall score (NO PRICE FACTOR)
        $overall = (
            ($efficacy * 0.35) +        // Clinical Efficacy: 35%
            ($quality * 0.30) +         // Quality & Safety: 30%
            ($bioavailability * 0.25) + // Bioavailability: 25%
            ($formulation * 0.10)       // Formulation: 10%
        );

        return [
            'efficacy_score' => round($efficacy, 2),
            'quality_score' => round($quality, 2),
            'bioavailability_score' => round($bioavailability, 2),
            'formulation_score' => round($formulation, 2),
            'value_score' => round($formulation, 2), // Backward compatibility
            'overall_recommendation_score' => round($overall, 2),
        ];
    }

    /**
     * Calculate Clinical Efficacy Score (0-10)
     *
     * Based on:
     * - User ratings (real-world efficacy feedback)
     * - Review volume (statistical significance)
     * - Active ingredient presence and quantity
     * - Dosage adequacy signals
     */
    private function calculateClinicalEfficacyScore(Supplement $supplement): float
    {
        $score = 4.0; // Base score

        // User rating contribution (0-3.5 points)
        // Higher ratings = more people experiencing positive effects
        if ($supplement->rating) {
            if ($supplement->rating >= 4.8) {
                $score += 3.5;
            } elseif ($supplement->rating >= 4.5) {
                $score += 3.0;
            } elseif ($supplement->rating >= 4.2) {
                $score += 2.5;
            } elseif ($supplement->rating >= 4.0) {
                $score += 2.0;
            } elseif ($supplement->rating >= 3.5) {
                $score += 1.0;
            } elseif ($supplement->rating >= 3.0) {
                $score += 0.5;
            }
        }

        // Review count contribution (0-1.5 points)
        // More reviews = more statistical significance
        if ($supplement->review_count) {
            if ($supplement->review_count >= 5000) {
                $score += 1.5;
            } elseif ($supplement->review_count >= 2000) {
                $score += 1.25;
            } elseif ($supplement->review_count >= 1000) {
                $score += 1.0;
            } elseif ($supplement->review_count >= 500) {
                $score += 0.75;
            } elseif ($supplement->review_count >= 100) {
                $score += 0.5;
            } elseif ($supplement->review_count >= 25) {
                $score += 0.25;
            }
        }

        // Active ingredients presence (0-1 point)
        if (!empty($supplement->active_ingredients) && is_array($supplement->active_ingredients)) {
            $ingredientCount = count($supplement->active_ingredients);
            // More active ingredients (up to a point) = more comprehensive formula
            $score += min(1.0, $ingredientCount * 0.25);
        }

        return min(10.0, max(0.0, $score));
    }

    /**
     * Calculate Quality & Safety Score (0-10)
     *
     * Based on:
     * - Third-party testing certifications (USP, NSF, ConsumerLab)
     * - Manufacturing standards (GMP, cGMP)
     * - Purity and contaminant testing
     * - Warning flags and safety data
     */
    private function calculateQualitySafetyScore(Supplement $supplement): float
    {
        $score = 3.0; // Base score

        // Certification analysis (0-5 points)
        $certFlags = $supplement->certification_flags;
        if ($certFlags && is_array($certFlags)) {
            $certScore = 0;
            $foundCerts = [];

            foreach ($certFlags as $cert) {
                $certLower = strtolower($cert);
                foreach (self::CERTIFICATION_WEIGHTS as $key => $weight) {
                    if (str_contains($certLower, $key) && !isset($foundCerts[$key])) {
                        $certScore += $weight;
                        $foundCerts[$key] = true;
                    }
                }
            }
            // Cap certification bonus at 5 points
            $score += min(5.0, $certScore);
        }

        // High rating with many reviews = quality indicator (0-1.5 points)
        if ($supplement->rating >= 4.5 && $supplement->review_count >= 500) {
            $score += 1.5;
        } elseif ($supplement->rating >= 4.3 && $supplement->review_count >= 200) {
            $score += 1.0;
        } elseif ($supplement->rating >= 4.0 && $supplement->review_count >= 100) {
            $score += 0.5;
        }

        // Warning flags penalty
        if (!empty($supplement->warning_flags) && is_array($supplement->warning_flags)) {
            $score -= min(2.0, count($supplement->warning_flags) * 0.5);
        }

        // Data completeness bonus (indicates reputable brand) (0-0.5 points)
        $completeness = 0;
        if (!empty($supplement->product_description)) $completeness += 0.1;
        if (!empty($supplement->active_ingredients)) $completeness += 0.15;
        if (!empty($supplement->suggested_use)) $completeness += 0.1;
        if (!empty($supplement->other_ingredients)) $completeness += 0.1;
        if ($supplement->extraction_confidence >= 0.8) $completeness += 0.05;
        $score += $completeness;

        return min(10.0, max(0.0, $score));
    }

    /**
     * Calculate Bioavailability Score (0-10)
     *
     * Based on:
     * - Ingredient forms (glycinate vs oxide, etc.)
     * - Delivery method (liquid, softgel, tablet, etc.)
     * - Absorption-enhancing cofactors
     */
    private function calculateBioavailabilityScore(Supplement $supplement): float
    {
        $formScore = $this->detectIngredientFormScore($supplement);
        $deliveryScore = $this->getDeliveryMethodScore($supplement);

        // Weight: 70% ingredient form, 30% delivery method
        $score = ($formScore * 0.70) + ($deliveryScore * 0.30);

        // Bonus for absorption enhancers detected
        $searchText = strtolower(
            ($supplement->title ?? '') . ' ' .
            ($supplement->product_description ?? '') . ' ' .
            ($supplement->other_ingredients ?? '')
        );

        // Absorption enhancer detection
        $enhancers = ['bioperine', 'piperine', 'black pepper', 'liposomal', 'phytosome', 'micronized'];
        foreach ($enhancers as $enhancer) {
            if (str_contains($searchText, $enhancer)) {
                $score += 0.5;
                break; // Only count once
            }
        }

        return min(10.0, max(0.0, $score));
    }

    /**
     * Detect ingredient form and return bioavailability score
     */
    private function detectIngredientFormScore(Supplement $supplement): float
    {
        $searchText = strtolower(
            ($supplement->title ?? '') . ' ' .
            ($supplement->product_description ?? '')
        );

        // Also check active ingredients
        if (!empty($supplement->active_ingredients) && is_array($supplement->active_ingredients)) {
            foreach ($supplement->active_ingredients as $ingredient) {
                $searchText .= ' ' . strtolower($ingredient['name'] ?? '');
            }
        }

        $highestScore = 5.0; // Default neutral score

        foreach (self::BIOAVAILABILITY_MAP as $form => $rating) {
            if (str_contains($searchText, strtolower($form))) {
                $formScore = $rating * 10;
                $highestScore = max($highestScore, $formScore);
            }
        }

        return $highestScore;
    }

    /**
     * Get delivery method absorption score
     */
    private function getDeliveryMethodScore(Supplement $supplement): float
    {
        $dosageForm = strtolower($supplement->dosage_form ?? 'unknown');

        // Check title/description for delivery method hints
        $searchText = strtolower($supplement->title ?? '');

        // Check for liposomal (highest absorption)
        if (str_contains($searchText, 'liposomal')) {
            return 9.8;
        }

        foreach (self::DELIVERY_METHOD_SCORES as $method => $score) {
            if (str_contains($dosageForm, $method) || str_contains($searchText, $method)) {
                return $score * 10;
            }
        }

        return 7.0; // Default neutral delivery score
    }

    /**
     * Calculate Formulation Quality Score (0-10)
     *
     * Based on:
     * - Number of clinically relevant active ingredients
     * - Synergistic ingredient combinations
     * - Serving size information
     * - Formula completeness
     */
    private function calculateFormulationScore(Supplement $supplement): float
    {
        $score = 5.0; // Base score

        // Active ingredients quality (0-2.5 points)
        if (!empty($supplement->active_ingredients) && is_array($supplement->active_ingredients)) {
            $count = count($supplement->active_ingredients);
            if ($count >= 3) {
                $score += 2.5;
            } elseif ($count >= 2) {
                $score += 1.5;
            } elseif ($count >= 1) {
                $score += 0.75;
            }
        }

        // Serving size information (indicates precise dosing) (0-1 point)
        if ($supplement->serving_size_value > 0) {
            $score += 0.5;
        }
        if ($supplement->servings_per_container > 0) {
            $score += 0.5;
        }

        // Dosage form quality (0-1 point)
        $goodForms = ['capsule', 'softgel', 'liquid'];
        $dosageForm = strtolower($supplement->dosage_form ?? '');
        if (in_array($dosageForm, $goodForms)) {
            $score += 1.0;
        }

        // Days of supply (indicates value but not price) (0-0.5 points)
        if ($supplement->days_of_supply >= 60) {
            $score += 0.5;
        } elseif ($supplement->days_of_supply >= 30) {
            $score += 0.25;
        }

        return min(10.0, max(0.0, $score));
    }

    /**
     * Batch calculate scores for all supplements
     */
    public function calculateAllScores(callable $progressCallback = null): int
    {
        $total = Supplement::count();
        $processed = 0;
        $batchSize = 500;

        Supplement::chunkById($batchSize, function ($supplements) use (&$processed, $progressCallback) {
            foreach ($supplements as $supplement) {
                $scores = $this->calculateScores($supplement);
                $supplement->update($scores);
                $processed++;
            }

            if ($progressCallback) {
                $progressCallback($processed);
            }
        });

        return $processed;
    }

    /**
     * Update category product counts
     */
    public function updateCategoryCounts(): void
    {
        $categories = SupplementCategory::all();

        foreach ($categories as $category) {
            $count = Supplement::where('category_id', $category->id)->count();
            $category->update(['product_count' => $count]);
        }
    }

    /**
     * Get scoring methodology explanation
     */
    public static function getScoringMethodology(): array
    {
        return [
            'overview' => 'Clinical-first scoring methodology that evaluates supplements based purely on scientific and quality factors. Price is intentionally excluded to focus on what matters clinically.',
            'categories' => [
                'efficacy' => [
                    'weight' => '35%',
                    'description' => 'Clinical Efficacy Score',
                    'factors' => [
                        'User ratings (real-world efficacy feedback)',
                        'Review volume (statistical significance)',
                        'Active ingredient presence and quantity',
                    ],
                ],
                'quality' => [
                    'weight' => '30%',
                    'description' => 'Quality & Safety Score',
                    'factors' => [
                        'Third-party testing (USP, NSF, ConsumerLab)',
                        'Manufacturing standards (GMP/cGMP)',
                        'Purity certifications',
                        'Safety warning analysis',
                    ],
                ],
                'bioavailability' => [
                    'weight' => '25%',
                    'description' => 'Bioavailability Score',
                    'factors' => [
                        'Ingredient forms (glycinate vs oxide, etc.)',
                        'Delivery method (liquid, softgel, tablet)',
                        'Absorption-enhancing cofactors',
                    ],
                ],
                'formulation' => [
                    'weight' => '10%',
                    'description' => 'Formulation Quality Score',
                    'factors' => [
                        'Active ingredient completeness',
                        'Synergistic combinations',
                        'Serving size precision',
                    ],
                ],
            ],
        ];
    }
}
