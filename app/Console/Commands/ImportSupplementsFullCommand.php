<?php

namespace App\Console\Commands;

use App\Models\Supplement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportSupplementsFullCommand extends Command
{
    protected $signature = 'supplements:full-import {--fresh : Truncate table before importing}';
    protected $description = 'Import ALL supplements from all chunks in one go';

    public function handle()
    {
        $splitDir = base_path('database/exports/splits');
        $totalTarget = 23853;

        if ($this->option('fresh')) {
            $this->warn('Clearing supplements table...');
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('supplements')->truncate();
            DB::table('analytics_events')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $existing = Supplement::count();
        $this->info("Current count: {$existing}/{$totalTarget}");

        if ($existing >= $totalTarget) {
            $this->info('All supplements already imported!');
            return Command::SUCCESS;
        }

        // String field limits to prevent "Data too long" errors
        $stringLimits = [
            'serving_size_unit' => 255,
            'dosage_form' => 255,
            'currency' => 10,
            'comparison_group' => 255,
            'sku' => 255,
            'brand' => 255,
        ];

        $imported = 0;
        $skipped = 0;
        $batchSize = 100;

        for ($chunkNum = 0; $chunkNum < 48; $chunkNum++) {
            $chunkFile = "{$splitDir}/chunk_" . str_pad($chunkNum, 3, '0', STR_PAD_LEFT) . ".json.gz";

            if (!file_exists($chunkFile)) {
                $this->warn("Missing chunk {$chunkNum}, skipping");
                continue;
            }

            $items = json_decode(gzdecode(file_get_contents($chunkFile)), true);

            if (empty($items)) {
                continue;
            }

            // Skip chunks we've already imported
            $chunkStart = $chunkNum * 500;
            if ($chunkStart + count($items) <= $existing) {
                continue;
            }

            // If partially imported, skip already-done items
            $offset = max(0, $existing - $chunkStart);
            $items = array_slice($items, $offset);

            // Process in batches
            foreach (array_chunk($items, $batchSize) as $batch) {
                $clean = [];
                foreach ($batch as $item) {
                    unset($item['created_at'], $item['updated_at'], $item['id']);

                    // Encode array fields to JSON
                    foreach (['certification_flags', 'allergen_contains_flags', 'allergen_free_from_flags', 'active_ingredients', 'warning_flags'] as $f) {
                        if (isset($item[$f]) && is_array($item[$f])) {
                            $item[$f] = json_encode($item[$f]);
                        }
                    }

                    // Truncate string fields to prevent overflow
                    foreach ($stringLimits as $field => $max) {
                        if (isset($item[$field]) && is_string($item[$field]) && mb_strlen($item[$field]) > $max) {
                            $item[$field] = mb_substr($item[$field], 0, $max);
                        }
                    }

                    $clean[] = $item;
                }

                try {
                    DB::table('supplements')->insert($clean);
                    $imported += count($clean);
                } catch (\Exception $e) {
                    // Fall back to one-by-one insert to skip bad rows
                    foreach ($clean as $row) {
                        try {
                            DB::table('supplements')->insert($row);
                            $imported++;
                        } catch (\Exception $e2) {
                            $skipped++;
                            $this->warn("Skipped row (pid: " . ($row['pid'] ?? '?') . "): " . mb_substr($e2->getMessage(), 0, 100));
                        }
                    }
                }
            }

            $total = Supplement::count();
            $this->info("Chunk {$chunkNum} done — Total: {$total}/{$totalTarget}");
        }

        $finalCount = Supplement::count();
        $this->info("DONE! Imported: {$imported}, Skipped: {$skipped}, Total: {$finalCount}/{$totalTarget}");

        return Command::SUCCESS;
    }
}
