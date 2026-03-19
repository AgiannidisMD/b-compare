<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>VITA-COMPARE | Περιήγηση Συμπληρωμάτων</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --gold: #c6a76b;
            --gold-light: #d4b896;
            --gold-dark: #b08d4a;
            --charcoal: #1d1d1f;
            --charcoal-light: #424245;
            --warm-bg: #fbfaf8;
            --warm-gray: #86868b;
            --card-bg: #ffffff;
            --border: #e8e8ed;
            --border-hover: #d2d2d7;
        }
        * { font-family: 'Inter', -apple-system, sans-serif; -webkit-font-smoothing: antialiased; }
        body {
            background: var(--warm-bg);
            color: var(--charcoal);
            padding-left: env(safe-area-inset-left);
            padding-right: env(safe-area-inset-right);
            padding-bottom: env(safe-area-inset-bottom);
        }

        .animate-fade-in { animation: fadeIn 0.4s ease-out; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card {
            background: var(--card-bg);
            border-radius: 16px;
            border: 1px solid var(--border);
            transition: all 0.3s cubic-bezier(0.25, 0.1, 0.25, 1);
            cursor: pointer;
        }
        .card:hover {
            box-shadow: 0 8px 24px rgba(0,0,0,0.06);
            border-color: var(--gold);
        }
        @media (hover: hover) {
            .card:hover { transform: translateY(-2px); }
        }
        .card:active { transform: scale(0.98); }

        .tab-active {
            background: linear-gradient(180deg, var(--gold) 0%, var(--gold-dark) 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(198, 167, 107, 0.3);
        }
        .tab-inactive {
            background: var(--card-bg);
            color: var(--charcoal);
            border: 1px solid var(--border);
        }

        .score-badge {
            background: linear-gradient(135deg, var(--charcoal) 0%, #2d2d2d 100%);
            color: white;
            font-weight: 700;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .score-good { color: #34c759; }
        .score-mid { color: var(--gold); }
        .score-bad { color: #ff3b30; }

        .relevance-bar { height: 3px; border-radius: 2px; background: var(--border); }
        .relevance-fill { height: 100%; border-radius: 2px; background: var(--gold); }

        .btn-ai {
            background: linear-gradient(180deg, var(--gold) 0%, var(--gold-dark) 100%);
            color: white;
            border-radius: 980px;
            font-weight: 500;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(198, 167, 107, 0.3);
        }
        .btn-ai:active { transform: scale(0.97); }

        .red-flag-badge {
            background: #fff2f0;
            color: #ff3b30;
            border-radius: 8px;
            font-size: 11px;
            padding: 2px 8px;
            font-weight: 500;
        }

        /* Mobile brand filter as horizontal scroll */
        @media (max-width: 1023px) {
            .brand-sidebar { display: none; }
            .brand-chips { display: flex; overflow-x: auto; gap: 8px; padding-bottom: 8px; -webkit-overflow-scrolling: touch; }
            .brand-chips::-webkit-scrollbar { display: none; }
        }
        @media (min-width: 1024px) {
            .brand-chips-mobile { display: none; }
        }
    </style>
</head>
<body>
<div x-data="browsePage()" class="min-h-screen">

    <!-- Header -->
    <header class="sticky top-0 z-50 px-4 sm:px-6 py-3 sm:py-4" style="background: rgba(251,250,248,0.85); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);">
        <div class="max-w-6xl mx-auto flex items-center justify-between">
            <div>
                <h1 class="text-lg sm:text-xl font-semibold" style="letter-spacing: -0.02em;">VITA-COMPARE</h1>
                <p class="text-[11px] sm:text-xs" style="color: var(--warm-gray);">Περιήγηση Συμπληρωμάτων</p>
            </div>
            <a href="/chat" class="btn-ai px-3 sm:px-4 py-2 text-xs sm:text-sm">Ρώτα το AI</a>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 sm:px-6 py-5 sm:py-8">

        <!-- Title -->
        <div class="text-center mb-6 sm:mb-8">
            <h2 class="text-2xl sm:text-3xl lg:text-4xl font-semibold mb-1" style="letter-spacing: -0.03em;">
                Βρείτε το Ιδανικό Συμπλήρωμα
            </h2>
            <p class="text-xs sm:text-sm" style="color: var(--warm-gray);">
                Επιλέξτε και ξεκινήστε συνομιλία με το AI
            </p>
        </div>

        <!-- Tabs -->
        <div class="flex justify-center gap-2 sm:gap-3 mb-6 sm:mb-10">
            <button @click="activeTab = 'function'"
                    :class="activeTab === 'function' ? 'tab-active' : 'tab-inactive'"
                    class="px-3 sm:px-5 py-2 sm:py-2.5 rounded-full text-xs sm:text-sm font-medium transition-all">
                Ανά Στόχο
            </button>
            <button @click="activeTab = 'ingredient'"
                    :class="activeTab === 'ingredient' ? 'tab-active' : 'tab-inactive'"
                    class="px-3 sm:px-5 py-2 sm:py-2.5 rounded-full text-xs sm:text-sm font-medium transition-all">
                Ανά Συστατικό
            </button>
            <button @click="activeTab = 'brand'; loadBrands()"
                    :class="activeTab === 'brand' ? 'tab-active' : 'tab-inactive'"
                    class="px-3 sm:px-5 py-2 sm:py-2.5 rounded-full text-xs sm:text-sm font-medium transition-all">
                Ανά Μάρκα
            </button>
        </div>

        <!-- TAB 1: Health Functions — each card links directly to chat -->
        <div x-show="activeTab === 'function'" class="animate-fade-in">
            <div class="grid grid-cols-3 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4">
                <template x-for="func in functions" :key="func.slug">
                    <a :href="'/chat?goal=' + func.slug" class="card p-3 sm:p-5 text-center block">
                        <div class="mx-auto mb-2" style="color: var(--gold);" x-html="getIcon(func.slug, 'function')"></div>
                        <h3 class="font-semibold text-[11px] sm:text-sm leading-tight" x-text="func.name_el"></h3>
                        <p class="text-[10px] sm:text-xs mt-1 hidden sm:block" style="color: var(--warm-gray);" x-text="func.category_count + ' κατηγορίες'"></p>
                    </a>
                </template>
            </div>
        </div>

        <!-- TAB 2: Ingredients — each card links directly to chat -->
        <div x-show="activeTab === 'ingredient'" class="animate-fade-in">
            <div class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-5 gap-3 sm:gap-4">
                <template x-for="cat in categories" :key="cat.slug">
                    <a :href="'/chat?category=' + cat.slug" class="card p-3 sm:p-5 text-center block">
                        <div class="mx-auto mb-2" style="color: var(--gold);" x-html="getIcon(cat.slug, 'category')"></div>
                        <h3 class="font-semibold text-[11px] sm:text-sm leading-tight" x-text="cat.name"></h3>
                        <p class="text-[10px] sm:text-xs mt-1" style="color: var(--warm-gray);" x-text="cat.product_count + ' προϊόντα'"></p>
                    </a>
                </template>
            </div>
        </div>

        <!-- TAB 3: Brands — each card links directly to chat -->
        <div x-show="activeTab === 'brand'" class="animate-fade-in">
            <div x-show="brands.length === 0" class="text-center py-12">
                <p style="color: var(--warm-gray);">Φόρτωση μαρκών...</p>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
                <template x-for="brand in brands" :key="brand.brand">
                    <a :href="'/chat?brand=' + encodeURIComponent(brand.brand)" class="card p-3 sm:p-5 block">
                        <h3 class="font-semibold text-xs sm:text-sm" x-text="brand.brand"></h3>
                        <div class="flex items-center gap-2 mt-2 flex-wrap">
                            <span class="text-[10px] sm:text-xs font-medium px-2 py-0.5 rounded-full" style="background: var(--charcoal); color: white;" x-text="(brand.trust_score || '-') + '/10'"></span>
                            <span class="text-[10px] sm:text-xs" style="color: var(--warm-gray);" x-text="brand.product_count + ' προϊόντα'"></span>
                        </div>
                    </a>
                </template>
            </div>
        </div>


    </main>
</div>

<script>
const iconSvg = (d, size = 'w-6 h-6') => `<svg class="${size}" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="${d}"/></svg>`;

const categoryIcons = {
    'omega-3-fish-oil': iconSvg('M20 12c0 1.5-3.5 3-8 3s-8-1.5-8-3c0-2 2-4 4-4 1 0 2 .5 2 1.5S9 11 8 12c2-1 4-2 6-2 1.5 0 3 1 3 2.5S15 14 14 13c1 1 3 0 4-.5 1-.5 2-.5 2-.5z'),
    'magnesium': iconSvg('M13 10V3L4 14h7v7l9-11h-7z'),
    'vitamin-d': iconSvg('M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z'),
    'probiotics': iconSvg('M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z'),
    'collagen': iconSvg('M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z'),
    'protein-supplements': iconSvg('M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z'),
    'vitamin-c': iconSvg('M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'),
    'coq10': iconSvg('M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z'),
    'b-vitamins': iconSvg('M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'),
    'zinc': iconSvg('M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'),
    'vitamin-a': iconSvg('M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z'),
    'calcium': iconSvg('M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'),
    'iron': iconSvg('M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z'),
    'digestive-enzymes': iconSvg('M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z'),
    'amino-acids': iconSvg('M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z'),
    'electrolytes': iconSvg('M13 10V3L4 14h7v7l9-11h-7z'),
    'joint-support': iconSvg('M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'),
    'sleep-support': iconSvg('M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z'),
    'herbal-adaptogens': iconSvg('M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z'),
    'fiber-supplements': iconSvg('M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4'),
    'multivitamins': iconSvg('M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'),
    'creatine': iconSvg('M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z'),
    'greens-superfoods': iconSvg('M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3'),
    'nmn-nad': iconSvg('M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z'),
    'vitamins-other': iconSvg('M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z'),
    'other-supplements': iconSvg('M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'),
};

const functionIcons = {
    'sleep': iconSvg('M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z'),
    'digestion': iconSvg('M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z'),
    'immunity': iconSvg('M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'),
    'energy': iconSvg('M13 10V3L4 14h7v7l9-11h-7z'),
    'stress-relief': iconSvg('M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'),
    'joint-health': iconSvg('M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'),
    'heart-health': iconSvg('M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z'),
    'brain-focus': iconSvg('M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z'),
    'skin-hair-nails': iconSvg('M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z'),
    'bone-health': iconSvg('M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'),
    'weight-management': iconSvg('M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3'),
    'athletic-performance': iconSvg('M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z'),
    'eye-health': iconSvg('M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z'),
    'mood-support': iconSvg('M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'),
    'detox-liver': iconSvg('M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z'),
};

const defaultIcon = iconSvg('M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4');

function browsePage() {
    return {
        activeTab: 'function',
        functions: @json($functions),
        categories: @json($categories),
        brands: [],

        getIcon(slug, type) {
            if (type === 'function') return functionIcons[slug] || defaultIcon;
            return categoryIcons[slug] || defaultIcon;
        },

        async loadBrands() {
            if (this.brands.length > 0) return;
            const res = await fetch('/api/browse/brands');
            const data = await res.json();
            this.brands = data.data || [];
        },
    };
}
</script>
</body>
</html>
