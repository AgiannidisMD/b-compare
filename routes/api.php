<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\Api\SupplementApiController;
use App\Http\Controllers\Api\CategoryApiController;
use App\Http\Controllers\Api\RecommendationApiController;
use App\Http\Controllers\Api\StatsApiController;
use App\Http\Controllers\Api\AnalyticsApiController;
use App\Http\Controllers\Api\ExportApiController;

/*
|--------------------------------------------------------------------------
| B-COMPARE API Routes
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
    // LEGACY ENDPOINTS (backward compatibility)
    // =====================================================================
    Route::get('/dashboard/stats', [ChatController::class, 'dashboardStats']);
});
