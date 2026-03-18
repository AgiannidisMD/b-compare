<?php

namespace App\Console\Commands;

use App\Models\Supplement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportSupplementsAutoCommand extends Command
{
    protected $signature = 'supplements:auto-import';
    protected $description = 'Auto-import supplements - run multiple times until complete';

    public function handle()
    {
        ini_set('memory_limit', '256M');
        set_time_limit(55); // Stop before Cloud timeout

        $path = base_path('database/exports/supplements.json.gz');

        if (!File::exists($path)) {
            $this->error("File not found");
            return Command::FAILURE;
        }

        // Check current count
        $currentCount = Supplement::count();
        $this->info("Current supplements in DB: {$currentCount}");

        // Decompress to temp
        $tempPath = storage_path('app/auto_import.json');
        if (!File::exists($tempPath)) {
            $this->info('Decompressing...');
            $gz = gzopen($path, 'rb');
            $out = fopen($tempPath, 'wb');
            while (!gzeof($gz)) {
                fwrite($out, gzread($gz, 4096));
            }
            gzclose($gz);
            fclose($out);
        }

        // Load JSON
        $json = file_get_contents($tempPath);
        $all = json_decode($json, true);
        unset($json);

        $total = count($all);
        $this->info("Total in file: {$total}");

        if ($currentCount >= $total) {
            $this->info("All supplements already imported!");
            File::delete($tempPath);
            return Command::SUCCESS;
        }

        // If starting fresh, truncate
        if ($currentCount === 0) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            Supplement::truncate();
        }

        // Get items to import (skip already imported)
        $items = array_slice($all, $currentCount, 2500); // Import 2500 at a time
        unset($all);

        if (empty($items)) {
            $this->info("Done!");
            return Command::SUCCESS;
        }

        $this->info("Importing " . count($items) . " items starting from offset {$currentCount}...");

        // Import in chunks
        $chunks = array_chunk($items, 500);
        $imported = 0;

        foreach ($chunks as $chunk) {
            $clean = array_map(function($item) {
                unset($item['created_at'], $item['updated_at']);
                foreach (['certification_flags', 'allergen_contains_flags', 'allergen_free_from_flags', 'active_ingredients', 'warning_flags'] as $f) {
                    if (isset($item[$f]) && is_array($item[$f])) {
                        $item[$f] = json_encode($item[$f]);
                    }
                }
                return $item;
            }, $chunk);

            Supplement::insert($clean);
            $imported += count($clean);
            $this->info("  +{$imported}");
        }

        $newCount = Supplement::count();
        $remaining = $total - $newCount;

        $this->info("Imported {$imported}. Total: {$newCount}/{$total}");

        if ($remaining > 0) {
            $this->warn("Run this command again to import remaining {$remaining} supplements.");
        } else {
            $this->info("ALL DONE! All {$total} supplements imported.");
            File::delete($tempPath);
        }

        return Command::SUCCESS;
    }
}
