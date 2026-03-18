<?php

namespace App\Console\Commands;

use App\Models\Supplement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportSupplementsBatchCommand extends Command
{
    protected $signature = 'supplements:import-batch {--batch=1 : Batch number (1-8)}';
    protected $description = 'Import supplements in batches (run batch 1-8)';

    public function handle()
    {
        ini_set('memory_limit', '256M');

        $batch = (int) $this->option('batch');
        $batchSize = 3000;
        $offset = ($batch - 1) * $batchSize;

        $path = base_path('database/exports/supplements.json.gz');

        if (!File::exists($path)) {
            $this->error("File not found: {$path}");
            return Command::FAILURE;
        }

        $this->info("Batch {$batch}: Importing records {$offset} to " . ($offset + $batchSize));

        // Decompress
        $tempPath = storage_path("app/batch_{$batch}.json");
        $gz = gzopen($path, 'rb');
        $out = fopen($tempPath, 'wb');
        while (!gzeof($gz)) {
            fwrite($out, gzread($gz, 4096));
        }
        gzclose($gz);
        fclose($out);

        // Read JSON
        $json = file_get_contents($tempPath);
        $all = json_decode($json, true);
        unset($json);

        if (!$all) {
            $this->error('Failed to parse JSON');
            File::delete($tempPath);
            return Command::FAILURE;
        }

        $total = count($all);
        $this->info("Total supplements in file: {$total}");

        // Get batch slice
        $items = array_slice($all, $offset, $batchSize);
        unset($all);

        if (empty($items)) {
            $this->info("No more items to import. Done!");
            File::delete($tempPath);
            return Command::SUCCESS;
        }

        $this->info("Processing " . count($items) . " items...");

        // Truncate only on first batch
        if ($batch === 1) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            Supplement::truncate();
            $this->info('Truncated table.');
        }

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
        }

        File::delete($tempPath);

        $totalInDb = Supplement::count();
        $this->info("Batch {$batch} complete! Imported {$imported} items.");
        $this->info("Total in database: {$totalInDb}");

        if ($offset + $batchSize < $total) {
            $nextBatch = $batch + 1;
            $this->info("Run next: php artisan supplements:import-batch --batch={$nextBatch}");
        } else {
            $this->info("All done! All supplements imported.");
        }

        return Command::SUCCESS;
    }
}
