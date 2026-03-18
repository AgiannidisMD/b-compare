<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    private string $provider;
    private array $config;

    public function __construct()
    {
        $this->provider = config('ai.provider', 'openrouter');
        $this->config = config('ai');
    }

    /**
     * Send a chat completion request
     *
     * @param string $systemPrompt The system instruction
     * @param array $messages Array of ['role' => 'user'|'assistant', 'content' => '...']
     * @param string $type 'chat' or 'recommendation' (determines model and settings)
     * @return array ['success' => bool, 'content' => string, 'error' => string|null]
     */
    public function chat(string $systemPrompt, array $messages, string $type = 'chat'): array
    {
        return match ($this->provider) {
            'openrouter' => $this->chatOpenRouter($systemPrompt, $messages, $type),
            'gemini' => $this->chatGemini($systemPrompt, $messages, $type),
            'openai' => $this->chatOpenAI($systemPrompt, $messages, $type),
            default => $this->chatOpenRouter($systemPrompt, $messages, $type),
        };
    }

    /**
     * OpenRouter API (OpenAI-compatible format)
     * Supports: GPT-4, Claude, Gemini, Llama, Mistral, etc.
     */
    private function chatOpenRouter(string $systemPrompt, array $messages, string $type): array
    {
        $apiKey = $this->config['openrouter']['api_key'] ?? null;

        if (!$apiKey) {
            Log::error('OpenRouter API key not configured');
            return ['success' => false, 'content' => '', 'error' => 'API key not configured'];
        }

        $model = $this->config['models'][$type] ?? 'google/gemini-2.0-flash-exp:free';
        $settings = $this->config['settings'][$type] ?? ['temperature' => 0.4, 'max_tokens' => 1024];
        $timeout = $this->config['timeouts'][$type] ?? 30;

        // Build messages array with system prompt
        $apiMessages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        foreach ($messages as $msg) {
            $apiMessages[] = [
                'role' => $msg['role'] === 'user' ? 'user' : 'assistant',
                'content' => $msg['content'],
            ];
        }

        try {
            $response = Http::timeout($timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                    'HTTP-Referer' => $this->config['openrouter']['site_url'] ?? config('app.url'),
                    'X-Title' => $this->config['openrouter']['site_name'] ?? 'SupplementIQ',
                ])
                ->post($this->config['openrouter']['base_url'] . '/chat/completions', [
                    'model' => $model,
                    'messages' => $apiMessages,
                    'temperature' => $settings['temperature'],
                    'max_tokens' => $settings['max_tokens'],
                    'response_format' => ['type' => 'json_object'],
                ])
                ->json();

            if (isset($response['choices'][0]['message']['content'])) {
                return [
                    'success' => true,
                    'content' => $response['choices'][0]['message']['content'],
                    'error' => null,
                    'model' => $response['model'] ?? $model,
                    'usage' => $response['usage'] ?? null,
                ];
            }

            // Handle error response
            if (isset($response['error'])) {
                Log::error('OpenRouter API error', ['error' => $response['error']]);
                return [
                    'success' => false,
                    'content' => '',
                    'error' => $response['error']['message'] ?? 'Unknown error',
                ];
            }

            Log::warning('OpenRouter unexpected response format', ['response' => $response]);
            return ['success' => false, 'content' => '', 'error' => 'Unexpected response format'];

        } catch (\Exception $e) {
            Log::error('OpenRouter request failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'content' => '', 'error' => $e->getMessage()];
        }
    }

    /**
     * Google Gemini API (native format)
     */
    private function chatGemini(string $systemPrompt, array $messages, string $type): array
    {
        $apiKey = $this->config['gemini']['api_key'] ?? null;

        if (!$apiKey) {
            Log::error('Gemini API key not configured');
            return ['success' => false, 'content' => '', 'error' => 'API key not configured'];
        }

        $model = $this->config['models'][$type] ?? 'gemini-2.0-flash';
        $settings = $this->config['settings'][$type] ?? ['temperature' => 0.4, 'max_tokens' => 1024];
        $timeout = $this->config['timeouts'][$type] ?? 30;

        // Build Gemini format messages
        $contents = [];
        foreach ($messages as $msg) {
            $contents[] = [
                'role' => $msg['role'] === 'user' ? 'user' : 'model',
                'parts' => [['text' => $msg['content']]],
            ];
        }

        try {
            $response = Http::timeout($timeout)
                ->withHeaders([
                    'x-goog-api-key' => $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->config['gemini']['base_url'] . "/models/{$model}:generateContent", [
                    'systemInstruction' => [
                        'parts' => [['text' => $systemPrompt]],
                    ],
                    'contents' => $contents,
                    'generationConfig' => [
                        'temperature' => $settings['temperature'],
                        'maxOutputTokens' => $settings['max_tokens'],
                        'responseMimeType' => 'application/json',
                    ],
                ])
                ->json();

            if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
                return [
                    'success' => true,
                    'content' => $response['candidates'][0]['content']['parts'][0]['text'],
                    'error' => null,
                    'model' => $model,
                ];
            }

            Log::warning('Gemini unexpected response format', ['response' => $response]);
            return ['success' => false, 'content' => '', 'error' => 'Unexpected response format'];

        } catch (\Exception $e) {
            Log::error('Gemini request failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'content' => '', 'error' => $e->getMessage()];
        }
    }

    /**
     * OpenAI API (native format)
     */
    private function chatOpenAI(string $systemPrompt, array $messages, string $type): array
    {
        $apiKey = $this->config['openai']['api_key'] ?? null;

        if (!$apiKey) {
            Log::error('OpenAI API key not configured');
            return ['success' => false, 'content' => '', 'error' => 'API key not configured'];
        }

        $model = $this->config['models'][$type] ?? 'gpt-4o-mini';
        $settings = $this->config['settings'][$type] ?? ['temperature' => 0.4, 'max_tokens' => 1024];
        $timeout = $this->config['timeouts'][$type] ?? 30;

        // Build messages array
        $apiMessages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        foreach ($messages as $msg) {
            $apiMessages[] = [
                'role' => $msg['role'] === 'user' ? 'user' : 'assistant',
                'content' => $msg['content'],
            ];
        }

        try {
            $response = Http::timeout($timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->config['openai']['base_url'] . '/chat/completions', [
                    'model' => $model,
                    'messages' => $apiMessages,
                    'temperature' => $settings['temperature'],
                    'max_tokens' => $settings['max_tokens'],
                    'response_format' => ['type' => 'json_object'],
                ])
                ->json();

            if (isset($response['choices'][0]['message']['content'])) {
                return [
                    'success' => true,
                    'content' => $response['choices'][0]['message']['content'],
                    'error' => null,
                    'model' => $response['model'] ?? $model,
                    'usage' => $response['usage'] ?? null,
                ];
            }

            Log::warning('OpenAI unexpected response format', ['response' => $response]);
            return ['success' => false, 'content' => '', 'error' => 'Unexpected response format'];

        } catch (\Exception $e) {
            Log::error('OpenAI request failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'content' => '', 'error' => $e->getMessage()];
        }
    }

    /**
     * Get current provider name
     */
    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * Get model for a specific type
     */
    public function getModel(string $type = 'chat'): string
    {
        return $this->config['models'][$type] ?? 'unknown';
    }

    /**
     * Test the API connection
     */
    public function testConnection(): array
    {
        $result = $this->chat(
            'You are a test assistant. Respond with JSON: {"status": "ok", "message": "Connection successful"}',
            [['role' => 'user', 'content' => 'Test connection']],
            'chat'
        );

        return [
            'provider' => $this->provider,
            'model' => $this->getModel('chat'),
            'success' => $result['success'],
            'response' => $result['content'] ?? null,
            'error' => $result['error'] ?? null,
        ];
    }

    /**
     * Stream a chat completion request via SSE
     *
     * @param string $systemPrompt The system instruction
     * @param array $messages Array of ['role' => 'user'|'assistant', 'content' => '...']
     * @param string $type 'chat' or 'recommendation'
     * @return \Generator Yields chunks of the response
     */
    public function streamChat(string $systemPrompt, array $messages, string $type = 'chat'): \Generator
    {
        return match ($this->provider) {
            'openrouter' => $this->streamOpenRouter($systemPrompt, $messages, $type),
            'openai' => $this->streamOpenAI($systemPrompt, $messages, $type),
            default => $this->streamOpenRouter($systemPrompt, $messages, $type),
        };
    }

    /**
     * Stream via OpenRouter (supports streaming for most models)
     */
    private function streamOpenRouter(string $systemPrompt, array $messages, string $type): \Generator
    {
        $apiKey = $this->config['openrouter']['api_key'] ?? null;

        if (!$apiKey) {
            yield ['type' => 'error', 'content' => 'API key not configured'];
            return;
        }

        $model = $this->config['models'][$type] ?? 'google/gemini-2.0-flash-exp:free';
        $settings = $this->config['settings'][$type] ?? ['temperature' => 0.4, 'max_tokens' => 1024];

        // Build messages array with system prompt
        $apiMessages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        foreach ($messages as $msg) {
            $apiMessages[] = [
                'role' => $msg['role'] === 'user' ? 'user' : 'assistant',
                'content' => $msg['content'],
            ];
        }

        try {
            $ch = curl_init();

            curl_setopt_array($ch, [
                CURLOPT_URL => $this->config['openrouter']['base_url'] . '/chat/completions',
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $apiKey,
                    'Content-Type: application/json',
                    'HTTP-Referer: ' . ($this->config['openrouter']['site_url'] ?? config('app.url')),
                    'X-Title: ' . ($this->config['openrouter']['site_name'] ?? 'SupplementIQ'),
                    'Accept: text/event-stream',
                ],
                CURLOPT_POSTFIELDS => json_encode([
                    'model' => $model,
                    'messages' => $apiMessages,
                    'temperature' => $settings['temperature'],
                    'max_tokens' => $settings['max_tokens'],
                    'stream' => true,
                ]),
                CURLOPT_TIMEOUT => 120,
            ]);

            $fullContent = '';
            $buffer = '';

            curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) use (&$fullContent, &$buffer) {
                $buffer .= $data;
                return strlen($data);
            });

            curl_exec($ch);

            if (curl_errno($ch)) {
                yield ['type' => 'error', 'content' => curl_error($ch)];
                curl_close($ch);
                return;
            }

            curl_close($ch);

            // Parse SSE data
            $lines = explode("\n", $buffer);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || !str_starts_with($line, 'data: ')) {
                    continue;
                }

                $data = substr($line, 6);
                if ($data === '[DONE]') {
                    break;
                }

                $json = json_decode($data, true);
                if ($json && isset($json['choices'][0]['delta']['content'])) {
                    $chunk = $json['choices'][0]['delta']['content'];
                    $fullContent .= $chunk;
                    yield ['type' => 'chunk', 'content' => $chunk];
                }
            }

            yield ['type' => 'done', 'content' => $fullContent];

        } catch (\Exception $e) {
            Log::error('OpenRouter stream failed', ['error' => $e->getMessage()]);
            yield ['type' => 'error', 'content' => $e->getMessage()];
        }
    }

    /**
     * Stream via OpenAI (native streaming)
     */
    private function streamOpenAI(string $systemPrompt, array $messages, string $type): \Generator
    {
        $apiKey = $this->config['openai']['api_key'] ?? null;

        if (!$apiKey) {
            yield ['type' => 'error', 'content' => 'API key not configured'];
            return;
        }

        $model = $this->config['models'][$type] ?? 'gpt-4o-mini';
        $settings = $this->config['settings'][$type] ?? ['temperature' => 0.4, 'max_tokens' => 1024];

        $apiMessages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        foreach ($messages as $msg) {
            $apiMessages[] = [
                'role' => $msg['role'] === 'user' ? 'user' : 'assistant',
                'content' => $msg['content'],
            ];
        }

        try {
            $ch = curl_init();

            curl_setopt_array($ch, [
                CURLOPT_URL => $this->config['openai']['base_url'] . '/chat/completions',
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $apiKey,
                    'Content-Type: application/json',
                    'Accept: text/event-stream',
                ],
                CURLOPT_POSTFIELDS => json_encode([
                    'model' => $model,
                    'messages' => $apiMessages,
                    'temperature' => $settings['temperature'],
                    'max_tokens' => $settings['max_tokens'],
                    'stream' => true,
                ]),
                CURLOPT_TIMEOUT => 120,
            ]);

            $fullContent = '';
            $buffer = '';

            curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) use (&$buffer) {
                $buffer .= $data;
                return strlen($data);
            });

            curl_exec($ch);

            if (curl_errno($ch)) {
                yield ['type' => 'error', 'content' => curl_error($ch)];
                curl_close($ch);
                return;
            }

            curl_close($ch);

            // Parse SSE data
            $lines = explode("\n", $buffer);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || !str_starts_with($line, 'data: ')) {
                    continue;
                }

                $data = substr($line, 6);
                if ($data === '[DONE]') {
                    break;
                }

                $json = json_decode($data, true);
                if ($json && isset($json['choices'][0]['delta']['content'])) {
                    $chunk = $json['choices'][0]['delta']['content'];
                    $fullContent .= $chunk;
                    yield ['type' => 'chunk', 'content' => $chunk];
                }
            }

            yield ['type' => 'done', 'content' => $fullContent];

        } catch (\Exception $e) {
            Log::error('OpenAI stream failed', ['error' => $e->getMessage()]);
            yield ['type' => 'error', 'content' => $e->getMessage()];
        }
    }
}
