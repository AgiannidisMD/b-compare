<?php

namespace App\Console\Commands;

use App\Models\Supplement;
use Illuminate\Console\Command;

class TestImportCommand extends Command
{
    protected $signature = 'supplements:test-import {chunk=6}';
    protected $description = 'Test import with minimal batch size';

    public function handle()
    {
        $chunk = $this->argument('chunk');
        $splitDir = base_path('database/exports/splits');
        $chunkFile = sprintf("{$splitDir}/chunk_%03d.json.gz", $chunk);

        $this->info("Testing chunk {$chunk}...");

        if (!file_exists($chunkFile)) {
            $this->error("File not found: {$chunkFile}");
            return Command::FAILURE;
        }

        $this->info("Reading file...");
        $content = gzdecode(file_get_contents($chunkFile));
        $items = json_decode($content, true);
        $this->info("Found " . count($items) . " items");

        // Just insert first 10 items as a test
        $testItems = array_slice($items, 0, 10);

        $this->info("Cleaning 10 items...");
        $clean = array_map(function($item) {
            unset($item['created_at'], $item['updated_at']);
            foreach (['certification_flags', 'allergen_contains_flags', 'allergen_free_from_flags', 'active_ingredients', 'warning_flags'] as $f) {
                if (isset($item[$f]) && is_array($item[$f])) {
                    $item[$f] = json_encode($item[$f]);
                }
            }
            return $item;
        }, $testItems);

        $this->info("Inserting 10 items...");
        Supplement::insert($clean);

        $count = Supplement::count();
        $this->info("Success! DB now has {$count} supplements");

        return Command::SUCCESS;
    }
}
