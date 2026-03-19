<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Supplement | VITA-COMPARE Admin</title>
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
                    <h1 class="text-xl font-semibold" style="color: var(--charcoal);">Add New Supplement</h1>
                    <p class="text-xs text-gray-500">Fill in the details below</p>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-6 py-8">
        @if($errors->any())
            <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.store') }}" class="space-y-6">
            @csrf

            <!-- Basic Info -->
            <div class="bg-white rounded-xl border p-6">
                <h2 class="text-lg font-semibold mb-4" style="color: var(--charcoal);">Basic Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Brand *</label>
                        <input type="text" name="brand" value="{{ old('brand') }}" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500/20 focus:border-yellow-600">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                        <select name="category_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500/20 focus:border-yellow-600">
                            <option value="">Select category...</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                        <input type="text" name="title" value="{{ old('title') }}" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500/20 focus:border-yellow-600">
                    </div>
                </div>
            </div>

            <!-- Pricing -->
            <div class="bg-white rounded-xl border p-6">
                <h2 class="text-lg font-semibold mb-4" style="color: var(--charcoal);">Pricing & Rating</h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Price</label>
                        <input type="number" step="0.01" name="current_price" value="{{ old('current_price') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500/20 focus:border-yellow-600">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Currency</label>
                        <select name="currency" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500/20 focus:border-yellow-600">
                            <option value="EUR" {{ old('currency', 'EUR') == 'EUR' ? 'selected' : '' }}>EUR</option>
                            <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>USD</option>
                            <option value="GBP" {{ old('currency') == 'GBP' ? 'selected' : '' }}>GBP</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Rating (0-5)</label>
                        <input type="number" step="0.1" min="0" max="5" name="rating" value="{{ old('rating') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500/20 focus:border-yellow-600">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Review Count</label>
                        <input type="number" min="0" name="review_count" value="{{ old('review_count') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500/20 focus:border-yellow-600">
                    </div>
                </div>
            </div>

            <!-- Serving Info -->
            <div class="bg-white rounded-xl border p-6">
                <h2 class="text-lg font-semibold mb-4" style="color: var(--charcoal);">Serving Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dosage Form</label>
                        <select name="dosage_form" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500/20 focus:border-yellow-600">
                            <option value="">Select...</option>
                            <option value="capsule" {{ old('dosage_form') == 'capsule' ? 'selected' : '' }}>Capsule</option>
                            <option value="softgel" {{ old('dosage_form') == 'softgel' ? 'selected' : '' }}>Softgel</option>
                            <option value="tablet" {{ old('dosage_form') == 'tablet' ? 'selected' : '' }}>Tablet</option>
                            <option value="powder" {{ old('dosage_form') == 'powder' ? 'selected' : '' }}>Powder</option>
                            <option value="liquid" {{ old('dosage_form') == 'liquid' ? 'selected' : '' }}>Liquid</option>
                            <option value="gummy" {{ old('dosage_form') == 'gummy' ? 'selected' : '' }}>Gummy</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Serving Size</label>
                        <input type="number" step="0.01" name="serving_size_value" value="{{ old('serving_size_value') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500/20 focus:border-yellow-600">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Serving Unit</label>
                        <input type="text" name="serving_size_unit" value="{{ old('serving_size_unit') }}" placeholder="e.g., capsule, ml, g"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500/20 focus:border-yellow-600">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Servings/Container</label>
                        <input type="number" min="0" name="servings_per_container" value="{{ old('servings_per_container') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500/20 focus:border-yellow-600">
                    </div>
                </div>
            </div>

            <!-- URLs -->
            <div class="bg-white rounded-xl border p-6">
                <h2 class="text-lg font-semibold mb-4" style="color: var(--charcoal);">URLs</h2>
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product URL</label>
                        <input type="url" name="product_url" value="{{ old('product_url') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500/20 focus:border-yellow-600">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Image URL</label>
                        <input type="url" name="image_url" value="{{ old('image_url') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500/20 focus:border-yellow-600">
                    </div>
                </div>
            </div>

            <!-- Description -->
            <div class="bg-white rounded-xl border p-6">
                <h2 class="text-lg font-semibold mb-4" style="color: var(--charcoal);">Description & Usage</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product Description</label>
                        <textarea name="product_description" rows="4"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500/20 focus:border-yellow-600">{{ old('product_description') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Suggested Use</label>
                        <textarea name="suggested_use" rows="2"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500/20 focus:border-yellow-600">{{ old('suggested_use') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Warnings</label>
                        <textarea name="warnings" rows="2"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500/20 focus:border-yellow-600">{{ old('warnings') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Advanced -->
            <div class="bg-white rounded-xl border p-6">
                <h2 class="text-lg font-semibold mb-4" style="color: var(--charcoal);">Advanced (JSON)</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Active Ingredients (JSON)</label>
                        <textarea name="active_ingredients" rows="3" placeholder='[{"name": "Vitamin D3", "amount": 1000, "unit": "IU"}]'
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500/20 focus:border-yellow-600 font-mono text-sm">{{ old('active_ingredients') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Certifications (JSON)</label>
                        <textarea name="certification_flags" rows="2" placeholder='["gmp", "third-party tested", "organic"]'
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500/20 focus:border-yellow-600 font-mono text-sm">{{ old('certification_flags') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex justify-end gap-4">
                <a href="{{ route('admin.index') }}" class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 text-sm font-medium text-white rounded-lg" style="background: var(--gold);">
                    Create Supplement
                </button>
            </div>
        </form>
    </main>
</body>
</html>
