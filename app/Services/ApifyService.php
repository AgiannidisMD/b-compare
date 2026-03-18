<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApifyService
{
    private string $apiKey;
    private string $baseUrl = 'https://api.apify.com/v2';

    // Your Apify actor IDs
    public const ACTORS = [
        'supplement-multi-source' => 'brWLLt6ThslTUtykw',
        'iherb' => 'a3nPZ17yPeMVa1H4C',
        'cheerio' => 'YrQuEkowkNCLdk4j2',
        'web-scraper' => 'moJRLRc85AitArpNN',
    ];

    public function __construct()
    {
        $this->apiKey = config('services.apify.api_key', env('APIFY_API_KEY'));
    }

    /**
     * Run an actor and wait for results
     */
    public function runActorSync(string $actorId, array $input, int $timeoutSecs = 120): array
    {
        $url = "{$this->baseUrl}/acts/{$actorId}/run-sync-get-dataset-items";

        Log::info('Running Apify actor', ['actorId' => $actorId, 'input' => $input]);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])
        ->timeout($timeoutSecs)
        ->post($url, $input);

        if (!$response->successful()) {
            Log::error('Apify actor failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new \Exception('Apify request failed: ' . $response->status() . ' - ' . substr($response->body(), 0, 200));
        }

        return $response->json() ?? [];
    }

    /**
     * Run actor asynchronously and return run ID
     */
    public function runActorAsync(string $actorId, array $input): string
    {
        $url = "{$this->baseUrl}/acts/{$actorId}/runs";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])
        ->timeout(30)
        ->post($url, $input);

        if (!$response->successful()) {
            throw new \Exception('Failed to start Apify actor: ' . $response->status());
        }

        $data = $response->json();
        return $data['data']['id'] ?? '';
    }

    /**
     * Get run status
     */
    public function getRunStatus(string $runId): array
    {
        $url = "{$this->baseUrl}/actor-runs/{$runId}";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->get($url);

        return $response->json()['data'] ?? [];
    }

    /**
     * Get dataset items from a run
     */
    public function getDatasetItems(string $datasetId, int $limit = 100): array
    {
        $url = "{$this->baseUrl}/datasets/{$datasetId}/items";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->get($url, ['limit' => $limit]);

        return $response->json() ?? [];
    }

    /**
     * Run Skroutz search via supplement-multi-source-scraper
     */
    public function searchSkroutz(string $searchTerm, int $maxProducts = 20): array
    {
        $input = [
            'source' => 'skroutz',
            'searchTerm' => $searchTerm,
            'maxProducts' => $maxProducts,
            'includeDetails' => false, // Quick search mode
        ];

        return $this->runActorSync(self::ACTORS['supplement-multi-source'], $input, 180);
    }

    /**
     * Run iHerb search
     */
    public function searchIHerb(string $searchTerm, int $maxProducts = 20): array
    {
        $input = [
            'searchTerm' => $searchTerm,
            'maxProducts' => $maxProducts,
            'country' => 'gr', // Greece
        ];

        return $this->runActorSync(self::ACTORS['iherb'], $input, 180);
    }

    /**
     * Run Cheerio scraper on a URL
     */
    public function scrapeUrl(string $url): array
    {
        $input = [
            'startUrls' => [['url' => $url]],
            'pageFunction' => $this->getProductPageFunction(),
            'proxyConfiguration' => ['useApifyProxy' => true],
        ];

        return $this->runActorSync(self::ACTORS['cheerio'], $input, 60);
    }

    /**
     * Generic multi-source search using supplement-multi-source-scraper
     * Based on actor input schema: sources, brand_queries, start_urls, max_pages_per_seed, max_results_per_seed
     */
    public function searchMultiSource(string $source, string $searchTerm, int $maxProducts = 20): array
    {
        $input = [
            'sources' => [$source], // Array of sources: skroutz, pharmnet, vita4you, bestprice, etc.
            'brand_queries' => [$searchTerm], // Search terms to look for
            'max_pages_per_seed' => 1, // Limit pages for speed
            'max_results_per_seed' => $maxProducts,
            'rate_limit_delay' => 0.5,
            'include_phase2' => true, // Enable detail fetching
            'phase2_batch_size' => min($maxProducts, 10), // Limit batch for speed
        ];

        return $this->runActorSync(self::ACTORS['supplement-multi-source'], $input, 300);
    }

    /**
     * Quick search - only Phase 1 (URLs only, much faster)
     * Returns URLs with extracted title hints from URL
     */
    public function quickSearch(string $source, string $searchTerm, int $maxProducts = 20): array
    {
        $input = [
            'sources' => [$source],
            'brand_queries' => [$searchTerm],
            'max_pages_per_seed' => 1,
            'max_results_per_seed' => $maxProducts,
            'rate_limit_delay' => 0.5,
            'include_phase2' => false, // Quick mode
        ];

        $results = $this->runActorSync(self::ACTORS['supplement-multi-source'], $input, 120);

        // Extract title from URL for quick display
        foreach ($results as &$item) {
            if (empty($item['title']) && !empty($item['product_url'])) {
                // Extract product name from URL slug
                $path = parse_url($item['product_url'], PHP_URL_PATH);
                if (preg_match('/\/([^\/]+)\.html?$/i', $path, $m)) {
                    $slug = $m[1];
                    // Clean up slug to readable title
                    $slug = preg_replace('/^\d+-/', '', $slug); // Remove leading ID
                    $slug = str_replace(['-', '_'], ' ', $slug);
                    $item['title'] = ucwords($slug);
                }
            }
        }

        return $results;
    }

    /**
     * Page function for extracting product data
     */
    private function getProductPageFunction(): string
    {
        return <<<'JS'
async function pageFunction(context) {
    const { $, request } = context;

    // Try to extract product data
    const product = {
        url: request.url,
        title: $('meta[property="og:title"]').attr('content') || $('h1').first().text().trim(),
        price: $('meta[property="product:price:amount"]').attr('content'),
        image: $('meta[property="og:image"]').attr('content'),
        description: $('meta[property="og:description"]').attr('content'),
    };

    // Try JSON-LD
    const jsonLd = $('script[type="application/ld+json"]').first().html();
    if (jsonLd) {
        try {
            const data = JSON.parse(jsonLd);
            if (data['@type'] === 'Product') {
                product.title = data.name || product.title;
                product.brand = data.brand?.name;
                product.price = data.offers?.price || product.price;
                product.image = data.image || product.image;
            }
        } catch (e) {}
    }

    return product;
}
JS;
    }

    /**
     * Get recent runs for an actor
     */
    public function getRecentRuns(string $actorId, int $limit = 10): array
    {
        $url = "{$this->baseUrl}/acts/{$actorId}/runs";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->get($url, ['limit' => $limit, 'desc' => true]);

        return $response->json()['data']['items'] ?? [];
    }
}
