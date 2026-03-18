<?php

namespace App\Console\Commands;

use App\Services\AIService;
use Illuminate\Console\Command;

class TestAIConnection extends Command
{
    protected $signature = 'ai:test';
    protected $description = 'Test AI service connection (OpenRouter/Gemini/OpenAI)';

    public function handle(AIService $ai): int
    {
        $this->info('Testing AI connection...');
        $this->newLine();

        $this->line('Provider: ' . $ai->getProvider());
        $this->line('Chat Model: ' . $ai->getModel('chat'));
        $this->line('Recommendation Model: ' . $ai->getModel('recommendation'));
        $this->newLine();

        $this->info('Sending test request...');

        $result = $ai->testConnection();

        if ($result['success']) {
            $this->info('Connection successful!');
            $this->line('Response: ' . $result['response']);
        } else {
            $this->error('Connection failed!');
            $this->error('Error: ' . $result['error']);
            return 1;
        }

        return 0;
    }
}
