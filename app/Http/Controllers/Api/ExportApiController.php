<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedicalCondition;
use App\Models\Supplement;
use App\Models\SupplementCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class ExportApiController extends Controller
{
    /**
     * GET /api/export/supplements
     * Paginated full export for CRM sync
     */
    public function supplements(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 100), 500);

        $query = Supplement::with('category');

        // Optional filters
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('min_score')) {
            $query->where('overall_recommendation_score', '>=', $request->min_score);
        }

        $supplements = $query
            ->orderBy('id')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $supplements->items(),
            'meta' => [
                'current_page' => $supplements->currentPage(),
                'last_page' => $supplements->lastPage(),
                'per_page' => $supplements->perPage(),
                'total' => $supplements->total(),
                'has_more' => $supplements->hasMorePages(),
                'next_page_url' => $supplements->nextPageUrl(),
            ],
        ]);
    }

    /**
     * GET /api/export/summary
     * Lightweight export (id, name, score only)
     */
    public function summary(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 500), 2000);

        $supplements = Supplement::select([
            'id',
            'brand',
            'title',
            'category_id',
            'overall_recommendation_score',
            'efficacy_score',
            'quality_score',
            'bioavailability_score',
            'formulation_score',
            'current_price',
            'rating',
            'updated_at',
        ])
            ->orderBy('id')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $supplements->items(),
            'meta' => [
                'current_page' => $supplements->currentPage(),
                'last_page' => $supplements->lastPage(),
                'per_page' => $supplements->perPage(),
                'total' => $supplements->total(),
                'has_more' => $supplements->hasMorePages(),
            ],
        ]);
    }

    /**
     * GET /api/export/changes
     * Delta sync - only records changed since timestamp
     */
    public function changes(Request $request): JsonResponse
    {
        $since = $request->get('since');

        if (!$since) {
            return response()->json([
                'success' => false,
                'error' => 'Please provide "since" parameter (ISO 8601 timestamp)',
            ], 400);
        }

        try {
            $sinceDate = Carbon::parse($since);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid date format. Use ISO 8601 (e.g., 2024-01-15T10:30:00Z)',
            ], 400);
        }

        $perPage = min($request->get('per_page', 500), 2000);

        $supplements = Supplement::where('updated_at', '>', $sinceDate)
            ->with('category')
            ->orderBy('updated_at')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $supplements->items(),
            'meta' => [
                'since' => $since,
                'current_page' => $supplements->currentPage(),
                'last_page' => $supplements->lastPage(),
                'per_page' => $supplements->perPage(),
                'total_changes' => $supplements->total(),
                'has_more' => $supplements->hasMorePages(),
                'sync_timestamp' => Carbon::now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * GET /api/export/categories
     * Export all categories
     */
    public function categories(): JsonResponse
    {
        $categories = SupplementCategory::orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
            'meta' => [
                'total' => $categories->count(),
                'exported_at' => Carbon::now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * GET /api/export/conditions
     * Export all conditions
     */
    public function conditions(): JsonResponse
    {
        $conditions = MedicalCondition::orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $conditions,
            'meta' => [
                'total' => $conditions->count(),
                'exported_at' => Carbon::now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * GET /api/export/full
     * Full database export (for initial sync)
     */
    public function full(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'categories' => SupplementCategory::all(),
                'conditions' => MedicalCondition::all(),
                'supplements_count' => Supplement::count(),
                'supplements_endpoint' => '/api/export/supplements',
            ],
            'meta' => [
                'exported_at' => Carbon::now()->toIso8601String(),
                'instructions' => 'Use /api/export/supplements with pagination to fetch all supplements',
            ],
        ]);
    }
}
