<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HealthFunction;
use App\Models\Supplement;
use App\Models\SupplementCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BrowseApiController extends Controller
{
    /**
     * GET /api/browse/functions
     * All health functions with category counts.
     */
    public function functions(): JsonResponse
    {
        $functions = HealthFunction::orderBy('sort_order')
            ->get()
            ->map(fn($f) => [
                'id' => $f->id,
                'name' => $f->name,
                'name_el' => $f->name_el,
                'slug' => $f->slug,
                'icon' => $f->icon,
                'description_el' => $f->description_el,
                'category_count' => $f->categories()->count(),
            ]);

        return response()->json(['data' => $functions]);
    }

    /**
     * GET /api/browse/functions/{slug}/categories
     * Categories for a health function with product counts.
     */
    public function functionCategories(string $slug): JsonResponse
    {
        $function = HealthFunction::where('slug', $slug)->firstOrFail();

        $categories = $function->categories()
            ->get()
            ->map(fn($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'icon' => $cat->icon,
                'product_count' => $cat->product_count,
                'relevance_score' => $cat->pivot->relevance_score,
            ]);

        return response()->json([
            'function' => [
                'name' => $function->name,
                'name_el' => $function->name_el,
                'slug' => $function->slug,
                'icon' => $function->icon,
            ],
            'categories' => $categories,
        ]);
    }

    /**
     * GET /api/browse/categories/{slug}/supplements
     * Supplements in a category with brand filter and sorting.
     */
    public function categorySupplements(Request $request, string $slug): JsonResponse
    {
        $category = SupplementCategory::where('slug', $slug)->firstOrFail();

        $query = Supplement::where('category_id', $category->id);

        // Brand filter
        if ($request->has('brands')) {
            $brands = is_array($request->brands) ? $request->brands : explode(',', $request->brands);
            $query->whereIn('brand', $brands);
        }

        // Sorting (no price sorting)
        $sort = $request->get('sort', 'score');
        $query = match ($sort) {
            'dose' => $query->orderByDesc('clinical_dose_score'),
            'bioavailability' => $query->orderByDesc('bioavailability_score'),
            'quality' => $query->orderByDesc('quality_score'),
            default => $query->orderByDesc('overall_recommendation_score'),
        };

        $supplements = $query->paginate($request->get('per_page', 20));

        $supplements->getCollection()->transform(fn($s) => [
            'id' => $s->id,
            'title' => $s->title,
            'brand' => $s->brand,
            'category_rank' => $s->category_rank,
            'overall_score' => $s->overall_recommendation_score,
            'clinical_dose_score' => $s->clinical_dose_score,
            'bioavailability_score' => $s->bioavailability_score,
            'quality_score' => $s->quality_score,
            'dosage_form' => $s->dosage_form,
            'servings_per_container' => $s->servings_per_container,
            'red_flags' => $s->red_flags_detected ?? [],
            'brand_trust_score' => $s->brand_trust_score,
            'image_url' => $s->image_url,
        ]);

        return response()->json($supplements);
    }

    /**
     * GET /api/browse/categories/{slug}/brands
     * Brands within a category with counts and avg scores.
     */
    public function categoryBrands(string $slug): JsonResponse
    {
        $category = SupplementCategory::where('slug', $slug)->firstOrFail();

        $brands = Supplement::where('category_id', $category->id)
            ->whereNotNull('brand')
            ->select('brand')
            ->selectRaw('COUNT(*) as product_count')
            ->selectRaw('ROUND(AVG(overall_recommendation_score), 2) as avg_score')
            ->selectRaw('ROUND(AVG(brand_trust_score), 2) as trust_score')
            ->groupBy('brand')
            ->orderByDesc('avg_score')
            ->get();

        return response()->json(['data' => $brands]);
    }

    /**
     * GET /api/browse/brands
     * All brands with counts, filterable by function or category.
     */
    public function brands(Request $request): JsonResponse
    {
        $query = Supplement::whereNotNull('brand');

        if ($request->has('category')) {
            $cat = SupplementCategory::where('slug', $request->category)->first();
            if ($cat) {
                $query->where('category_id', $cat->id);
            }
        }

        if ($request->has('function')) {
            $func = HealthFunction::where('slug', $request->function)->first();
            if ($func) {
                $categoryIds = $func->categories()->pluck('supplement_categories.id');
                $query->whereIn('category_id', $categoryIds);
            }
        }

        $brands = $query
            ->select('brand')
            ->selectRaw('COUNT(*) as product_count')
            ->selectRaw('ROUND(AVG(overall_recommendation_score), 2) as avg_score')
            ->selectRaw('ROUND(AVG(brand_trust_score), 2) as trust_score')
            ->groupBy('brand')
            ->orderByDesc('avg_score')
            ->limit(100)
            ->get();

        return response()->json(['data' => $brands]);
    }
}
