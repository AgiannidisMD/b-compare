<?php

namespace App\Services;

use App\Models\Supplement;
use App\Models\SupplementCategory;
use App\Services\Scoring\ClinicalScorer;
use Illuminate\Support\Facades\DB;

/**
 * Supplement Scoring Service
 *
 * Orchestrates the full scoring pipeline:
 * 1. Clinical scores (dose adequacy, form quality, certifications, formulation)
 * 2. Category rankings (rank + percentile within category)
 * 3. Brand trust scores (aggregated across all brand products)
 * 4. Category product counts
 *
 * All quality scoring is PRICE-FREE and REVIEW-FREE.
 * Price and reviews are stored as separate data points for users.
 */
class SupplementScoringService
{
    private ClinicalScorer $clinicalScorer;

    public function __construct()
    {
        $this->clinicalScorer = new ClinicalScorer();
    }

    /**
     * Calculate clinical scores for a single supplement.
     */
    public function calculateScores(Supplement $supplement): array
    {
        $categorySlug = $supplement->category?->slug;
        $scores = $this->clinicalScorer->score($supplement, $categorySlug);

        // value_score = overall quality (kept for backward compatibility, same as overall)
        $scores['value_score'] = $scores['overall_recommendation_score'];

        return $scores;
    }

    /**
     * Full scoring pipeline: scores → rankings → brand trust → category counts.
     */
    public function runFullPipeline(?callable $progressCallback = null): array
    {
        $results = [
            'scored' => 0,
            'ranked' => 0,
            'brands' => 0,
        ];

        // Phase 1: Calculate clinical scores for all supplements
        $results['scored'] = $this->calculateAllScores($progressCallback);

        // Phase 2: Calculate category rankings
        $results['ranked'] = $this->calculateCategoryRankings();

        // Phase 3: Calculate brand trust scores
        $results['brands'] = $this->calculateBrandTrustScores();

        // Phase 4: Update category counts
        $this->updateCategoryCounts();

        return $results;
    }

    /**
     * Batch calculate clinical scores for all supplements.
     */
    public function calculateAllScores(?callable $progressCallback = null): int
    {
        $processed = 0;
        $batchSize = 500;

        // Preload category slugs to avoid N+1
        $categoryMap = SupplementCategory::pluck('slug', 'id')->toArray();

        Supplement::chunkById($batchSize, function ($supplements) use (&$processed, $progressCallback, $categoryMap) {
            foreach ($supplements as $supplement) {
                $categorySlug = $categoryMap[$supplement->category_id] ?? null;
                $scores = $this->clinicalScorer->score($supplement, $categorySlug);
                $scores['value_score'] = $scores['overall_recommendation_score'];

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
     * Calculate rankings within each category.
     * Assigns category_rank (1 = best) and category_percentile (100 = top).
     */
    public function calculateCategoryRankings(): int
    {
        $ranked = 0;

        $categories = SupplementCategory::all();

        foreach ($categories as $category) {
            $count = Supplement::where('category_id', $category->id)->count();
            if ($count === 0) continue;

            // Rank by overall_recommendation_score DESC within category
            DB::statement("
                UPDATE supplements s
                JOIN (
                    SELECT id,
                        RANK() OVER (ORDER BY overall_recommendation_score DESC) as cat_rank
                    FROM supplements
                    WHERE category_id = ?
                ) ranked ON s.id = ranked.id
                SET s.category_rank = ranked.cat_rank,
                    s.category_percentile = ROUND((1 - (ranked.cat_rank - 1) / ?) * 100, 2)
            ", [$category->id, max(1, $count)]);

            $ranked += $count;
        }

        return $ranked;
    }

    /**
     * Calculate brand trust scores aggregated across all products.
     *
     * Brand trust = avg quality across all brand's products:
     * - Average overall_recommendation_score (50%)
     * - Average quality_score (30%)
     * - Red flag frequency inverted (20%)
     */
    public function calculateBrandTrustScores(): int
    {
        $brands = DB::table('supplements')
            ->select('brand')
            ->selectRaw('AVG(overall_recommendation_score) as avg_overall')
            ->selectRaw('AVG(quality_score) as avg_quality')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN red_flags_detected IS NOT NULL AND JSON_LENGTH(red_flags_detected) > 0 THEN 1 ELSE 0 END) as flagged_count")
            ->whereNotNull('overall_recommendation_score')
            ->groupBy('brand')
            ->get();

        $updated = 0;

        foreach ($brands as $brand) {
            $flagRate = $brand->total > 0 ? $brand->flagged_count / $brand->total : 0;
            $cleanRate = 1.0 - $flagRate;

            $trustScore = (
                (($brand->avg_overall ?? 5) * 0.50) +
                (($brand->avg_quality ?? 5) * 0.30) +
                ($cleanRate * 10 * 0.20)
            );

            $trustScore = round(min(10, max(0, $trustScore)), 2);

            $count = DB::table('supplements')
                ->where('brand', $brand->brand)
                ->update(['brand_trust_score' => $trustScore]);

            $updated += $count;
        }

        return $updated;
    }

    /**
     * Update category product counts.
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
     * Get scoring methodology explanation.
     */
    public static function getScoringMethodology(): array
    {
        return [
            'overview' => 'Pure clinical scoring — evaluates supplements based on ingredient quality, dose adequacy, and scientific evidence. Price and reviews are excluded from quality scores and provided as separate data points.',
            'categories' => [
                'efficacy' => [
                    'weight' => '40%',
                    'description' => 'Clinical Efficacy',
                    'factors' => [
                        'Dose adequacy vs therapeutic range (50%)',
                        'Ingredient form quality (30%)',
                        'Cofactor synergy (20%)',
                    ],
                ],
                'bioavailability' => [
                    'weight' => '25%',
                    'description' => 'Bioavailability',
                    'factors' => [
                        'Ingredient form absorption rate (70%)',
                        'Delivery method efficiency (30%)',
                    ],
                ],
                'quality' => [
                    'weight' => '20%',
                    'description' => 'Quality & Safety',
                    'factors' => [
                        'Third-party certifications (50%)',
                        'Data completeness (30%)',
                        'Red flag absence (20%)',
                    ],
                ],
                'formulation' => [
                    'weight' => '15%',
                    'description' => 'Formulation Quality',
                    'factors' => [
                        'Active ingredient completeness',
                        'Cofactor presence',
                        'Serving precision',
                    ],
                ],
            ],
            'separate_data' => [
                'price' => 'Cost per serving and cost per day — shown to users but not in quality score',
                'reviews' => 'Rating and review count — shown to users but not in quality score',
                'red_flags' => 'Clinical red flags detected — shown prominently to warn users',
                'category_rank' => 'Position within category (#1, #2, etc.)',
            ],
        ];
    }
}
