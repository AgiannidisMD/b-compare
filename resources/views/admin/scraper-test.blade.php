<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scraper Test | B-COMPARE Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        :root { --gold: #c6a76b; --charcoal: #1d1d1f; }
    </style>
</head>
<body class="bg-gray-50">
    <header class="bg-white border-b sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <a href="{{ route('admin.scraper') }}" class="p-2 hover:bg-gray-100 rounded-lg">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-xl font-semibold" style="color: var(--charcoal);">Scraper Test</h1>
                        <p class="text-xs text-gray-500">Test web scraping and see results instantly</p>
                    </div>
                </div>
                <a href="{{ route('admin.index') }}" class="text-sm text-gray-600 hover:text-gray-800">
                    Back to Admin
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-6 py-8" x-data="scraperTest()">
        <!-- Search Form -->
        <div class="bg-white rounded-xl border p-6 mb-8">
            <h2 class="text-lg font-semibold mb-4" style="color: var(--charcoal);">Run Scraper Test</h2>

            <form method="POST" action="{{ route('admin.scraper.test') }}" class="space-y-4">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Source</label>
                        <select name="source" x-model="source" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500/20 focus:border-yellow-600">
                            <optgroup label="Apify Cloud (Recommended)">
                                <option value="skroutz">Skroutz.gr</option>
                                <option value="iherb">iHerb Greece</option>
                                <option value="pharmnet">Pharmnet.gr</option>
                                <option value="vita4you">Vita4You.gr</option>
                                <option value="bestprice">BestPrice.gr</option>
                            </optgroup>
                            <optgroup label="PHP Scraper (Limited)">
                                <option value="pharm24">Pharm24.gr (PHP)</option>
                                <option value="custom">Custom URL (PHP)</option>
                            </optgroup>
                        </select>
                    </div>

                    <div x-show="source !== 'custom'">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search Term</label>
                        <input type="text" name="search_term"
                               value="{{ $searchTerm ?? '' }}"
                               placeholder="e.g., Omega 3, Vitamin D, Magnesium"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500/20 focus:border-yellow-600">
                    </div>

                    <div x-show="source === 'custom'">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Custom URL</label>
                        <input type="url" name="custom_url"
                               placeholder="https://..."
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500/20 focus:border-yellow-600">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Engine</label>
                        <label class="flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                            <input type="checkbox" name="use_apify" value="1" checked x-model="useApify"
                                   class="w-4 h-4 text-blue-600 rounded">
                            <span class="text-sm">Use Apify Cloud</span>
                            <span class="ml-auto text-xs text-green-600 font-medium">Active</span>
                        </label>
                    </div>

                    <div class="flex items-end">
                        <button type="submit" class="w-full px-6 py-3 text-sm font-medium text-white rounded-lg flex items-center justify-center gap-2" style="background: var(--gold);">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            Run Scraper
                        </button>
                    </div>
                </div>
            </form>

            <!-- Apify info -->
            <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700 flex items-center gap-3">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span><strong>Apify API Connected!</strong> Using cloud-based scraping with anti-bot bypass. Scraping may take 30-60 seconds.</span>
            </div>
        </div>

        @if(isset($error))
            <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl">
                <strong>Error:</strong> {{ $error }}
            </div>
        @endif

        @if(isset($results) && count($results) > 0)
            <!-- Results Header -->
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-semibold flex items-center gap-2" style="color: var(--charcoal);">
                        Scraper Results
                        @if($apifyUsed ?? false)
                            <span class="px-2 py-0.5 text-xs font-medium bg-green-100 text-green-700 rounded-full">Apify</span>
                        @endif
                    </h2>
                    <p class="text-sm text-gray-500">
                        Found <strong>{{ $resultCount }}</strong> products from
                        <span class="font-medium text-blue-600">{{ ucfirst($source) }}</span>
                        @if($searchTerm)
                            for "{{ $searchTerm }}"
                        @endif
                    </p>
                </div>

                @if($fetchUrl)
                    <a href="{{ $fetchUrl }}" target="_blank" class="text-sm text-blue-600 hover:underline flex items-center gap-1">
                        View Source Page
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                    </a>
                @endif
            </div>

            <!-- Import Form -->
            <form method="POST" action="{{ route('admin.scraper.import-results') }}" class="mb-6">
                @csrf
                <input type="hidden" name="products" value="{{ json_encode($results) }}">
                <input type="hidden" name="source_name" value="{{ $source }}">

                <div class="bg-green-50 border border-green-200 rounded-xl p-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        <div>
                            <p class="font-medium text-green-800">Import these products to B-COMPARE?</p>
                            <p class="text-sm text-green-600">Select a category and import all {{ $resultCount }} products</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <select name="category_id" required class="px-3 py-2 border border-green-300 rounded-lg bg-white text-sm">
                            <option value="">Select Category</option>
                            @foreach(\App\Models\SupplementCategory::orderBy('name')->get() as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700">
                            Import All
                        </button>
                    </div>
                </div>
            </form>

            <!-- Results Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($results as $index => $product)
                    <div class="bg-white rounded-xl border p-4 hover:shadow-lg transition-shadow">
                        <div class="flex gap-4">
                            @if(!empty($product['image']))
                                <img src="{{ $product['image'] }}"
                                     alt="{{ $product['title'] }}"
                                     class="w-20 h-20 object-contain bg-gray-50 rounded-lg flex-shrink-0"
                                     onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><rect fill=%22%23f3f4f6%22 width=%22100%22 height=%22100%22/><text x=%2250%22 y=%2255%22 text-anchor=%22middle%22 fill=%22%239ca3af%22 font-size=%2212%22>No Image</text></svg>'">
                            @else
                                <div class="w-20 h-20 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            @endif

                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-gray-500 mb-1">{{ $product['brand'] ?? 'Unknown Brand' }}</p>
                                <h3 class="font-medium text-sm text-gray-900 line-clamp-2 mb-2">
                                    {{ \Illuminate\Support\Str::limit($product['title'] ?? 'Unknown', 60) }}
                                </h3>

                                <div class="flex items-center justify-between">
                                    @if(!empty($product['price']))
                                        <span class="text-lg font-bold" style="color: var(--gold);">
                                            {{ $product['currency'] ?? 'EUR' }} {{ number_format($product['price'], 2) }}
                                        </span>
                                    @else
                                        <span class="text-sm text-gray-400">No price</span>
                                    @endif

                                    @if(!empty($product['rating']))
                                        <span class="text-sm text-amber-600 flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                            {{ number_format($product['rating'], 1) }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if(!empty($product['url']))
                            <div class="mt-3 pt-3 border-t">
                                <a href="{{ $product['url'] }}" target="_blank"
                                   class="text-xs text-blue-600 hover:underline flex items-center gap-1">
                                    View on {{ ucfirst($source ?? 'source') }}
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                    </svg>
                                </a>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <!-- Raw JSON Data (collapsible) -->
            <details class="mt-8 bg-white rounded-xl border">
                <summary class="px-6 py-4 cursor-pointer font-medium text-gray-700 hover:bg-gray-50">
                    View Raw JSON Data
                </summary>
                <div class="px-6 pb-6">
                    <pre class="bg-gray-800 text-gray-100 p-4 rounded-lg text-xs overflow-x-auto max-h-96">{{ json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </details>

        @elseif(isset($results) && count($results) === 0)
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 text-center">
                <svg class="w-12 h-12 text-yellow-500 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <h3 class="text-lg font-medium text-yellow-800 mb-1">No Products Found</h3>
                <p class="text-sm text-yellow-600">
                    The scraper couldn't find any products. This might be due to:
                </p>
                <ul class="text-sm text-yellow-600 mt-2 space-y-1">
                    <li>- Anti-bot protection on the website</li>
                    <li>- Changed HTML structure</li>
                    <li>- No results for the search term</li>
                </ul>
                @if($fetchUrl)
                    <p class="mt-4">
                        <a href="{{ $fetchUrl }}" target="_blank" class="text-blue-600 hover:underline text-sm">
                            Try visiting the page directly
                        </a>
                    </p>
                @endif
            </div>
        @else
            <!-- Empty state / instructions -->
            <div class="bg-white rounded-xl border p-8 text-center">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-700 mb-2">Test the Web Scraper</h3>
                <p class="text-gray-500 mb-4">
                    Select a source and enter a search term to see what products can be scraped.
                </p>
                <div class="text-sm text-gray-400 space-y-1">
                    <p>Try searching for:</p>
                    <p class="font-medium">Omega 3 | Vitamin D | Magnesium | Collagen | Probiotics</p>
                </div>
            </div>
        @endif
    </main>

    <script>
        function scraperTest() {
            return {
                source: '{{ $source ?? "skroutz" }}',
                useApify: true,
            }
        }
    </script>
</body>
</html>
