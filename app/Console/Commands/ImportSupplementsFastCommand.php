<?php

namespace App\Console\Commands;

use App\Models\Supplement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportSupplementsFastCommand extends Command
{
    protected $signature = 'supplements:fast-import {--chunk= : Specific chunk number to import}';
    protected $description = 'Fast import using pre-split JSON files';

    public function handle()
    {
        $splitDir = base_path('database/exports/splits');

        if (!is_dir($splitDir)) {
            $this->error("Split files not found in database/exports/splits/");
            return Command::FAILURE;
        }

        // Support both .json and .json.gz files
        $chunkFiles = glob("{$splitDir}/chunk_*.json.gz");
        if (empty($chunkFiles)) {
            $chunkFiles = glob("{$splitDir}/chunk_*.json");
        }
        sort($chunkFiles, SORT_NATURAL);
        $totalChunks = count($chunkFiles);

        if ($totalChunks === 0) {
            $this->error("No chunk files found");
            return Command::FAILURE;
        }

        $currentCount = Supplement::count();
        $this->info("DB: {$currentCount}/23853 supplements");

        // If specific chunk requested
        if ($this->option('chunk') !== null) {
            $chunkNum = (int) $this->option('chunk');
            $chunkFile = sprintf("{$splitDir}/chunk_%03d.json.gz", $chunkNum);

            if (!File::exists($chunkFile)) {
                // Try without .gz extension
                $chunkFile = sprintf("{$splitDir}/chunk_%03d.json", $chunkNum);
            }

            if (!File::exists($chunkFile)) {
                $this->error("Chunk file not found");
                return Command::FAILURE;
            }

            return $this->importChunk($chunkFile, $chunkNum, $totalChunks);
        }

        // Auto-detect which chunk to import
        $chunksCompleted = (int) floor($currentCount / 500);

        if ($chunksCompleted >= $totalChunks) {
            $this->info("All 48 chunks imported! Total: {$currentCount}");
            return Command::SUCCESS;
        }

        $chunkFile = $chunkFiles[$chunksCompleted];
        return $this->importChunk($chunkFile, $chunksCompleted, $totalChunks);
    }

    private function importChunk(string $chunkFile, int $chunkNum, int $totalChunks): int
    {
        $this->info("Importing chunk {$chunkNum}/{$totalChunks}");

        // Handle both .json and .json.gz files
        if (str_ends_with($chunkFile, '.gz')) {
            $content = gzdecode(file_get_contents($chunkFile));
            $items = json_decode($content, true);
        } else {
            $items = json_decode(file_get_contents($chunkFile), true);
        }

        if (empty($items)) {
            $this->warn("Empty chunk");
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
        }, $items);

        // Insert in very small batches to avoid timeout
        foreach (array_chunk($clean, 25) as $batch) {
            Supplement::insert($batch);
        }

        $newCount = Supplement::count();
        $this->info("Done! Total: {$newCount}/23853");

        $remaining = $totalChunks - $chunkNum - 1;
        if ($remaining > 0) {
            $this->warn("{$remaining} chunks remaining. Run again.");
        } else {
            $this->info("ALL CHUNKS IMPORTED!");
        }

        return Command::SUCCESS;
    }
}
