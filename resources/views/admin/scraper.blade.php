<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scraper Integration | B-COMPARE Admin</title>
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
                    <a href="{{ route('admin.index') }}" class="p-2 hover:bg-gray-100 rounded-lg">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-xl font-semibold" style="color: var(--charcoal);">Scraper Integration</h1>
                        <p class="text-xs text-gray-500">Import from scrapers or trigger new scrapes</p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-6 py-8" x-data="scraperPanel()">
        @if(session('success'))
            <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl">
                {{ session('error') }}
            </div>
        @endif

        <!-- Quick Test Button -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl p-6 mb-8 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold mb-1">Test Scraper Live</h2>
                    <p class="text-blue-100 text-sm">Run a quick test to scrape Omega 3, Vitamin D, or any supplement from Skroutz, Pharm24, and more</p>
                </div>
                <a href="{{ route('admin.scraper.test.form') }}" class="px-6 py-3 bg-white text-blue-600 rounded-lg font-medium hover:bg-blue-50 transition-colors flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    Run Test Scraper
                </a>
            </div>
        </div>

        <!-- Available Scrapers -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <!-- iHerb Scraper -->
            <div class="bg-white rounded-xl border p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold" style="color: var(--charcoal);">iHerb Greece</h3>
                        <p class="text-xs text-gray-500">gr.iherb.com</p>
                    </div>
                </div>
                <p class="text-sm text-gray-600 mb-4">Scrape supplements from iHerb Greece with full product details.</p>
                <div class="space-y-2">
                    <p class="text-xs text-gray-500">Local scraper: <code class="bg-gray-100 px-1 rounded">~/iherb_supplements_scraper/</code></p>
                </div>
            </div>

            <!-- Multi-Source Scraper -->
            <div class="bg-white rounded-xl border p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold" style="color: var(--charcoal);">Multi-Source (Apify)</h3>
                        <p class="text-xs text-gray-500">Greek pharmacies & marketplaces</p>
                    </div>
                </div>
                <p class="text-sm text-gray-600 mb-4">Supports: Skroutz, BestPrice, Pharmnet, Vita4You, Klifes, custom URLs</p>
                <div class="space-y-2">
                    <p class="text-xs text-gray-500">Apify actor: <code class="bg-gray-100 px-1 rounded">~/actors/supplement-multi-source-scraper/</code></p>
                </div>
            </div>

            <!-- Custom URL Scraper -->
            <div class="bg-white rounded-xl border p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold" style="color: var(--charcoal);">Custom URL</h3>
                        <p class="text-xs text-gray-500">Any supplement website</p>
                    </div>
                </div>
                <p class="text-sm text-gray-600 mb-4">Scrape any website by providing product URLs directly.</p>
            </div>
        </div>

        <!-- Import from JSON -->
        <div class="bg-white rounded-xl border p-6 mb-8">
            <h2 class="text-lg font-semibold mb-4" style="color: var(--charcoal);">Import from Scraper Output</h2>
            <p class="text-sm text-gray-600 mb-4">Upload a JSON file from your Apify scraper output or local scraper.</p>

            <form method="POST" action="{{ route('admin.scraper.import') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">JSON File</label>
                    <div class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:border-yellow-500 transition-colors">
                        <input type="file" name="json_file" required accept=".json"
                               class="w-full cursor-pointer">
                        <p class="text-sm text-gray-500 mt-2">Apify dataset export or local scraper JSON output</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Default Category</label>
                        <select name="default_category_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">-- Auto-detect from data --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Source Name</label>
                        <input type="text" name="source_name" placeholder="e.g., iherb, skroutz, pharmnet"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                </div>

                <button type="submit" class="px-6 py-2 text-sm font-medium text-white rounded-lg" style="background: var(--gold);">
                    Import JSON
                </button>
            </form>
        </div>

        <!-- Artisan Commands -->
        <div class="bg-white rounded-xl border p-6">
            <h2 class="text-lg font-semibold mb-4" style="color: var(--charcoal);">CLI Commands</h2>
            <p class="text-sm text-gray-600 mb-4">You can also import data using artisan commands:</p>

            <div class="space-y-4">
                <div class="p-4 bg-gray-800 rounded-lg">
                    <p class="text-xs text-gray-400 mb-1"># Import from Apify JSON file</p>
                    <code class="text-green-400 text-sm">php artisan supplements:import-json /path/to/apify-output.json</code>
                </div>

                <div class="p-4 bg-gray-800 rounded-lg">
                    <p class="text-xs text-gray-400 mb-1"># Run iHerb scraper and import</p>
                    <code class="text-green-400 text-sm">cd ~/iherb_supplements_scraper && python scrape_iherb_supplements.py</code>
                </div>

                <div class="p-4 bg-gray-800 rounded-lg">
                    <p class="text-xs text-gray-400 mb-1"># Recalculate all scores</p>
                    <code class="text-green-400 text-sm">php artisan supplements:calculate-scores</code>
                </div>

                <div class="p-4 bg-gray-800 rounded-lg">
                    <p class="text-xs text-gray-400 mb-1"># Update category counts</p>
                    <code class="text-green-400 text-sm">php artisan supplements:update-counts</code>
                </div>
            </div>
        </div>
    </main>

    <script>
        function scraperPanel() {
            return {
                // Future: Add live scraper controls
            }
        }
    </script>
</body>
</html>
