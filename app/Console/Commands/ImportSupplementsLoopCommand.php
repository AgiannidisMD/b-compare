<?php

namespace App\Console\Commands;

use App\Models\Supplement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportSupplementsLoopCommand extends Command
{
    protected $signature = 'supplements:loop-import {--rounds=10 : Number of rounds} {--batch=25 : Items per batch}';
    protected $description = 'Loop import - runs multiple batches in sequence';

    public function handle()
    {
        $rounds = (int) $this->option('rounds');
        $batchSize = (int) $this->option('batch');
        $splitDir = base_path('database/exports/splits');
        $totalTarget = 23853;

        $startCount = Supplement::count();
        $this->info("Starting: {$startCount}/{$totalTarget}");

        if ($startCount >= $totalTarget) {
            $this->info("DONE!");
            return Command::SUCCESS;
        }

        for ($round = 0; $round < $rounds; $round++) {
            $currentCount = Supplement::count();

            if ($currentCount >= $totalTarget) {
                $this->info("Complete at round {$round}!");
                break;
            }

            $chunkNum = (int) floor($currentCount / 500);
            $posInChunk = $currentCount % 500;

            if ($chunkNum >= 48) {
                $this->info("All chunks processed");
                break;
            }

            $chunkFile = "{$splitDir}/chunk_" . str_pad($chunkNum, 3, '0', STR_PAD_LEFT) . ".json.gz";

            if (!file_exists($chunkFile)) {
                $this->error("Missing: {$chunkFile}");
                break;
            }

            $items = json_decode(gzdecode(file_get_contents($chunkFile)), true);
            $batch = array_slice($items, $posInChunk, $batchSize);

            if (empty($batch)) {
                continue;
            }

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
        }

        $finalCount = Supplement::count();
        $imported = $finalCount - $startCount;
        $remaining = $totalTarget - $finalCount;

        $this->info("Imported: {$imported}. Total: {$finalCount}. Remaining: {$remaining}");

        return Command::SUCCESS;
    }
}
