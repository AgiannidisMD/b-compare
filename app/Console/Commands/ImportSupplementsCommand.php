<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('supplements:import')]
#[Description('Import supplements from Excel files')]
class ImportSupplementsCommand extends Command
{
    public function handle()
    {
        $this->info('📦 Starting supplement import (All 3 files)...');

        $files = [
            // Already imported (skip but inform)
            [
                'path' => '/Users/apostolisagiannidis/Desktop/Scrapers Data/FINAL_PRODUCTS_OUTPUT/products_final_enriched_codex_smoketest.xlsx',
                'name' => 'CODEX Smoketest (Already imported)'
            ],
            // New files
            [
                'path' => '/Users/apostolisagiannidis/Desktop/Scrapers Data/iherb/iherb22000/B-COMPARE2.xlsx',
                'name' => 'B-COMPARE2 (Greek market - klifes.gr)'
            ],
            [
                'path' => '/Users/apostolisagiannidis/Desktop/Scrapers Data/iherb/iherb22000/B-COMPARE1.xlsx',
                'name' => 'B-COMPARE1 (International - iHerb)'
            ],
            [
                'path' => '/Users/apostolisagiannidis/Desktop/Scrapers Data/glisodin/glisodin_phase2_deep8_2jyY6cblQiSjLcmWT_enriched.xlsx',
                'name' => 'GLISODIN (Specialized brand)'
            ],
        ];

        $totalImported = 0;
        $totalDuplicates = 0;

        try {
            foreach ($files as $file) {
                $this->info("\n📄 Processing: {$file['name']}");
                if (file_exists($file['path'])) {
                    $result = $this->importFromExcel($file['path'], $file['name']);
                    $totalImported += $result['imported'];
                    $totalDuplicates += $result['duplicates'];
                } else {
                    $this->warn("⚠️  File not found: {$file['path']}");
                }
            }

            $this->newLine();
            $this->info('✅ Import complete!');
            $this->info("📊 Total imported: $totalImported");
            $this->info("🔄 Total duplicates skipped: $totalDuplicates");

            $finalCount = \App\Models\Supplement::count();
            $this->info("📦 Total supplements in database: $finalCount");
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }

    private function importFromExcel($filePath, $sourceName)
    {
        if (!file_exists($filePath)) {
            $this->warn("⚠️  File not found: $filePath");
            return ['imported' => 0, 'duplicates' => 0];
        }

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();

        $headers = [];
        foreach ($worksheet->getRowIterator(1, 1) as $headerRow) {
            foreach ($headerRow->getCellIterator() as $cell) {
                $value = $cell->getValue();
                // Handle RichText objects in headers
                if (is_object($value) && method_exists($value, '__toString')) {
                    $value = (string)$value;
                }
                $headers[] = $value;
            }
        }

        $imported = 0;
        $duplicates = 0;
        $errors = 0;

        foreach ($worksheet->getRowIterator(2) as $dataRow) {
            $data = [];
            $col = 0;

            foreach ($dataRow->getCellIterator() as $cell) {
                $value = $cell->getValue();

                // Handle RichText objects
                if (is_object($value) && method_exists($value, '__toString')) {
                    $value = (string)$value;
                }

                $data[$headers[$col] ?? $col] = $value;
                $col++;
            }

            // Skip if no brand/title
            if (empty($data['brand'] ?? null) || empty($data['title'] ?? null)) {
                continue;
            }

            // Check for duplicates using brand + title
            $existing = \App\Models\Supplement::where('brand', trim($data['brand']))
                ->where('title', trim($data['title']))
                ->first();

            if ($existing) {
                $duplicates++;
                continue;
            }

            try {
                \App\Models\Supplement::create([
                    'pid' => $data['pid'] ?? null,
                    'sku' => $data['sku'] ?? null,
                    'brand' => trim($data['brand']),
                    'title' => trim($data['title']),
                    'product_url' => $data['product_url'] ?? null,
                    'image_url' => $data['image_url'] ?? null,
                    'current_price' => is_numeric($data['current_price'] ?? null) ? (float)$data['current_price'] : null,
                    'rating' => is_numeric($data['rating'] ?? null) ? (float)$data['rating'] : null,
                    'review_count' => is_numeric($data['review_count'] ?? null) ? (int)$data['review_count'] : 0,
                    'comparison_group' => $data['comparison_group'] ?? null,
                    'product_description' => $data['product_description'] ?? null,
                    'dosage_form' => $data['dosage_form'] ?? 'unknown',
                    'serving_size_value' => is_numeric($data['serving_size_value'] ?? null) ? (float)$data['serving_size_value'] : null,
                    'serving_size_unit' => isset($data['serving_size_unit']) ? mb_substr(trim($data['serving_size_unit']), 0, 50) : null,
                    'servings_per_container' => is_numeric($data['servings_per_container'] ?? null) ? (float)$data['servings_per_container'] : null,
                    'cost_per_serving' => is_numeric($data['cost_per_serving'] ?? null) ? (float)$data['cost_per_serving'] : null,
                    'cost_per_day' => is_numeric($data['cost_per_day'] ?? null) ? (float)$data['cost_per_day'] : null,
                    'days_of_supply' => is_numeric($data['days_of_supply'] ?? null) ? (int)$data['days_of_supply'] : null,
                    'daily_dose_recommended' => $data['daily_dose_recommended'] ?? null,
                    'certification_flags' => isset($data['certification_flags']) ? (is_string($data['certification_flags']) ? json_decode($data['certification_flags'], true) : $data['certification_flags']) : null,
                    'active_ingredients' => isset($data['active_ingredients_json']) ? (is_string($data['active_ingredients_json']) ? json_decode($data['active_ingredients_json'], true) : $data['active_ingredients_json']) : null,
                    'extraction_confidence' => is_numeric($data['extraction_confidence'] ?? null) ? (float)$data['extraction_confidence'] : null,
                ]);

                $imported++;
                if ($imported % 500 === 0) {
                    $this->line("  ✓ $imported imported from $sourceName...");
                }
            } catch (\Exception $e) {
                $errors++;
            }
        }

        $this->info("  ✅ $imported new | ⚠️  $duplicates duplicates | ❌ $errors errors");

        return ['imported' => $imported, 'duplicates' => $duplicates];
    }
}
