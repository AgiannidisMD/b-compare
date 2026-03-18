<?php

namespace App\Console\Commands;

use App\Models\Supplement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportSupplementsJsonCommand extends Command
{
    protected $signature = 'supplements:import-json {--path=} {--force : Skip confirmation} {--chunk=1000 : Chunk size}';
    protected $description = 'Import supplements from JSON export (for production)';

    public function handle()
    {
        $path = $this->option('path');

        // Auto-detect file path
        if (!$path) {
            $gzPath = base_path('database/exports/supplements.json.gz');
            $jsonPath = base_path('database/exports/supplements.json');

            if (File::exists($gzPath)) {
                $path = $gzPath;
            } elseif (File::exists($jsonPath)) {
                $path = $jsonPath;
            } else {
                $this->error("No export file found in database/exports/");
                return Command::FAILURE;
            }
        }

        if (!File::exists($path)) {
            $this->error("File not found: {$path}");
            return Command::FAILURE;
        }

        $this->info("Reading: {$path}");

        // Handle gzipped files
        if (str_ends_with($path, '.gz')) {
            $this->info('Decompressing gzipped file...');
            $json = gzdecode(File::get($path));
        } else {
            $json = File::get($path);
        }

        $supplements = json_decode($json, true);

        if (!$supplements) {
            $this->error('Invalid JSON file');
            return Command::FAILURE;
        }

        $total = count($supplements);
        $this->info("Found {$total} supplements to import");

        if ($this->option('force') || $this->confirm('This will replace all existing supplements. Continue?', true)) {

            // Check database driver for syntax differences
            $driver = DB::getDriverName();

            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
            }

            // Truncate existing data
            Supplement::truncate();
            $this->info('Truncated existing supplements.');

            // Import in larger chunks for speed
            $chunkSize = (int) $this->option('chunk');
            $chunks = array_chunk($supplements, $chunkSize);
            $this->info("Importing in chunks of {$chunkSize}...");

            $imported = 0;
            foreach ($chunks as $index => $chunk) {
                // Clean data for import
                $cleanChunk = array_map(function($item) {
                    // Remove auto-managed fields
                    unset($item['created_at'], $item['updated_at']);

                    // Convert JSON arrays stored as strings
                    foreach (['certification_flags', 'allergen_contains_flags', 'allergen_free_from_flags', 'active_ingredients', 'warning_flags'] as $jsonField) {
                        if (isset($item[$jsonField]) && is_string($item[$jsonField])) {
                            continue;
                        }
                        if (isset($item[$jsonField]) && is_array($item[$jsonField])) {
                            $item[$jsonField] = json_encode($item[$jsonField]);
                        }
                    }

                    return $item;
                }, $chunk);

                Supplement::insert($cleanChunk);
                $imported += count($chunk);

                // Simple progress output (no progress bar to avoid socket issues)
                if (($index + 1) % 5 === 0 || $imported === $total) {
                    $this->info("Imported {$imported}/{$total} supplements...");
                }
            }

            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }

            $this->info("Successfully imported {$total} supplements!");
        }

        return Command::SUCCESS;
    }
}
