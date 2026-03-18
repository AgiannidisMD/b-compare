<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedicalCondition;
use App\Models\Supplement;
use App\Models\SupplementCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class StatsApiController extends Controller
{
    /**
     * GET /api/stats/overview
     * Overall statistics
     */
    public function overview(): JsonResponse
    {
        $stats = [
            'supplements' => [
                'total' => Supplement::count(),
                'with_scores' => Supplement::whereNotNull('overall_recommendation_score')->count(),
                'avg_score' => round(Supplement::avg('overall_recommendation_score'), 2),
                'avg_rating' => round(Supplement::avg('rating'), 2),
                'avg_price' => round(Supplement::avg('current_price'), 2),
            ],
            'categories' => [
                'total' => SupplementCategory::count(),
                'with_products' => SupplementCategory::where('product_count', '>', 0)->count(),
            ],
            'conditions' => [
                'total' => MedicalCondition::count(),
            ],
            'brands' => [
                'total' => Supplement::distinct('brand')->count('brand'),
            ],
            'score_distribution' => $this->getScoreDistribution(),
            'dosage_forms' => $this->getDosageFormDistribution(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * GET /api/stats/categories
     * Statistics per category
     */
    public function categories(): JsonResponse
    {
        $categories = SupplementCategory::orderBy('product_count', 'desc')->get();

        $stats = $categories->map(function ($category) {
            $supplements = Supplement::where('category_id', $category->id);

            return [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'product_count' => $category->product_count,
                'avg_score' => round($supplements->avg('overall_recommendation_score'), 2),
                'avg_efficacy' => round($supplements->avg('efficacy_score'), 2),
                'avg_quality' => round($supplements->avg('quality_score'), 2),
                'avg_bioavailability' => round($supplements->avg('bioavailability_score'), 2),
                'avg_price' => round($supplements->avg('current_price'), 2),
                'avg_rating' => round($supplements->avg('rating'), 2),
                'top_brand' => $supplements->clone()
                    ->selectRaw('brand, COUNT(*) as count')
                    ->groupBy('brand')
                    ->orderByDesc('count')
                    ->first()?->brand,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * GET /api/stats/brands
     * Top brands statistics
     */
    public function brands(): JsonResponse
    {
        $brands = Supplement::selectRaw('
                brand,
                COUNT(*) as product_count,
                ROUND(AVG(overall_recommendation_score), 2) as avg_score,
                ROUND(AVG(rating), 2) as avg_rating,
                ROUND(AVG(current_price), 2) as avg_price,
                SUM(review_count) as total_reviews
            ')
            ->whereNotNull('brand')
            ->groupBy('brand')
            ->orderByDesc('product_count')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $brands,
            'meta' => [
                'total_brands' => Supplement::distinct('brand')->count('brand'),
            ],
        ]);
    }

    /**
     * GET /api/stats/scores
     * Score distribution statistics
     */
    public function scores(): JsonResponse
    {
        $distribution = $this->getScoreDistribution();

        $percentiles = Supplement::whereNotNull('overall_recommendation_score')
            ->selectRaw('
                MIN(overall_recommendation_score) as min,
                MAX(overall_recommendation_score) as max,
                AVG(overall_recommendation_score) as mean
            ')
            ->first();

        // Get median
        $count = Supplement::whereNotNull('overall_recommendation_score')->count();
        $median = Supplement::whereNotNull('overall_recommendation_score')
            ->orderBy('overall_recommendation_score')
            ->offset((int) floor($count / 2))
            ->limit(1)
            ->value('overall_recommendation_score');

        return response()->json([
            'success' => true,
            'data' => [
                'distribution' => $distribution,
                'statistics' => [
                    'min' => round($percentiles->min, 2),
                    'max' => round($percentiles->max, 2),
                    'mean' => round($percentiles->mean, 2),
                    'median' => round($median, 2),
                ],
                'by_score_component' => [
                    'efficacy' => [
                        'avg' => round(Supplement::avg('efficacy_score'), 2),
                        'min' => round(Supplement::min('efficacy_score'), 2),
                        'max' => round(Supplement::max('efficacy_score'), 2),
                    ],
                    'quality' => [
                        'avg' => round(Supplement::avg('quality_score'), 2),
                        'min' => round(Supplement::min('quality_score'), 2),
                        'max' => round(Supplement::max('quality_score'), 2),
                    ],
                    'bioavailability' => [
                        'avg' => round(Supplement::avg('bioavailability_score'), 2),
                        'min' => round(Supplement::min('bioavailability_score'), 2),
                        'max' => round(Supplement::max('bioavailability_score'), 2),
                    ],
                    'formulation' => [
                        'avg' => round(Supplement::avg('formulation_score'), 2),
                        'min' => round(Supplement::min('formulation_score'), 2),
                        'max' => round(Supplement::max('formulation_score'), 2),
                    ],
                ],
            ],
        ]);
    }

    /**
     * GET /api/stats/certifications
     * Certification breakdown
     */
    public function certifications(): JsonResponse
    {
        // Get all certifications
        $certifications = [];
        $supplements = Supplement::whereNotNull('certification_flags')->get(['certification_flags']);

        foreach ($supplements as $supplement) {
            if (is_array($supplement->certification_flags)) {
                foreach ($supplement->certification_flags as $cert) {
                    $certLower = strtolower(trim($cert));
                    if (!isset($certifications[$certLower])) {
                        $certifications[$certLower] = 0;
                    }
                    $certifications[$certLower]++;
                }
            }
        }

        arsort($certifications);

        return response()->json([
            'success' => true,
            'data' => [
                'certifications' => array_slice($certifications, 0, 30, true),
                'total_supplements_with_certs' => Supplement::whereNotNull('certification_flags')
                    ->whereRaw("JSON_LENGTH(certification_flags) > 0")
                    ->count(),
                'total_supplements' => Supplement::count(),
            ],
        ]);
    }

    private function getScoreDistribution(): array
    {
        return [
            'excellent' => Supplement::where('overall_recommendation_score', '>=', 8)->count(),
            'good' => Supplement::whereBetween('overall_recommendation_score', [6, 7.99])->count(),
            'average' => Supplement::whereBetween('overall_recommendation_score', [4, 5.99])->count(),
            'below_average' => Supplement::where('overall_recommendation_score', '<', 4)->count(),
            'unscored' => Supplement::whereNull('overall_recommendation_score')->count(),
        ];
    }

    private function getDosageFormDistribution(): array
    {
        return Supplement::selectRaw('dosage_form, COUNT(*) as count')
            ->whereNotNull('dosage_form')
            ->groupBy('dosage_form')
            ->orderByDesc('count')
            ->pluck('count', 'dosage_form')
            ->toArray();
    }
}
