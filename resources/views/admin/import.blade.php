<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Supplements | B-COMPARE Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        :root { --gold: #c6a76b; --charcoal: #1d1d1f; }
    </style>
</head>
<body class="bg-gray-50">
    <header class="bg-white border-b sticky top-0 z-50">
        <div class="max-w-4xl mx-auto px-6 py-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.index') }}" class="p-2 hover:bg-gray-100 rounded-lg">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-xl font-semibold" style="color: var(--charcoal);">Import Supplements</h1>
                    <p class="text-xs text-gray-500">Upload CSV or Excel file</p>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-6 py-8">
        @if(session('error'))
            <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-white rounded-xl border p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4" style="color: var(--charcoal);">Upload File</h2>

            <form method="POST" action="{{ route('admin.import.process') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">CSV or Excel File</label>
                    <div class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center hover:border-yellow-500 transition-colors">
                        <input type="file" name="file" required accept=".csv,.xlsx,.xls"
                               class="w-full cursor-pointer">
                        <p class="text-sm text-gray-500 mt-2">Supported formats: .csv, .xlsx, .xls (max 10MB)</p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Default Category (if not in file)</label>
                    <select name="default_category_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">-- No default (skip rows without category) --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="w-full px-6 py-3 text-sm font-medium text-white rounded-lg" style="background: var(--gold);">
                    Upload & Import
                </button>
            </form>
        </div>

        <!-- File Format Guide -->
        <div class="bg-white rounded-xl border p-6">
            <h2 class="text-lg font-semibold mb-4" style="color: var(--charcoal);">File Format Guide</h2>

            <div class="space-y-4">
                <div>
                    <h3 class="font-medium text-gray-700 mb-2">Required Columns:</h3>
                    <ul class="list-disc list-inside text-sm text-gray-600 space-y-1">
                        <li><code class="bg-gray-100 px-1 rounded">brand</code> - Brand name</li>
                        <li><code class="bg-gray-100 px-1 rounded">title</code> - Product title</li>
                    </ul>
                </div>

                <div>
                    <h3 class="font-medium text-gray-700 mb-2">Optional Columns:</h3>
                    <ul class="list-disc list-inside text-sm text-gray-600 space-y-1">
                        <li><code class="bg-gray-100 px-1 rounded">category</code> - Category name or slug</li>
                        <li><code class="bg-gray-100 px-1 rounded">price</code> - Current price</li>
                        <li><code class="bg-gray-100 px-1 rounded">currency</code> - EUR, USD, GBP</li>
                        <li><code class="bg-gray-100 px-1 rounded">product_url</code> or <code class="bg-gray-100 px-1 rounded">url</code> - Product page URL</li>
                        <li><code class="bg-gray-100 px-1 rounded">image_url</code> or <code class="bg-gray-100 px-1 rounded">image</code> - Image URL</li>
                        <li><code class="bg-gray-100 px-1 rounded">rating</code> - Rating (0-5)</li>
                        <li><code class="bg-gray-100 px-1 rounded">review_count</code> or <code class="bg-gray-100 px-1 rounded">reviews</code> - Number of reviews</li>
                        <li><code class="bg-gray-100 px-1 rounded">dosage_form</code> or <code class="bg-gray-100 px-1 rounded">form</code> - capsule, softgel, tablet, etc.</li>
                        <li><code class="bg-gray-100 px-1 rounded">serving_size</code> - Serving size value</li>
                        <li><code class="bg-gray-100 px-1 rounded">serving_unit</code> - Serving unit (capsule, ml, etc.)</li>
                        <li><code class="bg-gray-100 px-1 rounded">servings</code> - Servings per container</li>
                        <li><code class="bg-gray-100 px-1 rounded">description</code> - Product description</li>
                    </ul>
                </div>

                <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <h3 class="font-medium text-yellow-800 mb-2">Duplicate Handling:</h3>
                    <p class="text-sm text-yellow-700">Products with the same brand + title combination will be skipped to prevent duplicates.</p>
                </div>

                <div>
                    <h3 class="font-medium text-gray-700 mb-2">Example CSV:</h3>
                    <pre class="bg-gray-800 text-gray-100 p-4 rounded-lg text-xs overflow-x-auto">brand,title,category,price,rating,url
NOW Foods,Vitamin D3 5000 IU,Vitamin D,12.99,4.8,https://example.com/product1
Life Extension,Magnesium Citrate,Magnesium,18.50,4.7,https://example.com/product2
Thorne,Omega-3 Fish Oil,Omega-3 & Fish Oil,32.00,4.9,https://example.com/product3</pre>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
