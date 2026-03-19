<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\Api\SupplementApiController;
use App\Http\Controllers\Api\CategoryApiController;
use App\Http\Controllers\Api\RecommendationApiController;
use App\Http\Controllers\Api\StatsApiController;
use App\Http\Controllers\Api\AnalyticsApiController;
use App\Http\Controllers\Api\ExportApiController;
use App\Http\Controllers\Api\BrowseApiController;

/*
|--------------------------------------------------------------------------
| VITA-COMPARE API Routes
|--------------------------------------------------------------------------
|
| Comprehensive API for NutriCRM integration
| Base URL: /api
|
*/

Route::middleware('api')->group(function () {

    // =====================================================================
    // CHAT ENDPOINTS (existing)
    // =====================================================================
    Route::prefix('chat')->group(function () {
        Route::post('/message', [ChatController::class, 'sendMessage']);
        Route::post('/stream', [ChatController::class, 'streamMessage']); // SSE streaming
        Route::post('/select-category', [ChatController::class, 'selectCategory']);
    });

    // =====================================================================
    // SUPPLEMENT DATA APIs
    // =====================================================================
    Route::prefix('supplements')->group(function () {
        Route::get('/', [SupplementApiController::class, 'index']);
        Route::get('/search', [SupplementApiController::class, 'search']);
        Route::get('/top-rated', [SupplementApiController::class, 'topRated']);
        Route::get('/by-ids', [SupplementApiController::class, 'byIds']);
        Route::post('/compare', [SupplementApiController::class, 'compare']);
        Route::get('/{id}', [SupplementApiController::class, 'show']);
    });

    // =====================================================================
    // CATEGORY & CONDITION APIs
    // =====================================================================
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryApiController::class, 'index']);
        Route::get('/{slug}', [CategoryApiController::class, 'show']);
        Route::get('/{slug}/supplements', [CategoryApiController::class, 'supplements']);
    });

    Route::prefix('conditions')->group(function () {
        Route::get('/', [CategoryApiController::class, 'conditions']);
        Route::get('/{slug}', [CategoryApiController::class, 'conditionShow']);
    });

    // =====================================================================
    // RECOMMENDATION ENGINE APIs
    // =====================================================================
    Route::prefix('recommend')->group(function () {
        Route::post('/', [RecommendationApiController::class, 'recommend']);
        Route::get('/condition/{slug}', [RecommendationApiController::class, 'forCondition']);
        Route::get('/category/{slug}', [RecommendationApiController::class, 'forCategory']);
    });

    Route::get('/scoring/explain/{id}', [RecommendationApiController::class, 'explainScore']);

    // =====================================================================
    // STATISTICS APIs
    // =====================================================================
    Route::prefix('stats')->group(function () {
        Route::get('/overview', [StatsApiController::class, 'overview']);
        Route::get('/categories', [StatsApiController::class, 'categories']);
        Route::get('/brands', [StatsApiController::class, 'brands']);
        Route::get('/scores', [StatsApiController::class, 'scores']);
        Route::get('/certifications', [StatsApiController::class, 'certifications']);
    });

    // =====================================================================
    // ANALYTICS APIs (Usage & Demand)
    // =====================================================================
    Route::prefix('analytics')->group(function () {
        // Conversation analytics
        Route::get('/conversations', [AnalyticsApiController::class, 'conversations']);
        Route::get('/conversations/summary', [AnalyticsApiController::class, 'conversationsSummary']);
        Route::get('/messages', [AnalyticsApiController::class, 'messages']);

        // Demand analytics
        Route::get('/categories/demand', [AnalyticsApiController::class, 'categoriesDemand']);
        Route::get('/conditions/demand', [AnalyticsApiController::class, 'conditionsDemand']);
        Route::get('/preferences', [AnalyticsApiController::class, 'preferences']);

        // Recommendation analytics
        Route::get('/recommendations/generated', [AnalyticsApiController::class, 'recommendationsGenerated']);
        Route::get('/recommendations/top', [AnalyticsApiController::class, 'recommendationsTop']);

        // Trends
        Route::get('/trends/daily', [AnalyticsApiController::class, 'trendsDaily']);
        Route::get('/trends/weekly', [AnalyticsApiController::class, 'trendsWeekly']);
        Route::get('/trends/hourly', [AnalyticsApiController::class, 'trendsHourly']);

        // Funnel
        Route::get('/funnel', [AnalyticsApiController::class, 'funnel']);

        // Event tracking
        Route::post('/track', [AnalyticsApiController::class, 'track']);
    });

    // =====================================================================
    // EXPORT/SYNC APIs
    // =====================================================================
    Route::prefix('export')->group(function () {
        Route::get('/supplements', [ExportApiController::class, 'supplements']);
        Route::get('/summary', [ExportApiController::class, 'summary']);
        Route::get('/changes', [ExportApiController::class, 'changes']);
        Route::get('/categories', [ExportApiController::class, 'categories']);
        Route::get('/conditions', [ExportApiController::class, 'conditions']);
        Route::get('/full', [ExportApiController::class, 'full']);
    });

    // =====================================================================
    // BROWSE APIs
    // =====================================================================
    Route::prefix('browse')->group(function () {
        Route::get('/functions', [BrowseApiController::class, 'functions']);
        Route::get('/functions/{slug}/categories', [BrowseApiController::class, 'functionCategories']);
        Route::get('/categories/{slug}/supplements', [BrowseApiController::class, 'categorySupplements']);
        Route::get('/categories/{slug}/brands', [BrowseApiController::class, 'categoryBrands']);
        Route::get('/brands', [BrowseApiController::class, 'brands']);
    });

    // =====================================================================
    // LEGACY ENDPOINTS (backward compatibility)
    // =====================================================================
    Route::get('/dashboard/stats', [ChatController::class, 'dashboardStats']);

    // =====================================================================
    // IMPORT ENDPOINT (protected by secret)
    // =====================================================================
    Route::post('/import/run', function (\Illuminate\Http\Request $request) {
        $secret = $request->header('X-Import-Secret') ?? $request->input('secret');
        if ($secret !== config('app.import_secret', 'vita-import-2024')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $rounds = (int) ($request->input('rounds', 20));
        $batch = (int) ($request->input('batch', 25));
        $splitDir = base_path('database/exports/splits');
        $totalTarget = 23853;

        $startCount = \App\Models\Supplement::count();

        if ($startCount >= $totalTarget) {
            return response()->json([
                'status' => 'complete',
                'count' => $startCount,
                'target' => $totalTarget
            ]);
        }

        $imported = 0;
        for ($round = 0; $round < $rounds; $round++) {
            $currentCount = \App\Models\Supplement::count();
            if ($currentCount >= $totalTarget) break;

            $chunkNum = (int) floor($currentCount / 500);
            $posInChunk = $currentCount % 500;
            if ($chunkNum >= 48) break;

            $chunkFile = "{$splitDir}/chunk_" . str_pad($chunkNum, 3, '0', STR_PAD_LEFT) . ".json.gz";
            if (!file_exists($chunkFile)) break;

            $items = json_decode(gzdecode(file_get_contents($chunkFile)), true);
            $batchItems = array_slice($items, $posInChunk, $batch);
            if (empty($batchItems)) continue;

            $clean = [];
            foreach ($batchItems as $item) {
                unset($item['created_at'], $item['updated_at'], $item['id']);
                foreach (['certification_flags', 'allergen_contains_flags', 'allergen_free_from_flags', 'active_ingredients', 'warning_flags'] as $f) {
                    if (isset($item[$f]) && is_array($item[$f])) {
                        $item[$f] = json_encode($item[$f]);
                    }
                }
                $clean[] = $item;
            }

            \Illuminate\Support\Facades\DB::table('supplements')->insert($clean);
            $imported += count($clean);
        }

        $finalCount = \App\Models\Supplement::count();

        return response()->json([
            'status' => $finalCount >= $totalTarget ? 'complete' : 'in_progress',
            'imported' => $imported,
            'count' => $finalCount,
            'target' => $totalTarget,
            'remaining' => $totalTarget - $finalCount
        ]);
    });
});
