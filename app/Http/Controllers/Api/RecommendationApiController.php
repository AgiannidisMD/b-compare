<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsEvent;
use App\Models\MedicalCondition;
use App\Models\RecommendationEvent;
use App\Models\Supplement;
use App\Models\SupplementCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RecommendationApiController extends Controller
{
    /**
     * POST /api/recommend
     * Get personalized recommendations
     */
    public function recommend(Request $request): JsonResponse
    {
        $conditions = $request->get('conditions', []);
        $categorySlug = $request->get('category');
        $categoryId = $request->get('category_id');
        $allergens = $request->get('allergens', []);
        $preferences = $request->get('preferences', []);
        $limit = min($request->get('limit', 5), 20);

        // Build query
        $query = Supplement::with('category');

        // Filter by category
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        } elseif ($categorySlug) {
            $category = SupplementCategory::where('slug', $categorySlug)->first();
            if ($category) {
                $query->where('category_id', $category->id);
                $categoryId = $category->id;
            }
        }

        // Filter by conditions (if we have condition mappings)
        if (!empty($conditions)) {
            $conditionIds = MedicalCondition::whereIn('slug', $conditions)
                ->orWhereIn('name', $conditions)
                ->pluck('id');

            if ($conditionIds->isNotEmpty()) {
                $query->whereHas('conditions', function ($q) use ($conditionIds) {
                    $q->whereIn('medical_conditions.id', $conditionIds);
                });
            }
        }

        // Filter out allergens
        if (!empty($allergens)) {
            foreach ($allergens as $allergen) {
                $query->where(function ($q) use ($allergen) {
                    $q->whereNull('allergen_contains_flags')
                        ->orWhereRaw("NOT JSON_CONTAINS(allergen_contains_flags, ?)", ['"' . $allergen . '"']);
                });
            }
        }

        // Apply preferences
        if (in_array('vegan', $preferences)) {
            $query->whereRaw("JSON_CONTAINS(certification_flags, '\"vegan\"')");
        }
        if (in_array('organic', $preferences)) {
            $query->whereRaw("JSON_CONTAINS(certification_flags, '\"organic\"')");
        }
        if (in_array('gluten-free', $preferences)) {
            $query->whereRaw("JSON_CONTAINS(allergen_free_from_flags, '\"gluten\"')");
        }

        // Order by score and get results
        $supplements = $query
            ->whereNotNull('overall_recommendation_score')
            ->orderBy('overall_recommendation_score', 'desc')
            ->limit($limit)
            ->get();

        // Log recommendation event
        if ($supplements->isNotEmpty()) {
            RecommendationEvent::create([
                'category_id' => $categoryId,
                'condition_ids' => $conditionIds ?? [],
                'supplement_ids' => $supplements->pluck('id')->toArray(),
                'supplements_count' => $supplements->count(),
                'session_id' => $request->get('session_id'),
                'user_ip' => $request->ip(),
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'recommendations' => $supplements,
                'filters_applied' => [
                    'category' => $categorySlug ?? $categoryId,
                    'conditions' => $conditions,
                    'allergens' => $allergens,
                    'preferences' => $preferences,
                ],
            ],
            'meta' => [
                'count' => $supplements->count(),
            ],
        ]);
    }

    /**
     * GET /api/recommend/condition/{slug}
     * Top supplements for a specific condition
     */
    public function forCondition(string $slug, Request $request): JsonResponse
    {
        $condition = MedicalCondition::where('slug', $slug)->first();

        if (!$condition) {
            return response()->json([
                'success' => false,
                'error' => 'Condition not found',
            ], 404);
        }

        $limit = min($request->get('limit', 10), 50);

        $supplements = Supplement::whereHas('conditions', function ($q) use ($condition) {
            $q->where('medical_conditions.id', $condition->id);
        })
            ->with('category')
            ->orderBy('overall_recommendation_score', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'condition' => $condition,
                'recommendations' => $supplements,
            ],
        ]);
    }

    /**
     * GET /api/recommend/category/{slug}
     * Top supplements in a category
     */
    public function forCategory(string $slug, Request $request): JsonResponse
    {
        $category = SupplementCategory::where('slug', $slug)->first();

        if (!$category) {
            return response()->json([
                'success' => false,
                'error' => 'Category not found',
            ], 404);
        }

        $limit = min($request->get('limit', 10), 50);

        $supplements = Supplement::where('category_id', $category->id)
            ->whereNotNull('overall_recommendation_score')
            ->orderBy('overall_recommendation_score', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'category' => $category,
                'recommendations' => $supplements,
            ],
        ]);
    }

    /**
     * GET /api/scoring/explain/{id}
     * Get detailed score breakdown
     */
    public function explainScore(int $id): JsonResponse
    {
        $supplement = Supplement::with('category')->find($id);

        if (!$supplement) {
            return response()->json([
                'success' => false,
                'error' => 'Supplement not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'supplement' => [
                    'id' => $supplement->id,
                    'brand' => $supplement->brand,
                    'title' => $supplement->title,
                ],
                'scores' => [
                    'overall' => $supplement->overall_recommendation_score,
                    'efficacy' => [
                        'score' => $supplement->efficacy_score,
                        'weight' => '35%',
                        'factors' => [
                            'rating' => $supplement->rating,
                            'review_count' => $supplement->review_count,
                            'has_active_ingredients' => !empty($supplement->active_ingredients),
                        ],
                    ],
                    'quality' => [
                        'score' => $supplement->quality_score,
                        'weight' => '30%',
                        'factors' => [
                            'certifications' => $supplement->certification_flags ?? [],
                            'has_warnings' => !empty($supplement->warning_flags),
                        ],
                    ],
                    'bioavailability' => [
                        'score' => $supplement->bioavailability_score,
                        'weight' => '25%',
                        'factors' => [
                            'dosage_form' => $supplement->dosage_form,
                            'active_ingredients' => $supplement->active_ingredients ?? [],
                        ],
                    ],
                    'formulation' => [
                        'score' => $supplement->formulation_score,
                        'weight' => '10%',
                        'factors' => [
                            'serving_size' => $supplement->serving_size_value . ' ' . $supplement->serving_size_unit,
                            'servings_per_container' => $supplement->servings_per_container,
                            'days_of_supply' => $supplement->days_of_supply,
                        ],
                    ],
                ],
                'methodology' => 'Clinical Score = (Efficacy × 35%) + (Quality × 30%) + (Bioavailability × 25%) + (Formulation × 10%)',
            ],
        ]);
    }
}
