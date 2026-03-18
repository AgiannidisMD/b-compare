<?php

namespace App\Console\Commands;

use App\Models\Supplement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportSupplementsJsonCommand extends Command
{
    protected $signature = 'supplements:import-json {--path=} {--force : Skip confirmation} {--chunk=500 : Chunk size} {--offset=0 : Start from offset}';
    protected $description = 'Import supplements from JSON export (for production)';

    public function handle()
    {
        // Increase memory limit
        ini_set('memory_limit', '512M');

        $path = $this->option('path');
        $offset = (int) $this->option('offset');

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

        // Handle gzipped files - decompress to temp file for streaming
        if (str_ends_with($path, '.gz')) {
            $this->info('Decompressing...');
            $tempPath = storage_path('app/supplements_temp.json');
            $gz = gzopen($path, 'rb');
            $out = fopen($tempPath, 'wb');
            while (!gzeof($gz)) {
                fwrite($out, gzread($gz, 4096));
            }
            gzclose($gz);
            fclose($out);
            $path = $tempPath;
            $this->info('Decompressed to temp file.');
        }

        // Count total (quick scan)
        $content = file_get_contents($path);
        $total = substr_count($content, '"id":');
        unset($content); // Free memory

        $this->info("Found approximately {$total} supplements");

        if (!$this->option('force') && !$this->confirm('Continue with import?', true)) {
            return Command::SUCCESS;
        }

        // Setup database
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }

        // Only truncate if starting from beginning
        if ($offset === 0) {
            Supplement::truncate();
            $this->info('Truncated existing supplements.');
        }

        // Parse JSON array manually to save memory
        $this->info('Parsing and importing...');

        $handle = fopen($path, 'r');
        $chunkSize = (int) $this->option('chunk');
        $buffer = '';
        $items = [];
        $imported = 0;
        $skipped = 0;
        $inObject = 0;
        $currentObject = '';

        while (!feof($handle)) {
            $buffer .= fread($handle, 8192);

            for ($i = 0; $i < strlen($buffer); $i++) {
                $char = $buffer[$i];

                if ($char === '{') {
                    $inObject++;
                    $currentObject .= $char;
                } elseif ($char === '}') {
                    $currentObject .= $char;
                    $inObject--;

                    if ($inObject === 0 && !empty($currentObject)) {
                        // Skip items before offset
                        if ($skipped < $offset) {
                            $skipped++;
                            $currentObject = '';
                            continue;
                        }

                        $item = json_decode($currentObject, true);
                        if ($item && isset($item['id'])) {
                            unset($item['created_at'], $item['updated_at']);

                            // Encode JSON fields
                            foreach (['certification_flags', 'allergen_contains_flags', 'allergen_free_from_flags', 'active_ingredients', 'warning_flags'] as $jsonField) {
                                if (isset($item[$jsonField]) && is_array($item[$jsonField])) {
                                    $item[$jsonField] = json_encode($item[$jsonField]);
                                }
                            }

                            $items[] = $item;
                        }
                        $currentObject = '';

                        // Insert when chunk is full
                        if (count($items) >= $chunkSize) {
                            Supplement::insert($items);
                            $imported += count($items);
                            $this->info("Imported {$imported} supplements...");
                            $items = [];

                            // Free memory
                            gc_collect_cycles();
                        }
                    }
                } elseif ($inObject > 0) {
                    $currentObject .= $char;
                }
            }

            $buffer = '';
        }

        fclose($handle);

        // Insert remaining items
        if (!empty($items)) {
            Supplement::insert($items);
            $imported += count($items);
        }

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        // Clean up temp file
        if (isset($tempPath) && File::exists($tempPath)) {
            File::delete($tempPath);
        }

        $this->info("Successfully imported {$imported} supplements!");

        return Command::SUCCESS;
    }
}
