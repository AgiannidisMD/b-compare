<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>B-COMPARE | Admin Dashboard</title>
    <script defer src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        gold: {
                            light: '#d4b896',
                            DEFAULT: '#c6a76b',
                            dark: '#c6a259',
                            darker: '#b08d4a'
                        },
                        charcoal: {
                            light: '#4a5058',
                            DEFAULT: '#353b41',
                            dark: '#202020',
                            darker: '#1a1a1a'
                        },
                        warm: {
                            gray: '#7a746e',
                            light: '#f8f6f3',
                            bg: '#faf9f7'
                        }
                    },
                    borderRadius: {
                        'card': '14px',
                        'button': '50px',
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@200;300;400;500;600;700&display=swap');
        * { font-family: 'Inter', sans-serif; }

        .transition-premium {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-gold {
            background: linear-gradient(135deg, #c6a76b 0%, #c6a259 100%);
            color: white;
            border-radius: 50px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-gold:hover {
            background: linear-gradient(135deg, #d4b896 0%, #c6a76b 100%);
            box-shadow: 0 4px 20px rgba(198, 167, 107, 0.4);
        }

        .input-premium {
            border: 1px solid #e8e6e3;
            border-radius: 14px;
            transition: all 0.2s ease;
        }

        .input-premium:focus {
            outline: none;
            border-color: #c6a76b;
            box-shadow: 0 0 0 3px rgba(198, 167, 107, 0.15);
        }

        .stat-card {
            background: white;
            border: 1px solid #e8e6e3;
            border-radius: 14px;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.05);
            transform: translateY(-2px);
        }

        .progress-bar {
            height: 8px;
            border-radius: 4px;
            background: #f0eeeb;
        }

        .progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.5s ease;
        }

        .supplement-row:hover {
            background: #faf9f7;
        }

        .product-image {
            width: 50px;
            height: 50px;
            object-fit: contain;
            border-radius: 8px;
            background: #f8f6f3;
        }

        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #f8f6f3;
        }

        ::-webkit-scrollbar-thumb {
            background: #c6a76b;
            border-radius: 3px;
        }
    </style>
</head>
<body class="bg-warm-bg">
    <div class="min-h-screen" x-data="dashboard()">
        <!-- Header -->
        <header class="bg-white border-b border-gray-100 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-gold to-gold-dark rounded-card flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-xl font-semibold text-charcoal tracking-tight">B-COMPARE</h1>
                            <p class="text-xs text-warm-gray font-light">Admin Dashboard</p>
                        </div>
                    </div>
                    <a href="/" class="text-sm text-warm-gray hover:text-gold flex items-center gap-2 transition-premium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        Back to Chat
                    </a>
                </div>
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-6 py-8">
            <!-- Stats Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="stat-card p-5">
                    <div class="text-xs text-warm-gray uppercase tracking-wide mb-2">Supplements</div>
                    <div class="text-2xl font-light text-charcoal" x-text="stats.total_supplements?.toLocaleString() || '-'"></div>
                </div>
                <div class="stat-card p-5">
                    <div class="text-xs text-warm-gray uppercase tracking-wide mb-2">Categories</div>
                    <div class="text-2xl font-light text-charcoal" x-text="stats.total_categories || '-'"></div>
                </div>
                <div class="stat-card p-5">
                    <div class="text-xs text-warm-gray uppercase tracking-wide mb-2">Conditions</div>
                    <div class="text-2xl font-light text-charcoal" x-text="stats.total_conditions || '-'"></div>
                </div>
                <div class="stat-card p-5">
                    <div class="text-xs text-warm-gray uppercase tracking-wide mb-2">Avg Score</div>
                    <div class="text-2xl font-light text-gold" x-text="stats.avg_overall_score || '-'"></div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Score Distribution -->
                <div class="stat-card p-6">
                    <h3 class="font-medium text-charcoal mb-5">Score Distribution</h3>
                    <template x-if="stats.score_distribution">
                        <div class="space-y-4">
                            <div>
                                <div class="flex justify-between text-sm mb-2">
                                    <span class="text-warm-gray">Excellent (8+)</span>
                                    <span class="font-medium text-charcoal" x-text="stats.score_distribution.excellent?.toLocaleString()"></span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill bg-emerald-500" :style="'width: ' + Math.min(100, (stats.score_distribution.excellent / stats.total_supplements * 100 * 10)) + '%'"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-sm mb-2">
                                    <span class="text-warm-gray">Very Good (7-8)</span>
                                    <span class="font-medium text-charcoal" x-text="stats.score_distribution.very_good?.toLocaleString()"></span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill bg-blue-500" :style="'width: ' + Math.min(100, (stats.score_distribution.very_good / stats.total_supplements * 100 * 3)) + '%'"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-sm mb-2">
                                    <span class="text-warm-gray">Good (6-7)</span>
                                    <span class="font-medium text-charcoal" x-text="stats.score_distribution.good?.toLocaleString()"></span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill bg-gold" :style="'width: ' + (stats.score_distribution.good / stats.total_supplements * 100) + '%'"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-sm mb-2">
                                    <span class="text-warm-gray">Fair (5-6)</span>
                                    <span class="font-medium text-charcoal" x-text="stats.score_distribution.fair?.toLocaleString()"></span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill bg-amber-500" :style="'width: ' + (stats.score_distribution.fair / stats.total_supplements * 100 * 5) + '%'"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-sm mb-2">
                                    <span class="text-warm-gray">Poor (&lt;5)</span>
                                    <span class="font-medium text-charcoal" x-text="stats.score_distribution.poor?.toLocaleString()"></span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill bg-red-400" :style="'width: ' + (stats.score_distribution.poor / stats.total_supplements * 100 * 5) + '%'"></div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Top Categories & Price Range -->
                <div class="stat-card p-6">
                    <h3 class="font-medium text-charcoal mb-5">Top Categories</h3>
                    <template x-if="stats.top_categories">
                        <div class="space-y-3">
                            <template x-for="cat in stats.top_categories" :key="cat.name">
                                <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                                    <div class="flex items-center gap-3">
                                        <div x-html="getCategoryIcon(cat.slug || cat.name.toLowerCase().replace(/[^a-z0-9]+/g, '-'))" class="text-gold"></div>
                                        <span class="text-sm text-charcoal" x-text="cat.name"></span>
                                    </div>
                                    <span class="text-sm font-medium text-gold" x-text="cat.product_count?.toLocaleString()"></span>
                                </div>
                            </template>
                        </div>
                    </template>

                    <template x-if="stats.price_range">
                        <div class="mt-6 pt-5 border-t border-gray-100">
                            <h4 class="text-sm font-medium text-charcoal mb-3">Price Range</h4>
                            <div class="grid grid-cols-3 gap-4 text-center">
                                <div>
                                    <div class="text-xs text-warm-gray">Min</div>
                                    <div class="text-sm font-medium text-charcoal" x-text="'€' + stats.price_range.min"></div>
                                </div>
                                <div>
                                    <div class="text-xs text-warm-gray">Avg</div>
                                    <div class="text-sm font-medium text-gold" x-text="'€' + stats.price_range.avg"></div>
                                </div>
                                <div>
                                    <div class="text-xs text-warm-gray">Max</div>
                                    <div class="text-sm font-medium text-charcoal" x-text="'€' + stats.price_range.max"></div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Supplement Browser -->
            <div class="stat-card">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="font-medium text-charcoal mb-5">Browse Supplements</h3>

                    <!-- Filters -->
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <input type="text" x-model="filters.search" @input.debounce.300ms="loadSupplements()"
                               placeholder="Search brand or title..."
                               class="input-premium px-4 py-2.5 text-sm">

                        <select x-model="filters.category_id" @change="loadSupplements()"
                                class="input-premium px-4 py-2.5 text-sm">
                            <option value="">All Categories</option>
                            <template x-for="cat in categories" :key="cat.id">
                                <option :value="cat.id" x-text="cat.icon + ' ' + cat.name"></option>
                            </template>
                        </select>

                        <select x-model="filters.min_score" @change="loadSupplements()"
                                class="input-premium px-4 py-2.5 text-sm">
                            <option value="">Any Score</option>
                            <option value="8">Score 8+ (Excellent)</option>
                            <option value="7">Score 7+ (Very Good)</option>
                            <option value="6">Score 6+ (Good)</option>
                        </select>

                        <select x-model="filters.sort" @change="loadSupplements()"
                                class="input-premium px-4 py-2.5 text-sm">
                            <option value="overall_recommendation_score">Overall Score</option>
                            <option value="efficacy_score">Efficacy</option>
                            <option value="quality_score">Quality</option>
                            <option value="value_score">Value</option>
                            <option value="bioavailability_score">Bioavailability</option>
                            <option value="current_price">Price</option>
                            <option value="rating">Rating</option>
                        </select>

                        <select x-model="filters.order" @change="loadSupplements()"
                                class="input-premium px-4 py-2.5 text-sm">
                            <option value="desc">Descending</option>
                            <option value="asc">Ascending</option>
                        </select>
                    </div>
                </div>

                <!-- Results -->
                <div class="p-6">
                    <template x-if="loading">
                        <div class="text-center py-12 text-warm-gray">
                            <div class="inline-flex gap-2">
                                <div class="w-2 h-2 bg-gold rounded-full animate-bounce"></div>
                                <div class="w-2 h-2 bg-gold rounded-full animate-bounce" style="animation-delay: 0.1s;"></div>
                                <div class="w-2 h-2 bg-gold rounded-full animate-bounce" style="animation-delay: 0.2s;"></div>
                            </div>
                        </div>
                    </template>

                    <template x-if="!loading && supplements.data">
                        <div>
                            <div class="text-sm text-warm-gray mb-4">
                                Showing <span class="text-charcoal font-medium" x-text="supplements.from"></span>-<span class="text-charcoal font-medium" x-text="supplements.to"></span>
                                of <span class="text-charcoal font-medium" x-text="supplements.total?.toLocaleString()"></span> supplements
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-gray-100">
                                            <th class="px-3 py-3 text-left text-xs font-medium text-warm-gray uppercase tracking-wide">Image</th>
                                            <th class="px-3 py-3 text-left text-xs font-medium text-warm-gray uppercase tracking-wide">Brand</th>
                                            <th class="px-3 py-3 text-left text-xs font-medium text-warm-gray uppercase tracking-wide">Title</th>
                                            <th class="px-3 py-3 text-right text-xs font-medium text-warm-gray uppercase tracking-wide">Price</th>
                                            <th class="px-3 py-3 text-right text-xs font-medium text-warm-gray uppercase tracking-wide">Rating</th>
                                            <th class="px-3 py-3 text-right text-xs font-medium text-warm-gray uppercase tracking-wide">Overall</th>
                                            <th class="px-3 py-3 text-right text-xs font-medium text-warm-gray uppercase tracking-wide">Efficacy</th>
                                            <th class="px-3 py-3 text-right text-xs font-medium text-warm-gray uppercase tracking-wide">Quality</th>
                                            <th class="px-3 py-3 text-right text-xs font-medium text-warm-gray uppercase tracking-wide">Value</th>
                                            <th class="px-3 py-3 text-right text-xs font-medium text-warm-gray uppercase tracking-wide">Bio.</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        <template x-for="s in supplements.data" :key="s.id">
                                            <tr class="supplement-row transition-premium">
                                                <td class="px-3 py-3">
                                                    <img :src="s.image_url || '/images/placeholder.svg'"
                                                         :alt="s.title"
                                                         class="product-image"
                                                         x-on:error="$event.target.src='/images/placeholder.svg'"
                                                         loading="lazy">
                                                </td>
                                                <td class="px-3 py-3 font-medium text-charcoal" x-text="s.brand"></td>
                                                <td class="px-3 py-3 text-warm-gray max-w-xs truncate" x-text="s.title"></td>
                                                <td class="px-3 py-3 text-right text-charcoal" x-text="s.price ? '€' + parseFloat(s.price).toFixed(2) : '-'"></td>
                                                <td class="px-3 py-3 text-right">
                                                    <span class="inline-flex items-center gap-1 text-warm-gray">
                                                        <svg class="w-3 h-3 text-gold" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                        </svg>
                                                        <span x-text="s.rating ? parseFloat(s.rating).toFixed(1) : '-'"></span>
                                                    </span>
                                                </td>
                                                <td class="px-3 py-3 text-right">
                                                    <span class="font-semibold"
                                                          :class="s.overall_score >= 8 ? 'text-emerald-600' : s.overall_score >= 7 ? 'text-blue-600' : 'text-charcoal'"
                                                          x-text="s.overall_score"></span>
                                                </td>
                                                <td class="px-3 py-3 text-right text-blue-600" x-text="s.efficacy_score"></td>
                                                <td class="px-3 py-3 text-right text-emerald-600" x-text="s.quality_score"></td>
                                                <td class="px-3 py-3 text-right text-violet-600" x-text="s.value_score"></td>
                                                <td class="px-3 py-3 text-right text-amber-600" x-text="s.bioavailability_score"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <div class="flex justify-between items-center mt-6 pt-6 border-t border-gray-100">
                                <button @click="prevPage()" :disabled="!supplements.prev_page_url"
                                        class="px-5 py-2 text-sm text-warm-gray hover:text-charcoal border border-gray-200 rounded-button disabled:opacity-40 disabled:cursor-not-allowed transition-premium">
                                    Previous
                                </button>
                                <span class="text-sm text-warm-gray">
                                    Page <span class="text-charcoal font-medium" x-text="supplements.current_page"></span>
                                    of <span class="text-charcoal font-medium" x-text="supplements.last_page"></span>
                                </span>
                                <button @click="nextPage()" :disabled="!supplements.next_page_url"
                                        class="px-5 py-2 text-sm text-warm-gray hover:text-charcoal border border-gray-200 rounded-button disabled:opacity-40 disabled:cursor-not-allowed transition-premium">
                                    Next
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-charcoal text-white py-6 mt-12">
            <div class="max-w-7xl mx-auto px-6 text-center">
                <p class="text-sm text-gray-400">B-COMPARE Professional Supplement Analysis Platform</p>
                <p class="text-xs text-gray-500 mt-2">Powered by <a href="https://www.stolosofficial.gr" target="_blank" rel="noopener" class="text-gold hover:text-gold-light transition-colors">stolosofficial</a></p>
            </div>
        </footer>
    </div>

    <script>
        // Professional SVG icons for categories
        const categoryIcons = {
            'omega-3-fish-oil': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 12c0 1.5-3.5 3-8 3s-8-1.5-8-3c0-2 2-4 4-4 1 0 2 .5 2 1.5S9 11 8 12c2-1 4-2 6-2 1.5 0 3 1 3 2.5S15 14 14 13c1 1 3 0 4-.5 1-.5 2-.5 2-.5z"/></svg>',
            'magnesium': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>',
            'vitamin-d': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>',
            'probiotics': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>',
            'collagen': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>',
            'protein-supplements': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>',
            'vitamin-c': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>',
            'coq10': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>',
            'b-vitamins': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
            'zinc': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>',
            'vitamin-a': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>',
            'calcium': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>',
            'iron': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>',
            'digestive-enzymes': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>',
            'amino-acids': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>',
            'electrolytes': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>',
            'joint-support': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>',
            'sleep-support': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>',
            'herbaladaptogens': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>',
            'fiber-supplements': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>',
        };

        function getCategoryIcon(slug) {
            return categoryIcons[slug] || '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>';
        }

        function dashboard() {
            return {
                stats: {},
                categories: [],
                supplements: {},
                loading: false,
                filters: {
                    search: '',
                    category_id: '',
                    min_score: '',
                    sort: 'overall_recommendation_score',
                    order: 'desc'
                },
                page: 1,

                async init() {
                    await this.loadStats();
                    await this.loadCategories();
                    await this.loadSupplements();
                },

                async loadStats() {
                    const response = await fetch('/api/dashboard/stats');
                    this.stats = await response.json();
                },

                async loadCategories() {
                    const response = await fetch('/api/categories');
                    const data = await response.json();
                    // Handle both array and {data: [...]} format
                    this.categories = Array.isArray(data) ? data : (data.data || []);
                },

                async loadSupplements() {
                    this.loading = true;
                    const params = new URLSearchParams({
                        page: this.page,
                        per_page: 20,
                        sort: this.filters.sort,
                        order: this.filters.order,
                    });

                    if (this.filters.search) params.append('search', this.filters.search);
                    if (this.filters.category_id) params.append('category_id', this.filters.category_id);
                    if (this.filters.min_score) params.append('min_score', this.filters.min_score);

                    const response = await fetch('/api/supplements?' + params.toString());
                    this.supplements = await response.json();
                    this.loading = false;
                },

                nextPage() {
                    if (this.supplements.next_page_url) {
                        this.page++;
                        this.loadSupplements();
                    }
                },

                prevPage() {
                    if (this.supplements.prev_page_url) {
                        this.page--;
                        this.loadSupplements();
                    }
                }
            };
        }
    </script>
</body>
</html>
