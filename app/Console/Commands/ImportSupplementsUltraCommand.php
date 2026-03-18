<?php

namespace App\Console\Commands;

use App\Models\Supplement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportSupplementsUltraCommand extends Command
{
    protected $signature = 'supplements:ultra-import {--batch=25 : Items per batch}';
    protected $description = 'Ultra-fast import - 25 items, minimal overhead';

    public function handle()
    {
        $batchSize = (int) $this->option('batch');
        $splitDir = base_path('database/exports/splits');

        $currentCount = Supplement::count();
        $this->info("Current: {$currentCount}/23853");

        if ($currentCount >= 23853) {
            $this->info("DONE! All supplements imported.");
            return Command::SUCCESS;
        }

        // Calculate position
        $chunkNum = (int) floor($currentCount / 500);
        $posInChunk = $currentCount % 500;

        $chunkFile = "{$splitDir}/chunk_" . str_pad($chunkNum, 3, '0', STR_PAD_LEFT) . ".json.gz";

        if (!file_exists($chunkFile)) {
            $this->error("Missing: {$chunkFile}");
            return Command::FAILURE;
        }

        // Read and decode
        $raw = file_get_contents($chunkFile);
        $json = gzdecode($raw);
        $items = json_decode($json, true);

        // Get batch
        $batch = array_slice($items, $posInChunk, $batchSize);

        if (empty($batch)) {
            $this->info("Chunk {$chunkNum} done, run again for next chunk");
            return Command::SUCCESS;
        }

        // Clean and insert
        $clean = [];
        foreach ($batch as $item) {
            unset($item['created_at'], $item['updated_at'], $item['id']);
            foreach (['certification_flags', 'allergen_contains_flags', 'allergen_free_from_flags', 'active_ingredients', 'warning_flags'] as $f) {
                if (isset($item[$f]) && is_array($item[$f])) {
                    $item[$f] = json_encode($item[$f]);
                }
            }
            // Truncate fields that may contain dirty data exceeding column limits
                $stringLimits = [
                    'serving_size_unit' => 255,
                    'dosage_form' => 255,
                    'currency' => 10,
                    'comparison_group' => 255,
                    'sku' => 255,
                    'brand' => 255,
                ];
                foreach ($stringLimits as $field => $max) {
                    if (isset($item[$field]) && is_string($item[$field]) && mb_strlen($item[$field]) > $max) {
                        $item[$field] = mb_substr($item[$field], 0, $max);
                    }
                }

                $clean[] = $item;
        }

        DB::table('supplements')->insert($clean);

        $newCount = $currentCount + count($clean);
        $this->info("Imported {$batchSize}. Now: {$newCount}/23853");

        return Command::SUCCESS;
    }
}
