<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplement;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SupplementApiController extends Controller
{
    /**
     * GET /api/supplements
     * List supplements with filters
     */
    public function index(Request $request): JsonResponse
    {
        $query = Supplement::query()->with('category');

        // Filters
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('category_slug')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category_slug);
            });
        }

        if ($request->has('brand')) {
            $query->where('brand', 'like', '%' . $request->brand . '%');
        }

        if ($request->has('min_score')) {
            $query->where('overall_recommendation_score', '>=', $request->min_score);
        }

        if ($request->has('max_score')) {
            $query->where('overall_recommendation_score', '<=', $request->max_score);
        }

        if ($request->has('dosage_form')) {
            $query->where('dosage_form', $request->dosage_form);
        }

        if ($request->has('min_rating')) {
            $query->where('rating', '>=', $request->min_rating);
        }

        // Search filter
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('brand', 'like', '%' . $search . '%')
                    ->orWhere('title', 'like', '%' . $search . '%');
            });
        }

        // Sorting - support both 'sort_by' and 'sort' params
        $sortBy = $request->get('sort_by', $request->get('sort', 'overall_recommendation_score'));
        $sortDir = $request->get('sort_dir', $request->get('order', 'desc'));
        $query->orderBy($sortBy, $sortDir);

        // Pagination
        $perPage = min($request->get('per_page', 20), 100);
        $supplements = $query->paginate($perPage);

        // Return in Laravel pagination format (expected by dashboard)
        return response()->json($supplements);
    }

    /**
     * GET /api/supplements/{id}
     * Get single supplement with full details
     */
    public function show(int $id): JsonResponse
    {
        $supplement = Supplement::with(['category', 'conditions'])->find($id);

        if (!$supplement) {
            return response()->json([
                'success' => false,
                'error' => 'Supplement not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $supplement,
        ]);
    }

    /**
     * GET /api/supplements/search
     * Full-text search
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json([
                'success' => false,
                'error' => 'Search query must be at least 2 characters',
            ], 400);
        }

        $supplements = Supplement::with('category')
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', '%' . $query . '%')
                    ->orWhere('brand', 'like', '%' . $query . '%')
                    ->orWhere('product_description', 'like', '%' . $query . '%');
            })
            ->orderBy('overall_recommendation_score', 'desc')
            ->limit($request->get('limit', 20))
            ->get();

        return response()->json([
            'success' => true,
            'data' => $supplements,
            'meta' => [
                'query' => $query,
                'count' => $supplements->count(),
            ],
        ]);
    }

    /**
     * POST /api/supplements/compare
     * Compare multiple supplements
     */
    public function compare(Request $request): JsonResponse
    {
        $ids = $request->get('ids', []);

        if (count($ids) < 2 || count($ids) > 5) {
            return response()->json([
                'success' => false,
                'error' => 'Please provide 2-5 supplement IDs to compare',
            ], 400);
        }

        $supplements = Supplement::with('category')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $items = $supplements->values()->map(fn($s) => [
            'id' => $s->id,
            'brand' => $s->brand,
            'title' => $s->title,
            'category' => $s->category?->name,
            'overall_score' => $s->overall_recommendation_score,
            'clinical_dose_score' => $s->clinical_dose_score,
            'efficacy_score' => $s->efficacy_score,
            'bioavailability_score' => $s->bioavailability_score,
            'quality_score' => $s->quality_score,
            'formulation_score' => $s->formulation_score,
            'brand_trust_score' => $s->brand_trust_score,
            'category_rank' => $s->category_rank,
            'dosage_form' => $s->dosage_form,
            'serving_size' => ($s->serving_size_value ?? '') . ' ' . ($s->serving_size_unit ?? ''),
            'servings_per_container' => $s->servings_per_container,
            'active_ingredients' => $s->active_ingredients,
            'red_flags' => $s->red_flags_detected ?? [],
            'certification_flags' => $s->certification_flags,
            'image_url' => $s->image_url,
            'product_url' => $s->product_url,
        ]);

        $winner = $supplements->sortByDesc('overall_recommendation_score')->first();

        return response()->json([
            'success' => true,
            'data' => [
                'supplements' => $items,
                'summary' => [
                    'best_overall' => $winner?->id,
                    'best_dose' => $supplements->sortByDesc('clinical_dose_score')->first()?->id,
                    'best_bioavailability' => $supplements->sortByDesc('bioavailability_score')->first()?->id,
                    'best_quality' => $supplements->sortByDesc('quality_score')->first()?->id,
                ],
            ],
        ]);
    }

    /**
     * GET /api/supplements/by-ids
     * Get multiple supplements by IDs
     */
    public function byIds(Request $request): JsonResponse
    {
        $ids = explode(',', $request->get('ids', ''));
        $ids = array_filter(array_map('intval', $ids));

        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'error' => 'Please provide supplement IDs',
            ], 400);
        }

        $supplements = Supplement::with('category')
            ->whereIn('id', $ids)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $supplements,
            'meta' => [
                'requested' => count($ids),
                'found' => $supplements->count(),
            ],
        ]);
    }

    /**
     * GET /api/supplements/top-rated
     * Get top rated supplements
     */
    public function topRated(Request $request): JsonResponse
    {
        $query = Supplement::with('category')
            ->whereNotNull('overall_recommendation_score')
            ->orderBy('overall_recommendation_score', 'desc');

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $limit = min($request->get('limit', 10), 50);
        $supplements = $query->limit($limit)->get();

        return response()->json([
            'success' => true,
            'data' => $supplements,
        ]);
    }
}
