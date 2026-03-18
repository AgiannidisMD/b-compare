<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedicalCondition;
use App\Models\Supplement;
use App\Models\SupplementCategory;
use App\Models\SupplementConditionMapping;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoryApiController extends Controller
{
    /**
     * GET /api/categories
     * List all categories with counts
     */
    public function index(): JsonResponse
    {
        $categories = SupplementCategory::orderBy('product_count', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
            'meta' => [
                'total_categories' => $categories->count(),
                'total_products' => $categories->sum('product_count'),
            ],
        ]);
    }

    /**
     * GET /api/categories/{slug}
     * Category details with top supplements
     */
    public function show(string $slug, Request $request): JsonResponse
    {
        $category = SupplementCategory::where('slug', $slug)->first();

        if (!$category) {
            return response()->json([
                'success' => false,
                'error' => 'Category not found',
            ], 404);
        }

        $limit = min($request->get('limit', 10), 50);
        $topSupplements = Supplement::where('category_id', $category->id)
            ->orderBy('overall_recommendation_score', 'desc')
            ->limit($limit)
            ->get();

        // Score distribution for this category
        $scoreDistribution = Supplement::where('category_id', $category->id)
            ->selectRaw('
                COUNT(CASE WHEN overall_recommendation_score >= 8 THEN 1 END) as excellent,
                COUNT(CASE WHEN overall_recommendation_score >= 6 AND overall_recommendation_score < 8 THEN 1 END) as good,
                COUNT(CASE WHEN overall_recommendation_score >= 4 AND overall_recommendation_score < 6 THEN 1 END) as average,
                COUNT(CASE WHEN overall_recommendation_score < 4 THEN 1 END) as below_average
            ')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'category' => $category,
                'top_supplements' => $topSupplements,
                'stats' => [
                    'total_products' => $category->product_count,
                    'avg_score' => round(Supplement::where('category_id', $category->id)->avg('overall_recommendation_score'), 2),
                    'avg_price' => round(Supplement::where('category_id', $category->id)->avg('current_price'), 2),
                    'score_distribution' => $scoreDistribution,
                ],
            ],
        ]);
    }

    /**
     * GET /api/categories/{slug}/supplements
     * Get all supplements in a category
     */
    public function supplements(string $slug, Request $request): JsonResponse
    {
        $category = SupplementCategory::where('slug', $slug)->first();

        if (!$category) {
            return response()->json([
                'success' => false,
                'error' => 'Category not found',
            ], 404);
        }

        $query = Supplement::where('category_id', $category->id);

        // Sorting
        $sortBy = $request->get('sort_by', 'overall_recommendation_score');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        // Pagination
        $perPage = min($request->get('per_page', 20), 100);
        $supplements = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $supplements->items(),
            'meta' => [
                'category' => $category->name,
                'current_page' => $supplements->currentPage(),
                'last_page' => $supplements->lastPage(),
                'per_page' => $supplements->perPage(),
                'total' => $supplements->total(),
            ],
        ]);
    }

    /**
     * GET /api/conditions
     * List all medical conditions
     */
    public function conditions(): JsonResponse
    {
        $conditions = MedicalCondition::orderBy('name')->get();

        // Add recommended categories count for each condition
        $conditions = $conditions->map(function ($condition) {
            $recommendedCategoriesCount = SupplementConditionMapping::where('condition_id', $condition->id)
                ->distinct('supplement_id')
                ->count();
            $condition->recommended_supplements_count = $recommendedCategoriesCount;
            return $condition;
        });

        return response()->json([
            'success' => true,
            'data' => $conditions,
            'meta' => [
                'total_conditions' => $conditions->count(),
            ],
        ]);
    }

    /**
     * GET /api/conditions/{slug}
     * Condition details with recommended supplements
     */
    public function conditionShow(string $slug, Request $request): JsonResponse
    {
        $condition = MedicalCondition::where('slug', $slug)->first();

        if (!$condition) {
            return response()->json([
                'success' => false,
                'error' => 'Condition not found',
            ], 404);
        }

        $limit = min($request->get('limit', 10), 50);

        // Get recommended supplements for this condition
        $recommendedSupplements = Supplement::whereHas('conditions', function ($query) use ($condition) {
            $query->where('medical_conditions.id', $condition->id);
        })
            ->with('category')
            ->orderBy('overall_recommendation_score', 'desc')
            ->limit($limit)
            ->get();

        // Get top categories for this condition
        $topCategories = SupplementConditionMapping::where('condition_id', $condition->id)
            ->join('supplements', 'supplement_condition_mappings.supplement_id', '=', 'supplements.id')
            ->join('supplement_categories', 'supplements.category_id', '=', 'supplement_categories.id')
            ->selectRaw('supplement_categories.*, COUNT(*) as supplement_count, AVG(supplement_condition_mappings.efficacy_rating) as avg_efficacy')
            ->groupBy('supplement_categories.id')
            ->orderByDesc('avg_efficacy')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'condition' => $condition,
                'recommended_supplements' => $recommendedSupplements,
                'top_categories' => $topCategories,
            ],
        ]);
    }
}
