<?php

namespace App\Console\Commands;

use App\Models\Supplement;
use Illuminate\Console\Command;

class ImportSupplementsMicroCommand extends Command
{
    protected $signature = 'supplements:micro-import {--items=50 : Items per run}';
    protected $description = 'Micro import - 50 items at a time for strict timeouts';

    public function handle()
    {
        $itemsPerRun = (int) $this->option('items');
        $splitDir = base_path('database/exports/splits');

        $chunkFiles = glob("{$splitDir}/chunk_*.json.gz");
        sort($chunkFiles, SORT_NATURAL);

        $currentCount = Supplement::count();
        $this->info("DB: {$currentCount}/23853");

        // Calculate which chunk and position within chunk
        $chunkNum = (int) floor($currentCount / 500);
        $positionInChunk = $currentCount % 500;

        if ($chunkNum >= 48) {
            $this->info("All done!");
            return Command::SUCCESS;
        }

        $chunkFile = $chunkFiles[$chunkNum];
        $content = gzdecode(file_get_contents($chunkFile));
        $items = json_decode($content, true);

        // Get next batch starting from current position
        $batch = array_slice($items, $positionInChunk, $itemsPerRun);

        if (empty($batch)) {
            $this->info("Chunk {$chunkNum} complete, moving to next");
            return Command::SUCCESS;
        }

        $clean = array_map(function($item) {
            unset($item['created_at'], $item['updated_at']);
            foreach (['certification_flags', 'allergen_contains_flags', 'allergen_free_from_flags', 'active_ingredients', 'warning_flags'] as $f) {
                if (isset($item[$f]) && is_array($item[$f])) {
                    $item[$f] = json_encode($item[$f]);
                }
            }
            return $item;
        }, $batch);

        Supplement::insert($clean);

        $newCount = Supplement::count();
        $remaining = 23853 - $newCount;

        $this->info("+{$itemsPerRun}. Total: {$newCount}. Remaining: {$remaining}");

        return Command::SUCCESS;
    }
}
