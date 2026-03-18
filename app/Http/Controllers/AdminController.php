<?php

namespace App\Http\Controllers;

use App\Models\Supplement;
use App\Models\SupplementCategory;
use App\Services\ApifyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    /**
     * Admin dashboard / supplement list
     */
    public function index(Request $request)
    {
        $query = Supplement::with('category');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('brand', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $supplements = $query->orderBy('updated_at', 'desc')->paginate(25);
        $categories = SupplementCategory::orderBy('name')->get();

        return view('admin.index', compact('supplements', 'categories'));
    }

    /**
     * Show form to create new supplement
     */
    public function create()
    {
        $categories = SupplementCategory::orderBy('name')->get();
        return view('admin.create', compact('categories'));
    }

    /**
     * Store new supplement
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'brand' => 'required|string|max:255',
            'title' => 'required|string|max:500',
            'category_id' => 'required|exists:supplement_categories,id',
            'current_price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'product_url' => 'nullable|url|max:2000',
            'image_url' => 'nullable|url|max:2000',
            'rating' => 'nullable|numeric|min:0|max:5',
            'review_count' => 'nullable|integer|min:0',
            'dosage_form' => 'nullable|string|max:50',
            'serving_size_value' => 'nullable|numeric|min:0',
            'serving_size_unit' => 'nullable|string|max:50',
            'servings_per_container' => 'nullable|integer|min:0',
            'product_description' => 'nullable|string',
            'suggested_use' => 'nullable|string',
            'warnings' => 'nullable|string',
            'active_ingredients' => 'nullable|string', // JSON string
            'certification_flags' => 'nullable|string', // JSON string
        ]);

        // Parse JSON fields
        if (!empty($validated['active_ingredients'])) {
            $validated['active_ingredients'] = json_decode($validated['active_ingredients'], true);
        }
        if (!empty($validated['certification_flags'])) {
            $validated['certification_flags'] = json_decode($validated['certification_flags'], true);
        }

        // Generate PID
        $validated['pid'] = 'MANUAL-' . time() . '-' . rand(1000, 9999);

        // Calculate scores
        $supplement = Supplement::create($validated);
        $this->calculateScores($supplement);

        // Update category count
        $this->updateCategoryCount($supplement->category_id);

        return redirect()->route('admin.index')
            ->with('success', "Supplement '{$supplement->brand} - {$supplement->title}' created successfully!");
    }

    /**
     * Show form to edit supplement
     */
    public function edit($id)
    {
        $supplement = Supplement::findOrFail($id);
        $categories = SupplementCategory::orderBy('name')->get();
        return view('admin.edit', compact('supplement', 'categories'));
    }

    /**
     * Update supplement
     */
    public function update(Request $request, $id)
    {
        $supplement = Supplement::findOrFail($id);

        $validated = $request->validate([
            'brand' => 'required|string|max:255',
            'title' => 'required|string|max:500',
            'category_id' => 'required|exists:supplement_categories,id',
            'current_price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'product_url' => 'nullable|url|max:2000',
            'image_url' => 'nullable|url|max:2000',
            'rating' => 'nullable|numeric|min:0|max:5',
            'review_count' => 'nullable|integer|min:0',
            'dosage_form' => 'nullable|string|max:50',
            'serving_size_value' => 'nullable|numeric|min:0',
            'serving_size_unit' => 'nullable|string|max:50',
            'servings_per_container' => 'nullable|integer|min:0',
            'product_description' => 'nullable|string',
            'suggested_use' => 'nullable|string',
            'warnings' => 'nullable|string',
            'active_ingredients' => 'nullable|string',
            'certification_flags' => 'nullable|string',
        ]);

        // Parse JSON fields
        if (!empty($validated['active_ingredients'])) {
            $validated['active_ingredients'] = json_decode($validated['active_ingredients'], true);
        }
        if (!empty($validated['certification_flags'])) {
            $validated['certification_flags'] = json_decode($validated['certification_flags'], true);
        }

        $oldCategoryId = $supplement->category_id;
        $supplement->update($validated);
        $this->calculateScores($supplement);

        // Update category counts if changed
        if ($oldCategoryId != $supplement->category_id) {
            $this->updateCategoryCount($oldCategoryId);
            $this->updateCategoryCount($supplement->category_id);
        }

        return redirect()->route('admin.index')
            ->with('success', "Supplement updated successfully!");
    }

    /**
     * Delete supplement
     */
    public function destroy($id)
    {
        $supplement = Supplement::findOrFail($id);
        $categoryId = $supplement->category_id;
        $name = "{$supplement->brand} - {$supplement->title}";

        $supplement->delete();
        $this->updateCategoryCount($categoryId);

        return redirect()->route('admin.index')
            ->with('success', "Supplement '{$name}' deleted successfully!");
    }

    /**
     * Show CSV import form
     */
    public function importForm()
    {
        $categories = SupplementCategory::orderBy('name')->get();
        return view('admin.import', compact('categories'));
    }

    /**
     * Process CSV import
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
            'default_category_id' => 'nullable|exists:supplement_categories,id',
        ]);

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();

        try {
            if (in_array($extension, ['xlsx', 'xls'])) {
                $data = $this->parseExcel($file);
            } else {
                $data = $this->parseCsv($file);
            }

            $imported = 0;
            $skipped = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($data as $index => $row) {
                $rowNum = $index + 2; // Account for header row

                // Skip empty rows
                if (empty($row['brand']) && empty($row['title'])) {
                    continue;
                }

                // Validate required fields
                if (empty($row['brand']) || empty($row['title'])) {
                    $errors[] = "Row {$rowNum}: Missing brand or title";
                    $skipped++;
                    continue;
                }

                // Find or use default category
                $categoryId = $request->default_category_id;
                if (!empty($row['category'])) {
                    $category = SupplementCategory::where('name', 'like', '%' . $row['category'] . '%')
                        ->orWhere('slug', 'like', '%' . $row['category'] . '%')
                        ->first();
                    if ($category) {
                        $categoryId = $category->id;
                    }
                }

                if (!$categoryId) {
                    $errors[] = "Row {$rowNum}: No category found and no default set";
                    $skipped++;
                    continue;
                }

                // Check for duplicates
                $exists = Supplement::where('brand', $row['brand'])
                    ->where('title', $row['title'])
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                // Create supplement
                $supplement = Supplement::create([
                    'pid' => 'IMPORT-' . time() . '-' . $index,
                    'brand' => $row['brand'],
                    'title' => $row['title'],
                    'category_id' => $categoryId,
                    'current_price' => $row['price'] ?? null,
                    'currency' => $row['currency'] ?? 'EUR',
                    'product_url' => $row['product_url'] ?? $row['url'] ?? null,
                    'image_url' => $row['image_url'] ?? $row['image'] ?? null,
                    'rating' => $row['rating'] ?? null,
                    'review_count' => $row['review_count'] ?? $row['reviews'] ?? null,
                    'dosage_form' => $row['dosage_form'] ?? $row['form'] ?? null,
                    'serving_size_value' => $row['serving_size'] ?? null,
                    'serving_size_unit' => $row['serving_unit'] ?? null,
                    'servings_per_container' => $row['servings'] ?? null,
                    'product_description' => $row['description'] ?? null,
                ]);

                $this->calculateScores($supplement);
                $imported++;
            }

            // Update all category counts
            $this->updateAllCategoryCounts();

            DB::commit();

            $message = "Import completed: {$imported} supplements imported, {$skipped} skipped.";
            if (!empty($errors)) {
                $message .= " Errors: " . implode("; ", array_slice($errors, 0, 5));
                if (count($errors) > 5) {
                    $message .= " and " . (count($errors) - 5) . " more...";
                }
            }

            return redirect()->route('admin.index')->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Import failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Parse CSV file
     */
    private function parseCsv($file): array
    {
        $data = [];
        $handle = fopen($file->getPathname(), 'r');
        $headers = fgetcsv($handle);
        $headers = array_map('strtolower', array_map('trim', $headers));

        while (($row = fgetcsv($handle)) !== false) {
            $data[] = array_combine($headers, array_pad($row, count($headers), null));
        }

        fclose($handle);
        return $data;
    }

    /**
     * Parse Excel file
     */
    private function parseExcel($file): array
    {
        // Simple xlsx parser without external dependencies
        $zip = new \ZipArchive();
        $zip->open($file->getPathname());

        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $sharedStrings = $zip->getFromName('xl/sharedStrings.xml');
        $zip->close();

        // Parse shared strings
        $strings = [];
        if ($sharedStrings) {
            preg_match_all('/<t[^>]*>([^<]*)<\/t>/i', $sharedStrings, $matches);
            $strings = $matches[1] ?? [];
        }

        // Parse worksheet
        preg_match_all('/<row[^>]*>(.*?)<\/row>/is', $xml, $rows);

        $data = [];
        $headers = [];

        foreach ($rows[1] as $rowIndex => $rowXml) {
            preg_match_all('/<c[^>]*(?:t="s")?[^>]*><v>(\d+)<\/v><\/c>|<c[^>]*><v>([^<]+)<\/v><\/c>/i', $rowXml, $cells);

            $rowData = [];
            for ($i = 0; $i < count($cells[0]); $i++) {
                $value = !empty($cells[1][$i]) ? ($strings[(int)$cells[1][$i]] ?? '') : $cells[2][$i];
                $rowData[] = $value;
            }

            if ($rowIndex === 0) {
                $headers = array_map('strtolower', array_map('trim', $rowData));
            } else {
                if (count($rowData) > 0) {
                    $data[] = array_combine(
                        array_slice($headers, 0, count($rowData)),
                        $rowData
                    );
                }
            }
        }

        return $data;
    }

    /**
     * Calculate scores for a supplement
     */
    private function calculateScores(Supplement $supplement): void
    {
        // Efficacy score based on rating and reviews
        $efficacyScore = 5.0;
        if ($supplement->rating) {
            $efficacyScore = ($supplement->rating / 5) * 10;
        }
        if ($supplement->review_count && $supplement->review_count > 100) {
            $efficacyScore = min(10, $efficacyScore + 0.5);
        }

        // Quality score based on certifications
        $qualityScore = $supplement->rating ?? 5.0;

        // Value score based on price vs average
        $valueScore = 7.0;
        if ($supplement->current_price) {
            $avgPrice = Supplement::where('category_id', $supplement->category_id)
                ->whereNotNull('current_price')
                ->avg('current_price') ?? 20;

            if ($supplement->current_price < $avgPrice * 0.7) {
                $valueScore = 9.0;
            } elseif ($supplement->current_price < $avgPrice) {
                $valueScore = 7.5;
            } elseif ($supplement->current_price < $avgPrice * 1.3) {
                $valueScore = 6.0;
            } else {
                $valueScore = 5.0;
            }
        }

        // Bioavailability - default score
        $bioavailabilityScore = 7.0;

        // Formulation score
        $formulationScore = 7.0;

        // Overall score (weighted)
        $overallScore = (
            $efficacyScore * 0.35 +
            $qualityScore * 0.25 +
            $valueScore * 0.20 +
            $bioavailabilityScore * 0.15 +
            $formulationScore * 0.05
        );

        $supplement->update([
            'efficacy_score' => round($efficacyScore, 2),
            'quality_score' => round($qualityScore, 2),
            'value_score' => round($valueScore, 2),
            'bioavailability_score' => round($bioavailabilityScore, 2),
            'formulation_score' => round($formulationScore, 2),
            'overall_recommendation_score' => round($overallScore, 2),
        ]);
    }

    /**
     * Update category product count
     */
    private function updateCategoryCount($categoryId): void
    {
        $count = Supplement::where('category_id', $categoryId)->count();
        SupplementCategory::where('id', $categoryId)->update(['product_count' => $count]);
    }

    /**
     * Update all category counts
     */
    private function updateAllCategoryCounts(): void
    {
        $categories = SupplementCategory::all();
        foreach ($categories as $category) {
            $this->updateCategoryCount($category->id);
        }
    }

    /**
     * Show scraper integration form
     */
    public function scraperForm()
    {
        $categories = SupplementCategory::orderBy('name')->get();
        return view('admin.scraper', compact('categories'));
    }

    /**
     * Import from scraper JSON output
     */
    public function importJson(Request $request)
    {
        $request->validate([
            'json_file' => 'required|file|mimes:json|max:51200', // 50MB max
            'default_category_id' => 'nullable|exists:supplement_categories,id',
            'source_name' => 'nullable|string|max:100',
        ]);

        $file = $request->file('json_file');
        $sourceName = $request->source_name ?? 'scraper';

        try {
            $content = file_get_contents($file->getPathname());
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->with('error', 'Invalid JSON file: ' . json_last_error_msg());
            }

            // Handle both array and object formats
            if (isset($data['data'])) {
                $items = $data['data'];
            } elseif (isset($data['items'])) {
                $items = $data['items'];
            } elseif (is_array($data) && isset($data[0])) {
                $items = $data;
            } else {
                return back()->with('error', 'Could not find items in JSON. Expected array or {data: [...]} format.');
            }

            $imported = 0;
            $skipped = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($items as $index => $item) {
                $rowNum = $index + 1;

                // Map scraper fields to our schema
                $brand = $this->extractField($item, ['brand', 'manufacturer', 'vendor']);
                $title = $this->extractField($item, ['title', 'name', 'product_title', 'productTitle']);

                if (empty($brand) && empty($title)) {
                    continue;
                }

                // Extract brand from title if missing
                if (empty($brand) && !empty($title)) {
                    $parts = explode(',', $title, 2);
                    if (count($parts) >= 2) {
                        $brand = trim($parts[0]);
                        $title = trim($parts[1]);
                    } else {
                        $brand = 'Unknown';
                    }
                }

                if (empty($title)) {
                    $skipped++;
                    continue;
                }

                // Check for duplicates
                $exists = Supplement::where('brand', $brand)
                    ->where('title', $title)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                // Find category
                $categoryId = $request->default_category_id;
                $categoryName = $this->extractField($item, ['category', 'comparison_group', 'product_category']);
                if ($categoryName) {
                    $category = SupplementCategory::where('name', 'like', '%' . $categoryName . '%')
                        ->orWhere('slug', 'like', '%' . strtolower(str_replace(' ', '-', $categoryName)) . '%')
                        ->first();
                    if ($category) {
                        $categoryId = $category->id;
                    }
                }

                if (!$categoryId) {
                    // Try to auto-detect category from title/description
                    $categoryId = $this->detectCategory($title . ' ' . ($item['product_description'] ?? ''));
                }

                if (!$categoryId) {
                    $errors[] = "Row {$rowNum}: No category found for '{$brand} - " . substr($title, 0, 30) . "'";
                    $skipped++;
                    continue;
                }

                // Parse price
                $price = $this->extractField($item, ['current_price', 'price', 'price_text']);
                if (is_string($price)) {
                    $price = preg_replace('/[^0-9.,]/', '', $price);
                    $price = str_replace(',', '.', $price);
                    $price = floatval($price) ?: null;
                }

                // Create supplement
                $supplement = Supplement::create([
                    'pid' => 'SCRAPE-' . strtoupper($sourceName) . '-' . time() . '-' . $index,
                    'brand' => $brand,
                    'title' => $title,
                    'category_id' => $categoryId,
                    'current_price' => $price,
                    'currency' => $this->extractField($item, ['currency']) ?? 'EUR',
                    'product_url' => $this->extractField($item, ['product_url', 'url', 'link', 'canonical_url']),
                    'image_url' => $this->extractField($item, ['image_url', 'image', 'og_image']),
                    'rating' => $this->parseFloat($this->extractField($item, ['rating', 'rating_text'])),
                    'review_count' => $this->parseInt($this->extractField($item, ['review_count', 'review_count_text', 'reviews'])),
                    'dosage_form' => $this->extractField($item, ['dosage_form', 'form']),
                    'product_description' => $this->extractField($item, ['product_description', 'description', 'metaDesc']),
                    'nutritional_facts' => $this->extractField($item, ['nutritional_facts', 'nutrition', 'supplement_facts']),
                    'suggested_use' => $this->extractField($item, ['suggested_use', 'directions', 'usage']),
                    'warnings' => $this->extractField($item, ['warnings', 'warning']),
                    'other_ingredients' => $this->extractField($item, ['other_ingredients', 'ingredients']),
                    'extraction_confidence' => $this->parseFloat($this->extractField($item, ['extraction_confidence'])) ?? 0.8,
                ]);

                $this->calculateScores($supplement);
                $imported++;
            }

            $this->updateAllCategoryCounts();
            DB::commit();

            $message = "Import completed: {$imported} supplements imported, {$skipped} skipped.";
            if (!empty($errors)) {
                $message .= " First errors: " . implode("; ", array_slice($errors, 0, 3));
            }

            return redirect()->route('admin.index')->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('JSON import failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Extract field from item with multiple possible keys
     */
    private function extractField(array $item, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (!empty($item[$key])) {
                return is_string($item[$key]) ? trim($item[$key]) : (string) $item[$key];
            }
        }
        return null;
    }

    /**
     * Parse float from various formats
     */
    private function parseFloat($value): ?float
    {
        if (is_null($value) || $value === '') return null;
        if (is_numeric($value)) return (float) $value;

        $cleaned = preg_replace('/[^0-9.,]/', '', (string) $value);
        $cleaned = str_replace(',', '.', $cleaned);
        return $cleaned ? (float) $cleaned : null;
    }

    /**
     * Parse int from various formats
     */
    private function parseInt($value): ?int
    {
        if (is_null($value) || $value === '') return null;
        if (is_numeric($value)) return (int) $value;

        $cleaned = preg_replace('/[^0-9]/', '', (string) $value);
        return $cleaned ? (int) $cleaned : null;
    }

    /**
     * Auto-detect category from text
     */
    private function detectCategory(string $text): ?int
    {
        $text = strtolower($text);

        $categoryKeywords = [
            'omega' => 'Omega-3 & Fish Oil',
            'fish oil' => 'Omega-3 & Fish Oil',
            'magnesium' => 'Magnesium',
            'vitamin d' => 'Vitamin D',
            'vitamin d3' => 'Vitamin D',
            'probiotic' => 'Probiotics',
            'collagen' => 'Collagen',
            'protein' => 'Protein Supplements',
            'whey' => 'Protein Supplements',
            'vitamin c' => 'Vitamin C',
            'coq10' => 'CoQ10',
            'ubiquinol' => 'CoQ10',
            'b-complex' => 'B-Vitamins',
            'b vitamin' => 'B-Vitamins',
            'zinc' => 'Zinc',
            'vitamin a' => 'Vitamin A',
            'calcium' => 'Calcium',
            'iron' => 'Iron',
            'digestive enzyme' => 'Digestive Enzymes',
            'amino acid' => 'Amino Acids',
            'bcaa' => 'Amino Acids',
            'electrolyte' => 'Electrolytes',
            'joint' => 'Joint Support',
            'glucosamine' => 'Joint Support',
            'sleep' => 'Sleep Support',
            'melatonin' => 'Sleep Support',
            'ashwagandha' => 'Herbal/Adaptogens',
            'adaptogen' => 'Herbal/Adaptogens',
            'herbal' => 'Herbal/Adaptogens',
            'fiber' => 'Fiber Supplements',
            'psyllium' => 'Fiber Supplements',
        ];

        foreach ($categoryKeywords as $keyword => $categoryName) {
            if (str_contains($text, $keyword)) {
                $category = SupplementCategory::where('name', $categoryName)->first();
                if ($category) {
                    return $category->id;
                }
            }
        }

        return null;
    }

    /**
     * Show scraper test form
     */
    public function scraperTestForm()
    {
        return view('admin.scraper-test');
    }

    /**
     * Run a scraper test and return results
     */
    public function runScraperTest(Request $request)
    {
        $request->validate([
            'source' => 'required|in:skroutz,pharm24,vita4you,iherb,pharmnet,bestprice,custom',
            'search_term' => 'required_without:custom_url|string|max:255',
            'custom_url' => 'required_if:source,custom|nullable|url',
            'use_apify' => 'nullable|boolean',
        ]);

        set_time_limit(300); // Allow longer execution for Apify

        $source = $request->source;
        $searchTerm = $request->search_term;
        $customUrl = $request->custom_url;
        $useApify = $request->boolean('use_apify', true); // Default to Apify

        $results = [];
        $error = null;
        $fetchUrl = null;
        $apifyUsed = false;

        try {
            // Try Apify first for supported sources
            if ($useApify && env('APIFY_API_KEY') && in_array($source, ['skroutz', 'iherb', 'pharmnet', 'vita4you', 'bestprice'])) {
                $apify = new ApifyService();
                $apifyUsed = true;

                Log::info('Running Apify scraper', ['source' => $source, 'searchTerm' => $searchTerm]);

                if ($source === 'iherb') {
                    $fetchUrl = 'Apify: iherb-scraper';
                    $apifyResults = $apify->searchIHerb($searchTerm, 20);
                } else {
                    $fetchUrl = 'Apify: supplement-multi-source-scraper';
                    $apifyResults = $apify->searchMultiSource($source, $searchTerm, 20);
                }

                // Transform Apify results to our format
                foreach ($apifyResults as $item) {
                    $productUrl = $item['product_url'] ?? $item['productUrl'] ?? $item['url'] ?? '';

                    // Try to get title from various fields
                    $title = $item['title'] ?? $item['name'] ?? $item['product_title'] ?? $item['productTitle'] ?? '';

                    // Extract title from URL slug (works great for Skroutz URLs)
                    if (empty($title) && !empty($productUrl)) {
                        $path = parse_url($productUrl, PHP_URL_PATH);
                        // Skroutz URL format: /s/12345/Product-Name-Here.html
                        if (preg_match('/\/s\/\d+\/([^\/]+)\.html?$/i', $path, $m)) {
                            $slug = $m[1];
                            // Convert slug to readable title
                            $slug = str_replace('-', ' ', $slug);
                            // Fix Greek transliteration common patterns
                            $slug = preg_replace('/(\d+)\s+(kapsoules|softgels|tablets|tampletes)/i', '$1 $2', $slug);
                            $title = ucwords(strtolower($slug));
                        } elseif (preg_match('/\/([^\/]+)\.html?$/i', $path, $m)) {
                            // Generic URL pattern
                            $slug = preg_replace('/^\d+-/', '', $m[1]);
                            $slug = str_replace(['-', '_'], ' ', $slug);
                            $title = ucwords(strtolower($slug));
                        }
                    }

                    if (empty($title)) {
                        $title = $item['title_hint'] ?? 'Unknown Product';
                    }

                    // Extract brand from title (usually first word before comma or dash)
                    $brand = $item['brand'] ?? $item['manufacturer'] ?? '';
                    if (empty($brand) && !empty($title)) {
                        // Common pattern: "Brand Name Product Description"
                        if (preg_match('/^(Moller\'?s|Now Foods|Solgar|Life Extension|Nordic Naturals|Lamberts|Nature\'s Bounty|Swisse|Blackmores)/i', $title, $m)) {
                            $brand = $m[1];
                        } else {
                            $brand = $this->extractBrandFromTitle($title);
                        }
                    }

                    $results[] = [
                        'title' => $title,
                        'brand' => $brand,
                        'price' => $this->parseFloat($item['price'] ?? $item['current_price'] ?? $item['price_text'] ?? null),
                        'currency' => $item['currency'] ?? 'EUR',
                        'image' => $item['image'] ?? $item['image_url'] ?? $item['og_image'] ?? null,
                        'url' => $productUrl,
                        'rating' => $this->parseFloat($item['rating'] ?? $item['rating_text'] ?? null),
                        'review_count' => $this->parseInt($item['review_count'] ?? $item['review_count_text'] ?? null),
                        'description' => $item['description'] ?? $item['product_description'] ?? $item['metaDesc'] ?? null,
                        'source' => $item['source'] ?? $source,
                    ];
                }
            } else {
                // Fall back to PHP scraper
                switch ($source) {
                    case 'skroutz':
                        $fetchUrl = 'https://www.skroutz.gr/search?keyphrase=' . urlencode($searchTerm);
                        $results = $this->scrapeSkroutz($fetchUrl);
                        break;

                    case 'pharm24':
                        $fetchUrl = 'https://www.pharm24.gr/catalogsearch/result/?q=' . urlencode($searchTerm);
                        $results = $this->scrapePharm24($fetchUrl);
                        break;

                    case 'vita4you':
                        $fetchUrl = 'https://www.vita4you.gr/search?search=' . urlencode($searchTerm);
                        $results = $this->scrapeVita4You($fetchUrl);
                        break;

                    case 'iherb':
                        $fetchUrl = 'https://gr.iherb.com/search?kw=' . urlencode($searchTerm);
                        $results = $this->scrapeIHerb($fetchUrl);
                        break;

                    case 'custom':
                        $fetchUrl = $customUrl;
                        $results = $this->scrapeCustomUrl($customUrl);
                        break;

                    default:
                        $error = 'Unsupported source without Apify. Please enable Apify or choose a different source.';
                }
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
            Log::error('Scraper test failed', [
                'source' => $source,
                'search' => $searchTerm,
                'useApify' => $useApify,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return view('admin.scraper-test', [
            'results' => $results,
            'error' => $error,
            'source' => $source,
            'searchTerm' => $searchTerm,
            'fetchUrl' => $fetchUrl,
            'resultCount' => count($results),
            'apifyUsed' => $apifyUsed,
        ]);
    }

    /**
     * Scrape Skroutz search results
     */
    private function scrapeSkroutz(string $url): array
    {
        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language' => 'el-GR,el;q=0.9,en;q=0.8',
            'Cache-Control' => 'no-cache',
        ])->timeout(30)->get($url);

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch Skroutz: HTTP ' . $response->status());
        }

        $html = $response->body();
        $products = [];

        // Parse product cards using regex (Skroutz uses ld-product-card)
        // Look for product data in JSON-LD or parse HTML structure

        // Try to find JSON-LD product data first
        preg_match_all('/<script[^>]*type="application\/ld\+json"[^>]*>(.*?)<\/script>/si', $html, $jsonMatches);

        foreach ($jsonMatches[1] ?? [] as $jsonStr) {
            $data = json_decode(trim($jsonStr), true);
            if (isset($data['@type']) && $data['@type'] === 'Product') {
                $products[] = [
                    'title' => $data['name'] ?? 'Unknown',
                    'brand' => $data['brand']['name'] ?? 'Unknown',
                    'price' => $data['offers']['price'] ?? null,
                    'currency' => $data['offers']['priceCurrency'] ?? 'EUR',
                    'image' => $data['image'] ?? null,
                    'url' => $data['offers']['url'] ?? $url,
                    'rating' => $data['aggregateRating']['ratingValue'] ?? null,
                    'review_count' => $data['aggregateRating']['reviewCount'] ?? null,
                ];
            }
            if (isset($data['@type']) && $data['@type'] === 'ItemList') {
                foreach ($data['itemListElement'] ?? [] as $item) {
                    if (isset($item['item'])) {
                        $product = $item['item'];
                        $products[] = [
                            'title' => $product['name'] ?? 'Unknown',
                            'brand' => $product['brand']['name'] ?? 'Unknown',
                            'price' => $product['offers']['lowPrice'] ?? $product['offers']['price'] ?? null,
                            'currency' => $product['offers']['priceCurrency'] ?? 'EUR',
                            'image' => $product['image'] ?? null,
                            'url' => $product['url'] ?? $url,
                            'rating' => $product['aggregateRating']['ratingValue'] ?? null,
                            'review_count' => $product['aggregateRating']['reviewCount'] ?? null,
                        ];
                    }
                }
            }
        }

        // Fallback: parse HTML structure for product cards
        if (empty($products)) {
            // Look for cf-card (category card) or sku-card structures
            preg_match_all('/data-product-id="(\d+)".*?<a[^>]*href="([^"]+)"[^>]*>.*?<img[^>]*src="([^"]+)"[^>]*>.*?(?:class="[^"]*name[^"]*"[^>]*>([^<]+)<|<h2[^>]*>([^<]+)<)/si', $html, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $title = trim($match[4] ?? $match[5] ?? 'Unknown');
                if (!empty($title)) {
                    $products[] = [
                        'title' => html_entity_decode($title),
                        'brand' => $this->extractBrandFromTitle($title),
                        'price' => null,
                        'url' => str_starts_with($match[2], 'http') ? $match[2] : 'https://www.skroutz.gr' . $match[2],
                        'image' => $match[3] ?? null,
                    ];
                }
            }

            // Alternative pattern for list items
            preg_match_all('/<li[^>]*class="[^"]*cf[^"]*"[^>]*>.*?<a[^>]*href="([^"]+)"[^>]*title="([^"]+)".*?(?:data-price="([^"]+)"|price[^>]*>([^<€]+))/si', $html, $altMatches, PREG_SET_ORDER);

            foreach ($altMatches as $match) {
                $title = trim($match[2] ?? 'Unknown');
                $price = trim($match[3] ?? $match[4] ?? '');
                $price = preg_replace('/[^0-9.,]/', '', $price);
                $price = str_replace(',', '.', $price);

                if (!empty($title)) {
                    $products[] = [
                        'title' => html_entity_decode($title),
                        'brand' => $this->extractBrandFromTitle($title),
                        'price' => $price ? floatval($price) : null,
                        'url' => str_starts_with($match[1], 'http') ? $match[1] : 'https://www.skroutz.gr' . $match[1],
                    ];
                }
            }
        }

        return array_slice($products, 0, 20); // Limit to 20 results
    }

    /**
     * Scrape Pharm24 search results
     */
    private function scrapePharm24(string $url): array
    {
        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
            'Accept' => 'text/html,application/xhtml+xml',
            'Accept-Language' => 'el-GR,el;q=0.9,en;q=0.8',
        ])->timeout(30)->get($url);

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch Pharm24: HTTP ' . $response->status());
        }

        $html = $response->body();
        $products = [];

        // Look for product list items
        preg_match_all('/<div[^>]*class="[^"]*product-item[^"]*"[^>]*>.*?<a[^>]*href="([^"]+)"[^>]*>.*?<img[^>]*src="([^"]+)".*?<span[^>]*class="[^"]*product-name[^"]*"[^>]*>([^<]+)<.*?(?:price[^>]*>([^<€]+)|data-price="([^"]+)")/si', $html, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $title = trim($match[3] ?? 'Unknown');
            $price = trim($match[4] ?? $match[5] ?? '');
            $price = preg_replace('/[^0-9.,]/', '', $price);
            $price = str_replace(',', '.', $price);

            if (!empty($title)) {
                $products[] = [
                    'title' => html_entity_decode($title),
                    'brand' => $this->extractBrandFromTitle($title),
                    'price' => $price ? floatval($price) : null,
                    'currency' => 'EUR',
                    'url' => str_starts_with($match[1], 'http') ? $match[1] : 'https://www.pharm24.gr' . $match[1],
                    'image' => $match[2] ?? null,
                ];
            }
        }

        return array_slice($products, 0, 20);
    }

    /**
     * Scrape Vita4You search results
     */
    private function scrapeVita4You(string $url): array
    {
        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
            'Accept' => 'text/html,application/xhtml+xml',
            'Accept-Language' => 'el-GR,el;q=0.9,en;q=0.8',
        ])->timeout(30)->get($url);

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch Vita4You: HTTP ' . $response->status());
        }

        $html = $response->body();
        $products = [];

        // Parse product cards
        preg_match_all('/<div[^>]*class="[^"]*product[^"]*"[^>]*>.*?<a[^>]*href="([^"]+)"[^>]*>.*?<img[^>]*src="([^"]+)".*?(?:title[^>]*>([^<]+)<|alt="([^"]+)").*?(?:price[^>]*>([^<€]+))/si', $html, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $title = trim($match[3] ?? $match[4] ?? 'Unknown');
            $price = trim($match[5] ?? '');
            $price = preg_replace('/[^0-9.,]/', '', $price);
            $price = str_replace(',', '.', $price);

            if (!empty($title)) {
                $products[] = [
                    'title' => html_entity_decode($title),
                    'brand' => $this->extractBrandFromTitle($title),
                    'price' => $price ? floatval($price) : null,
                    'currency' => 'EUR',
                    'url' => str_starts_with($match[1], 'http') ? $match[1] : 'https://www.vita4you.gr' . $match[1],
                    'image' => $match[2] ?? null,
                ];
            }
        }

        return array_slice($products, 0, 20);
    }

    /**
     * Scrape iHerb Greece search results
     */
    private function scrapeIHerb(string $url): array
    {
        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml',
            'Accept-Language' => 'el-GR,el;q=0.9,en;q=0.8',
        ])->timeout(30)->get($url);

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch iHerb: HTTP ' . $response->status());
        }

        $html = $response->body();
        $products = [];

        // iHerb products are often in JavaScript - try multiple extraction methods

        // Method 1: Look for product data in window.__PRELOADED_STATE__ or similar
        if (preg_match('/window\.__PRELOADED_STATE__\s*=\s*({.*?});/s', $html, $preloadMatch)) {
            $data = json_decode($preloadMatch[1], true);
            if (isset($data['search']['products'])) {
                foreach ($data['search']['products'] as $product) {
                    $products[] = [
                        'title' => $product['name'] ?? 'Unknown',
                        'brand' => $product['brandName'] ?? 'Unknown',
                        'price' => $product['price'] ?? null,
                        'currency' => 'EUR',
                        'image' => $product['image'] ?? null,
                        'url' => isset($product['url']) ? 'https://gr.iherb.com' . $product['url'] : $url,
                        'rating' => $product['rating'] ?? null,
                        'review_count' => $product['reviewCount'] ?? null,
                    ];
                }
            }
        }

        // Method 2: Parse HTML structure (limited - iHerb uses JS rendering)
        if (empty($products)) {
            preg_match_all('/<div[^>]*class="[^"]*product[^"]*"[^>]*>.*?<a[^>]*href="([^"]+)"[^>]*>.*?(?:title="([^"]+)"|alt="([^"]+)").*?(?:<span[^>]*class="[^"]*price[^"]*"[^>]*>([^<]+))?/si', $html, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $title = trim($match[2] ?? $match[3] ?? 'Unknown');
                $price = trim($match[4] ?? '');
                $price = preg_replace('/[^0-9.,]/', '', $price);
                $price = str_replace(',', '.', $price);

                if (!empty($title) && $title !== 'Unknown') {
                    $products[] = [
                        'title' => html_entity_decode($title),
                        'brand' => $this->extractBrandFromTitle($title),
                        'price' => $price ? floatval($price) : null,
                        'currency' => 'EUR',
                        'url' => str_starts_with($match[1], 'http') ? $match[1] : 'https://gr.iherb.com' . $match[1],
                    ];
                }
            }
        }

        // Method 3: Try to find product-cell containers
        if (empty($products)) {
            preg_match_all('/class="product-title"[^>]*>([^<]+)<|aria-label="([^"]+)"/i', $html, $titleMatches);
            $titles = array_filter(array_merge($titleMatches[1] ?? [], $titleMatches[2] ?? []));

            foreach (array_slice($titles, 0, 20) as $title) {
                if (!empty(trim($title)) && strlen($title) > 5) {
                    $products[] = [
                        'title' => html_entity_decode(trim($title)),
                        'brand' => $this->extractBrandFromTitle(trim($title)),
                        'price' => null,
                        'url' => $url,
                        'note' => 'Limited data - iHerb uses JavaScript rendering. Use the Python scraper for full data.',
                    ];
                }
            }
        }

        return array_slice($products, 0, 20);
    }

    /**
     * Scrape a custom URL (generic parser)
     */
    private function scrapeCustomUrl(string $url): array
    {
        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/json',
        ])->timeout(30)->get($url);

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch URL: HTTP ' . $response->status());
        }

        $html = $response->body();
        $products = [];

        // Try to find JSON-LD product data
        preg_match_all('/<script[^>]*type="application\/ld\+json"[^>]*>(.*?)<\/script>/si', $html, $jsonMatches);

        foreach ($jsonMatches[1] ?? [] as $jsonStr) {
            $data = json_decode(trim($jsonStr), true);
            if (isset($data['@type']) && $data['@type'] === 'Product') {
                $products[] = [
                    'title' => $data['name'] ?? 'Unknown',
                    'brand' => $data['brand']['name'] ?? $this->extractBrandFromTitle($data['name'] ?? ''),
                    'price' => $data['offers']['price'] ?? null,
                    'currency' => $data['offers']['priceCurrency'] ?? 'EUR',
                    'image' => $data['image'] ?? null,
                    'url' => $data['offers']['url'] ?? $url,
                    'description' => $data['description'] ?? null,
                ];
            }
        }

        // Extract Open Graph / meta data
        if (empty($products)) {
            $title = null;
            $price = null;
            $image = null;

            if (preg_match('/<meta[^>]*property="og:title"[^>]*content="([^"]+)"/i', $html, $m)) {
                $title = $m[1];
            }
            if (preg_match('/<meta[^>]*property="og:image"[^>]*content="([^"]+)"/i', $html, $m)) {
                $image = $m[1];
            }
            if (preg_match('/<meta[^>]*property="product:price:amount"[^>]*content="([^"]+)"/i', $html, $m)) {
                $price = floatval($m[1]);
            }

            if ($title) {
                $products[] = [
                    'title' => html_entity_decode($title),
                    'brand' => $this->extractBrandFromTitle($title),
                    'price' => $price,
                    'currency' => 'EUR',
                    'image' => $image,
                    'url' => $url,
                ];
            }
        }

        return $products;
    }

    /**
     * Extract brand from product title (first word or before comma/dash)
     */
    private function extractBrandFromTitle(string $title): string
    {
        // Common patterns: "Brand, Product Name" or "Brand - Product Name"
        if (preg_match('/^([^,\-]+)[,\-]/', $title, $m)) {
            $brand = trim($m[1]);
            // If brand is very short or looks like a product word, use first word
            if (strlen($brand) > 2 && !preg_match('/^(omega|vitamin|supplement|capsule)/i', $brand)) {
                return $brand;
            }
        }

        // Fall back to first word
        $words = explode(' ', $title);
        return $words[0] ?? 'Unknown';
    }

    /**
     * Import scraped results to database
     */
    public function importScrapedResults(Request $request)
    {
        $request->validate([
            'products' => 'required|json',
            'category_id' => 'required|exists:supplement_categories,id',
        ]);

        $products = json_decode($request->products, true);
        $categoryId = $request->category_id;
        $sourceName = $request->source_name ?? 'web-scrape';

        $imported = 0;
        $skipped = 0;

        DB::beginTransaction();

        try {
            foreach ($products as $index => $product) {
                $brand = $product['brand'] ?? 'Unknown';
                $title = $product['title'] ?? '';

                if (empty($title)) {
                    $skipped++;
                    continue;
                }

                // Check for duplicates
                $exists = Supplement::where('brand', $brand)
                    ->where('title', $title)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                $supplement = Supplement::create([
                    'pid' => 'SCRAPE-' . strtoupper($sourceName) . '-' . time() . '-' . $index,
                    'brand' => $brand,
                    'title' => $title,
                    'category_id' => $categoryId,
                    'current_price' => $product['price'] ?? null,
                    'currency' => $product['currency'] ?? 'EUR',
                    'product_url' => $product['url'] ?? null,
                    'image_url' => $product['image'] ?? null,
                    'rating' => $product['rating'] ?? null,
                    'review_count' => $product['review_count'] ?? null,
                    'product_description' => $product['description'] ?? null,
                ]);

                $this->calculateScores($supplement);
                $imported++;
            }

            $this->updateCategoryCount($categoryId);
            DB::commit();

            return redirect()->route('admin.index')
                ->with('success', "Imported {$imported} products, {$skipped} skipped (duplicates or empty).");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Import scraped results failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
}
