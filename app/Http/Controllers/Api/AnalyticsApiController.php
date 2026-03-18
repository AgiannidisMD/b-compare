<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsEvent;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\DailyStat;
use App\Models\RecommendationEvent;
use App\Models\Supplement;
use App\Models\SupplementCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsApiController extends Controller
{
    /**
     * GET /api/analytics/conversations
     * Conversation statistics
     */
    public function conversations(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        $startDate = Carbon::now()->subDays($days);

        $total = ChatConversation::count();
        $recent = ChatConversation::where('created_at', '>=', $startDate)->count();

        // Daily breakdown
        $daily = ChatConversation::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Completion rate (conversations that got recommendations)
        $completed = ChatConversation::where('is_ready_to_recommend', true)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'recent' => $recent,
                'period_days' => $days,
                'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
                'daily' => $daily,
            ],
        ]);
    }

    /**
     * GET /api/analytics/conversations/summary
     * Conversation summary stats
     */
    public function conversationsSummary(): JsonResponse
    {
        $total = ChatConversation::count();
        $completed = ChatConversation::where('is_ready_to_recommend', true)->count();
        $messagesTotal = ChatMessage::count();

        // Average messages per conversation
        $avgMessages = $total > 0 ? round($messagesTotal / $total, 1) : 0;

        // Conversations by category
        $byCategory = ChatConversation::whereNotNull('selected_category_id')
            ->selectRaw('selected_category_id, COUNT(*) as count')
            ->groupBy('selected_category_id')
            ->with('category:id,name,slug')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'category_id' => $item->selected_category_id,
                    'category_name' => $item->category?->name ?? 'Unknown',
                    'count' => $item->count,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'total_conversations' => $total,
                'completed_conversations' => $completed,
                'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
                'total_messages' => $messagesTotal,
                'avg_messages_per_conversation' => $avgMessages,
                'by_category' => $byCategory,
            ],
        ]);
    }

    /**
     * GET /api/analytics/messages
     * Message statistics
     */
    public function messages(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        $startDate = Carbon::now()->subDays($days);

        $total = ChatMessage::count();
        $recent = ChatMessage::where('created_at', '>=', $startDate)->count();

        $byRole = ChatMessage::selectRaw('role, COUNT(*) as count')
            ->groupBy('role')
            ->pluck('count', 'role')
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'recent' => $recent,
                'period_days' => $days,
                'by_role' => $byRole,
                'user_to_bot_ratio' => isset($byRole['assistant']) && $byRole['assistant'] > 0
                    ? round(($byRole['user'] ?? 0) / $byRole['assistant'], 2)
                    : 0,
            ],
        ]);
    }

    /**
     * GET /api/analytics/categories/demand
     * Most requested categories
     */
    public function categoriesDemand(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        $startDate = Carbon::now()->subDays($days);

        // From conversations
        $fromConversations = ChatConversation::whereNotNull('selected_category_id')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('selected_category_id, COUNT(*) as count')
            ->groupBy('selected_category_id')
            ->orderByDesc('count')
            ->get();

        // From recommendation events
        $fromRecommendations = RecommendationEvent::whereNotNull('category_id')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('category_id, COUNT(*) as count')
            ->groupBy('category_id')
            ->orderByDesc('count')
            ->get();

        // Merge and get category details
        $categories = SupplementCategory::all()->keyBy('id');

        $demand = $fromConversations->map(function ($item) use ($categories) {
            $category = $categories->get($item->selected_category_id);
            return [
                'category_id' => $item->selected_category_id,
                'category_name' => $category?->name ?? 'Unknown',
                'category_slug' => $category?->slug ?? 'unknown',
                'conversations_count' => $item->count,
            ];
        })->sortByDesc('conversations_count')->values();

        return response()->json([
            'success' => true,
            'data' => $demand,
            'meta' => [
                'period_days' => $days,
                'total_category_selections' => $fromConversations->sum('count'),
            ],
        ]);
    }

    /**
     * GET /api/analytics/conditions/demand
     * Most common conditions mentioned
     */
    public function conditionsDemand(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        $startDate = Carbon::now()->subDays($days);

        // Extract conditions from conversations
        $conversations = ChatConversation::whereNotNull('extracted_conditions')
            ->where('created_at', '>=', $startDate)
            ->get(['extracted_conditions']);

        $conditionCounts = [];
        foreach ($conversations as $conv) {
            if (is_array($conv->extracted_conditions)) {
                foreach ($conv->extracted_conditions as $condition) {
                    $condName = is_array($condition) ? ($condition['name'] ?? $condition) : $condition;
                    $condName = strtolower(trim($condName));
                    if (!isset($conditionCounts[$condName])) {
                        $conditionCounts[$condName] = 0;
                    }
                    $conditionCounts[$condName]++;
                }
            }
        }

        arsort($conditionCounts);

        return response()->json([
            'success' => true,
            'data' => array_slice($conditionCounts, 0, 20, true),
            'meta' => [
                'period_days' => $days,
                'total_condition_mentions' => array_sum($conditionCounts),
            ],
        ]);
    }

    /**
     * GET /api/analytics/preferences
     * Common preferences
     */
    public function preferences(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        $startDate = Carbon::now()->subDays($days);

        $conversations = ChatConversation::whereNotNull('extracted_preferences')
            ->where('created_at', '>=', $startDate)
            ->get(['extracted_preferences']);

        $preferenceCounts = [];
        foreach ($conversations as $conv) {
            if (is_array($conv->extracted_preferences)) {
                foreach ($conv->extracted_preferences as $key => $value) {
                    if ($value === true || $value === 'true' || $value === 1) {
                        if (!isset($preferenceCounts[$key])) {
                            $preferenceCounts[$key] = 0;
                        }
                        $preferenceCounts[$key]++;
                    }
                }
            }
        }

        arsort($preferenceCounts);

        return response()->json([
            'success' => true,
            'data' => $preferenceCounts,
            'meta' => [
                'period_days' => $days,
            ],
        ]);
    }

    /**
     * GET /api/analytics/recommendations/generated
     * Recommendation generation stats
     */
    public function recommendationsGenerated(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        $startDate = Carbon::now()->subDays($days);

        $total = RecommendationEvent::count();
        $recent = RecommendationEvent::where('created_at', '>=', $startDate)->count();

        // Daily breakdown
        $daily = RecommendationEvent::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(supplements_count) as supplements_recommended')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date')
            ->toArray();

        // By category
        $byCategory = RecommendationEvent::whereNotNull('category_id')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('category_id, COUNT(*) as count')
            ->groupBy('category_id')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $categories = SupplementCategory::all()->keyBy('id');
        $byCategory = $byCategory->map(function ($item) use ($categories) {
            return [
                'category' => $categories->get($item->category_id)?->name ?? 'Unknown',
                'count' => $item->count,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'recent' => $recent,
                'period_days' => $days,
                'daily' => $daily,
                'by_category' => $byCategory,
            ],
        ]);
    }

    /**
     * GET /api/analytics/recommendations/top
     * Most recommended supplements
     */
    public function recommendationsTop(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        $limit = min($request->get('limit', 20), 50);
        $startDate = Carbon::now()->subDays($days);

        // Count supplement recommendations
        $events = RecommendationEvent::where('created_at', '>=', $startDate)->get(['supplement_ids']);

        $supplementCounts = [];
        foreach ($events as $event) {
            if (is_array($event->supplement_ids)) {
                foreach ($event->supplement_ids as $id) {
                    if (!isset($supplementCounts[$id])) {
                        $supplementCounts[$id] = 0;
                    }
                    $supplementCounts[$id]++;
                }
            }
        }

        arsort($supplementCounts);
        $topIds = array_slice(array_keys($supplementCounts), 0, $limit);

        $supplements = Supplement::whereIn('id', $topIds)
            ->with('category')
            ->get()
            ->keyBy('id');

        $topRecommended = [];
        foreach ($topIds as $id) {
            $supplement = $supplements->get($id);
            if ($supplement) {
                $topRecommended[] = [
                    'id' => $id,
                    'brand' => $supplement->brand,
                    'title' => $supplement->title,
                    'category' => $supplement->category?->name,
                    'score' => $supplement->overall_recommendation_score,
                    'times_recommended' => $supplementCounts[$id],
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $topRecommended,
            'meta' => [
                'period_days' => $days,
            ],
        ]);
    }

    /**
     * GET /api/analytics/trends/daily
     * Daily trends for last N days
     */
    public function trendsDaily(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        $startDate = Carbon::now()->subDays($days);

        // Get daily stats
        $conversations = ChatConversation::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $recommendations = RecommendationEvent::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $messages = ChatMessage::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Build complete date range
        $trends = [];
        for ($i = $days; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $trends[$date] = [
                'date' => $date,
                'conversations' => $conversations[$date] ?? 0,
                'recommendations' => $recommendations[$date] ?? 0,
                'messages' => $messages[$date] ?? 0,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => array_values($trends),
        ]);
    }

    /**
     * GET /api/analytics/trends/weekly
     * Weekly aggregates
     */
    public function trendsWeekly(Request $request): JsonResponse
    {
        $weeks = $request->get('weeks', 12);
        $startDate = Carbon::now()->subWeeks($weeks);

        $conversations = ChatConversation::where('created_at', '>=', $startDate)
            ->selectRaw('YEARWEEK(created_at, 1) as week, COUNT(*) as count')
            ->groupBy('week')
            ->pluck('count', 'week')
            ->toArray();

        $recommendations = RecommendationEvent::where('created_at', '>=', $startDate)
            ->selectRaw('YEARWEEK(created_at, 1) as week, COUNT(*) as count')
            ->groupBy('week')
            ->pluck('count', 'week')
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'conversations' => $conversations,
                'recommendations' => $recommendations,
            ],
            'meta' => [
                'weeks' => $weeks,
            ],
        ]);
    }

    /**
     * GET /api/analytics/trends/hourly
     * Peak usage hours
     */
    public function trendsHourly(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        $startDate = Carbon::now()->subDays($days);

        $hourly = ChatConversation::where('created_at', '>=', $startDate)
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        // Fill missing hours with 0
        $distribution = [];
        for ($h = 0; $h < 24; $h++) {
            $distribution[$h] = $hourly[$h] ?? 0;
        }

        $peakHour = array_search(max($distribution), $distribution);

        return response()->json([
            'success' => true,
            'data' => [
                'distribution' => $distribution,
                'peak_hour' => $peakHour,
                'peak_count' => $distribution[$peakHour],
            ],
            'meta' => [
                'period_days' => $days,
            ],
        ]);
    }

    /**
     * GET /api/analytics/funnel
     * Conversion funnel
     */
    public function funnel(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        $startDate = Carbon::now()->subDays($days);

        $totalConversations = ChatConversation::where('created_at', '>=', $startDate)->count();
        $categorySelected = ChatConversation::where('created_at', '>=', $startDate)
            ->whereNotNull('selected_category_id')
            ->count();
        $conditionsEntered = ChatConversation::where('created_at', '>=', $startDate)
            ->whereNotNull('extracted_conditions')
            ->whereRaw("JSON_LENGTH(extracted_conditions) > 0")
            ->count();
        $recommendationsShown = ChatConversation::where('created_at', '>=', $startDate)
            ->where('is_ready_to_recommend', true)
            ->count();
        $clickEvents = AnalyticsEvent::where('created_at', '>=', $startDate)
            ->where('event_type', 'supplement_click')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'funnel' => [
                    ['step' => 'Started Conversation', 'count' => $totalConversations, 'rate' => 100],
                    ['step' => 'Category Selected', 'count' => $categorySelected, 'rate' => $totalConversations > 0 ? round(($categorySelected / $totalConversations) * 100, 1) : 0],
                    ['step' => 'Conditions Entered', 'count' => $conditionsEntered, 'rate' => $totalConversations > 0 ? round(($conditionsEntered / $totalConversations) * 100, 1) : 0],
                    ['step' => 'Recommendations Shown', 'count' => $recommendationsShown, 'rate' => $totalConversations > 0 ? round(($recommendationsShown / $totalConversations) * 100, 1) : 0],
                    ['step' => 'Product Clicked', 'count' => $clickEvents, 'rate' => $totalConversations > 0 ? round(($clickEvents / $totalConversations) * 100, 1) : 0],
                ],
            ],
            'meta' => [
                'period_days' => $days,
            ],
        ]);
    }

    /**
     * POST /api/analytics/track
     * Track an event (for click tracking etc)
     */
    public function track(Request $request): JsonResponse
    {
        $event = AnalyticsEvent::create([
            'event_type' => $request->get('event_type'),
            'supplement_id' => $request->get('supplement_id'),
            'category_id' => $request->get('category_id'),
            'condition_id' => $request->get('condition_id'),
            'conversation_id' => $request->get('conversation_id'),
            'session_id' => $request->get('session_id'),
            'user_ip' => $request->ip(),
            'metadata' => $request->get('metadata'),
        ]);

        return response()->json([
            'success' => true,
            'data' => ['event_id' => $event->id],
        ]);
    }
}
