<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\MedicalCondition;
use App\Models\Supplement;
use App\Models\SupplementCategory;
use App\Services\AIService;
use App\Services\ChatbotService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    private $chatbot;
    private $ai;

    public function __construct(ChatbotService $chatbot, AIService $ai)
    {
        $this->chatbot = $chatbot;
        $this->ai = $ai;
    }

    public function index(Request $request)
    {
        $categories = SupplementCategory::all();

        // Pre-select context from browse page
        $preselectedCategory = null;
        $preselectedSupplement = null;
        $preselectedGoal = null;
        $preselectedBrand = null;

        if ($request->has('category')) {
            $preselectedCategory = SupplementCategory::where('slug', $request->category)->first();
        }
        if ($request->has('supplement')) {
            $preselectedSupplement = Supplement::find($request->supplement);
        }
        if ($request->has('goal')) {
            $preselectedGoal = \App\Models\HealthFunction::where('slug', $request->goal)->first();
        }
        if ($request->has('brand')) {
            $preselectedBrand = $request->brand;
        }

        return view('chat', compact('categories', 'preselectedCategory', 'preselectedSupplement', 'preselectedGoal', 'preselectedBrand'));
    }

    public function sendMessage(Request $request)
    {
        // Increase execution time for AI calls
        set_time_limit(120);

        $request->validate([
            'session_id' => 'required|string',
            'message' => 'required|string',
        ]);

        // Get or create conversation
        $conversation = ChatConversation::firstOrCreate(
            ['session_id' => $request->session_id],
            ['user_ip' => $request->ip()]
        );

        // Process message through chatbot
        $result = $this->chatbot->processMessage($conversation, $request->message);

        return response()->json($result);
    }

    /**
     * Stream AI response via Server-Sent Events (SSE)
     * For real-time typing effect in chat UI
     */
    public function streamMessage(Request $request): StreamedResponse
    {
        $request->validate([
            'session_id' => 'required|string',
            'message' => 'required|string',
        ]);

        // Get or create conversation
        $conversation = ChatConversation::firstOrCreate(
            ['session_id' => $request->session_id],
            ['user_ip' => $request->ip()]
        );

        // Add user message to history
        $history = $conversation->conversation_history ?? [];
        $history[] = ['role' => 'user', 'content' => $request->message];

        // Build full clinical system prompt for streaming
        $systemPrompt = $this->chatbot->buildStreamingPrompt($conversation, $request->message);

        return response()->stream(function () use ($systemPrompt, $history, $conversation) {
            // Disable output buffering
            if (ob_get_level()) {
                ob_end_clean();
            }

            // Send headers for SSE
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            header('X-Accel-Buffering: no'); // Disable nginx buffering

            $fullResponse = '';

            // Stream the AI response
            foreach ($this->ai->streamChat($systemPrompt, $history, 'chat') as $event) {
                if ($event['type'] === 'chunk') {
                    $chunk = $event['content'];
                    $fullResponse .= $chunk;

                    // Send SSE event
                    echo "data: " . json_encode(['type' => 'chunk', 'content' => $chunk]) . "\n\n";
                    flush();
                } elseif ($event['type'] === 'done') {
                    // Save conversation with full response
                    $history[] = ['role' => 'assistant', 'content' => $fullResponse];
                    $conversation->update([
                        'conversation_history' => $history,
                    ]);

                    echo "data: " . json_encode(['type' => 'done', 'content' => $fullResponse]) . "\n\n";
                    flush();
                } elseif ($event['type'] === 'error') {
                    echo "data: " . json_encode(['type' => 'error', 'content' => $event['content']]) . "\n\n";
                    flush();
                }
            }

            echo "data: [DONE]\n\n";
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function getCategories()
    {
        return response()->json(
            SupplementCategory::orderBy('product_count', 'desc')->get()
        );
    }

    public function selectCategory(Request $request)
    {
        // Increase execution time for AI calls
        set_time_limit(120);

        $request->validate([
            'session_id' => 'required|string',
            'category_id' => 'required|integer',
        ]);

        // Get or create conversation
        $conversation = ChatConversation::firstOrCreate(
            ['session_id' => $request->session_id],
            ['user_ip' => $request->ip()]
        );

        // Get category info
        $category = SupplementCategory::findOrFail($request->category_id);

        // Update conversation with selected category (reset state)
        $conversation->update([
            'selected_category_id' => $request->category_id,
            'conversation_history' => [],
            'extracted_conditions' => [],
            'extracted_preferences' => [],
            'is_ready_to_recommend' => false,
        ]);

        // Use AI to generate contextual greeting
        $result = $this->chatbot->processMessage(
            $conversation,
            "Επέλεξα την κατηγορία {$category->name}"
        );

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'product_count' => $category->product_count,
            ]
        ]);
    }

    /**
     * Search and filter supplements
     */
    public function searchSupplements(Request $request)
    {
        $query = Supplement::query();

        // Category filter
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Search by brand or title
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('brand', 'LIKE', "%{$search}%")
                  ->orWhere('title', 'LIKE', "%{$search}%");
            });
        }

        // Price range filter
        if ($request->has('min_price')) {
            $query->where('current_price', '>=', $request->min_price);
        }
        if ($request->has('max_price')) {
            $query->where('current_price', '<=', $request->max_price);
        }

        // Minimum rating filter
        if ($request->has('min_rating')) {
            $query->where('rating', '>=', $request->min_rating);
        }

        // Minimum overall score filter
        if ($request->has('min_score')) {
            $query->where('overall_recommendation_score', '>=', $request->min_score);
        }

        // Sort options
        $sortField = $request->get('sort', 'overall_recommendation_score');
        $sortDir = $request->get('order', 'desc');
        $allowedSorts = ['overall_recommendation_score', 'efficacy_score', 'quality_score', 'value_score', 'bioavailability_score', 'current_price', 'rating'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDir);
        }

        // Pagination
        $perPage = min($request->get('per_page', 20), 100);
        $supplements = $query->paginate($perPage);

        // Transform for API
        $supplements->getCollection()->transform(function ($s) {
            return [
                'id' => $s->id,
                'brand' => $s->brand,
                'title' => $s->title,
                'price' => $s->current_price,
                'rating' => $s->rating,
                'review_count' => $s->review_count,
                'overall_score' => $s->overall_recommendation_score,
                'efficacy_score' => $s->efficacy_score,
                'quality_score' => $s->quality_score,
                'value_score' => $s->value_score,
                'bioavailability_score' => $s->bioavailability_score,
                'dosage_form' => $s->dosage_form,
                'category_id' => $s->category_id,
                'image_url' => $s->image_url,
                'product_url' => $s->product_url,
            ];
        });

        return response()->json($supplements);
    }

    /**
     * Get top supplements for a category
     */
    public function topSupplements(Request $request)
    {
        $categoryId = $request->get('category_id');
        $limit = min($request->get('limit', 10), 50);

        $query = Supplement::whereNotNull('overall_recommendation_score');

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $supplements = $query->orderBy('overall_recommendation_score', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'brand' => $s->brand,
                    'title' => $s->title,
                    'price' => $s->current_price,
                    'rating' => $s->rating,
                    'overall_score' => $s->overall_recommendation_score,
                    'efficacy_score' => $s->efficacy_score,
                    'quality_score' => $s->quality_score,
                    'value_score' => $s->value_score,
                    'bioavailability_score' => $s->bioavailability_score,
                    'image_url' => $s->image_url,
                ];
            });

        return response()->json($supplements);
    }

    /**
     * Get supplement details
     */
    public function supplementDetails($id)
    {
        $supplement = Supplement::findOrFail($id);

        return response()->json([
            'id' => $supplement->id,
            'brand' => $supplement->brand,
            'title' => $supplement->title,
            'description' => $supplement->product_description,
            'price' => $supplement->current_price,
            'rating' => $supplement->rating,
            'review_count' => $supplement->review_count,
            'overall_score' => $supplement->overall_recommendation_score,
            'efficacy_score' => $supplement->efficacy_score,
            'quality_score' => $supplement->quality_score,
            'value_score' => $supplement->value_score,
            'bioavailability_score' => $supplement->bioavailability_score,
            'dosage_form' => $supplement->dosage_form,
            'serving_size_value' => $supplement->serving_size_value,
            'serving_size_unit' => $supplement->serving_size_unit,
            'servings_per_container' => $supplement->servings_per_container,
            'cost_per_serving' => $supplement->cost_per_serving,
            'active_ingredients' => $supplement->active_ingredients,
            'certification_flags' => $supplement->certification_flags,
            'allergen_contains_flags' => $supplement->allergen_contains_flags,
            'allergen_free_from_flags' => $supplement->allergen_free_from_flags,
            'suggested_use' => $supplement->suggested_use,
            'warnings' => $supplement->warnings,
            'image_url' => $supplement->image_url,
            'product_url' => $supplement->product_url,
            'category' => $supplement->category ? [
                'id' => $supplement->category->id,
                'name' => $supplement->category->name,
                'icon' => $supplement->category->icon,
            ] : null,
        ]);
    }

    /**
     * Get medical conditions
     */
    public function getConditions()
    {
        return response()->json(
            MedicalCondition::orderBy('name')->get(['id', 'name', 'slug', 'description'])
        );
    }

    /**
     * Get dashboard statistics
     */
    public function dashboardStats()
    {
        return response()->json([
            'total_supplements' => Supplement::count(),
            'total_categories' => SupplementCategory::count(),
            'total_conditions' => MedicalCondition::count(),
            'supplements_with_scores' => Supplement::whereNotNull('overall_recommendation_score')->count(),
            'avg_overall_score' => round(Supplement::whereNotNull('overall_recommendation_score')->avg('overall_recommendation_score'), 2),
            'price_range' => [
                'min' => Supplement::whereNotNull('current_price')->min('current_price'),
                'max' => Supplement::whereNotNull('current_price')->max('current_price'),
                'avg' => round(Supplement::whereNotNull('current_price')->avg('current_price'), 2),
            ],
            'top_categories' => SupplementCategory::orderBy('product_count', 'desc')
                ->limit(5)
                ->get(['name', 'icon', 'product_count']),
            'score_distribution' => [
                'excellent' => Supplement::where('overall_recommendation_score', '>=', 8)->count(),
                'very_good' => Supplement::whereBetween('overall_recommendation_score', [7, 8])->count(),
                'good' => Supplement::whereBetween('overall_recommendation_score', [6, 7])->count(),
                'fair' => Supplement::whereBetween('overall_recommendation_score', [5, 6])->count(),
                'poor' => Supplement::where('overall_recommendation_score', '<', 5)->count(),
            ],
        ]);
    }
}
