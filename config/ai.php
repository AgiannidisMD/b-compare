<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Provider Configuration
    |--------------------------------------------------------------------------
    |
    | Supported providers: 'openrouter', 'gemini', 'openai'
    | OpenRouter gives access to: GPT-4, Claude, Gemini, Llama, Mistral, etc.
    |
    */

    'provider' => env('AI_PROVIDER', 'openrouter'),

    /*
    |--------------------------------------------------------------------------
    | API Keys
    |--------------------------------------------------------------------------
    */

    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),
        'base_url' => 'https://openrouter.ai/api/v1',
        'site_url' => env('APP_URL', 'http://localhost'), // For OpenRouter rankings
        'site_name' => env('APP_NAME', 'SupplementIQ'),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'base_url' => 'https://api.openai.com/v1',
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Configuration
    |--------------------------------------------------------------------------
    |
    | OpenRouter model IDs (examples):
    | - google/gemini-2.0-flash-exp:free (FREE)
    | - google/gemini-flash-1.5 (cheap)
    | - anthropic/claude-3.5-sonnet (best quality)
    | - openai/gpt-4o-mini (fast & cheap)
    | - openai/gpt-4o (high quality)
    | - meta-llama/llama-3.1-70b-instruct (open source)
    |
    | See full list: https://openrouter.ai/models
    |
    */

    'models' => [
        // Model for conversation/parameter extraction
        'chat' => env('AI_CHAT_MODEL', 'anthropic/claude-sonnet-4'),

        // Model for recommendations (same quality for consistency)
        'recommendation' => env('AI_RECOMMENDATION_MODEL', 'anthropic/claude-sonnet-4'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Generation Settings
    |--------------------------------------------------------------------------
    */

    'settings' => [
        'chat' => [
            'temperature' => 0.4,
            'max_tokens' => 1024,
        ],
        'recommendation' => [
            'temperature' => 0.3,
            'max_tokens' => 2048,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Timeouts (milliseconds)
    |--------------------------------------------------------------------------
    */

    'timeouts' => [
        'chat' => 20,
        'recommendation' => 45,
    ],
];
