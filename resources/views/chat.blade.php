<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>VITA-COMPARE | Ανάλυση Συμπληρωμάτων Διατροφής</title>
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

        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'SF Pro Display', sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        body {
            background: var(--warm-bg);
            color: var(--charcoal);
            padding-left: env(safe-area-inset-left);
            padding-right: env(safe-area-inset-right);
        }

        /* Apple-style subtle animations */
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Premium glassmorphism header */
        .header-glass {
            background: rgba(251, 250, 248, 0.8);
            backdrop-filter: saturate(180%) blur(20px);
            -webkit-backdrop-filter: saturate(180%) blur(20px);
        }

        /* Category cards - Apple product card style */
        .category-card {
            background: var(--card-bg);
            border-radius: 20px;
            border: 1px solid var(--border);
            transition: all 0.4s cubic-bezier(0.25, 0.1, 0.25, 1);
            cursor: pointer;
        }
        .category-card:hover {
            transform: scale(1.02) translateY(-4px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.08);
            border-color: var(--gold);
        }
        .category-card:active {
            transform: scale(0.98);
        }

        /* Premium button */
        .btn-premium {
            background: linear-gradient(180deg, var(--gold) 0%, var(--gold-dark) 100%);
            color: white;
            border-radius: 980px;
            font-weight: 500;
            letter-spacing: -0.01em;
            transition: all 0.3s cubic-bezier(0.25, 0.1, 0.25, 1);
            box-shadow: 0 2px 8px rgba(198, 167, 107, 0.3);
        }
        .btn-premium:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 16px rgba(198, 167, 107, 0.4);
        }
        .btn-premium:active {
            transform: scale(0.98);
        }

        /* Chat bubbles - refined */
        .bubble-user {
            background: var(--charcoal);
            color: white;
            border-radius: 20px 20px 6px 20px;
        }
        .bubble-assistant {
            background: var(--card-bg);
            color: var(--charcoal);
            border: 1px solid var(--border);
            border-radius: 20px 20px 20px 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }

        /* Recommendation cards - premium style */
        .rec-card {
            background: var(--card-bg);
            border-radius: 20px;
            border: 1px solid var(--border);
            transition: all 0.3s ease;
            overflow: hidden;
        }
        .rec-card:hover {
            box-shadow: 0 12px 32px rgba(0,0,0,0.08);
            border-color: var(--border-hover);
        }

        /* Rank badge - refined */
        .rank-badge {
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
            color: white;
            font-weight: 600;
            font-size: 13px;
            width: 32px;
            height: 32px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(198, 167, 107, 0.3);
        }

        /* Score display - Apple Watch style */
        .score-ring {
            background: linear-gradient(135deg, var(--charcoal) 0%, #2d2d2d 100%);
            color: white;
            font-weight: 700;
            font-size: 18px;
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            letter-spacing: -0.02em;
        }

        /* Score metric pills */
        .score-pill {
            background: var(--warm-bg);
            border-radius: 12px;
            padding: 10px 12px;
            text-align: center;
        }

        /* Quick reply chips - refined */
        .chip {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 980px;
            padding: 10px 18px;
            font-size: 14px;
            font-weight: 500;
            color: var(--charcoal);
            transition: all 0.25s ease;
            cursor: pointer;
        }
        .chip:hover {
            border-color: var(--gold);
            background: #fffcf7;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(198, 167, 107, 0.15);
        }

        /* Progress stepper */
        .step-circle {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 12px;
            transition: all 0.3s ease;
        }
        .step-active {
            background: var(--gold);
            color: white;
            box-shadow: 0 2px 8px rgba(198, 167, 107, 0.4);
        }
        .step-inactive {
            background: #e8e8ed;
            color: #86868b;
        }

        /* Input field - premium */
        .input-premium {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 14px;
            transition: all 0.2s ease;
        }
        .input-premium:focus {
            outline: none;
            border-color: var(--gold);
            box-shadow: 0 0 0 4px rgba(198, 167, 107, 0.1);
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb {
            background: #d1d1d6;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover { background: #b0b0b5; }

        /* Loading dots */
        .loading-dot {
            width: 8px;
            height: 8px;
            background: var(--gold);
            border-radius: 50%;
            animation: loadingPulse 1.4s ease-in-out infinite;
        }
        .loading-dot:nth-child(2) { animation-delay: 0.2s; }
        .loading-dot:nth-child(3) { animation-delay: 0.4s; }
        @keyframes loadingPulse {
            0%, 80%, 100% { transform: scale(0.6); opacity: 0.5; }
            40% { transform: scale(1); opacity: 1; }
        }

        /* Mobile-specific improvements */
        @media (max-width: 640px) {
            /* Better touch targets */
            .category-card {
                min-height: 100px;
            }
            .chip {
                padding: 12px 16px;
                font-size: 13px;
            }
            .btn-premium {
                padding: 14px 20px;
            }
            /* Smaller score ring on mobile */
            .score-ring {
                width: 48px;
                height: 48px;
                font-size: 16px;
                border-radius: 12px;
            }
            /* Compact rank badge */
            .rank-badge {
                width: 28px;
                height: 28px;
                font-size: 12px;
                border-radius: 8px;
            }
            /* Fix chat input area for mobile keyboards */
            .input-premium {
                font-size: 16px; /* Prevents iOS zoom */
            }
        }

        /* Safe area for bottom input on notched phones */
        @supports (padding-bottom: env(safe-area-inset-bottom)) {
            .bottom-input-area {
                padding-bottom: calc(16px + env(safe-area-inset-bottom));
            }
        }
    </style>
</head>
<body>
    <div class="min-h-screen flex flex-col" x-data="chatApp()">
        <!-- Header - Glassmorphism -->
        <header class="header-glass sticky top-0 z-50 border-b border-gray-200/50">
            <div class="max-w-6xl mx-auto px-5 sm:px-8 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <!-- Back button -->
                        <template x-if="selectedCategory">
                            <button @click="resetChat()"
                                    class="w-10 h-10 rounded-full flex items-center justify-center hover:bg-gray-100 transition-colors">
                                <svg class="w-5 h-5" style="color: var(--charcoal);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                            </button>
                        </template>

                        <!-- Logo -->
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl flex items-center justify-center" style="background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-lg font-semibold tracking-tight" style="color: var(--charcoal);">VITA-COMPARE</h1>
                                <p class="text-xs" style="color: var(--warm-gray);">Ανάλυση Συμπληρωμάτων</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="/" class="text-sm font-medium hover:opacity-70 transition-opacity px-3 py-1.5 rounded-full" style="color: var(--warm-gray); border: 1px solid var(--border);">
                            Περιήγηση
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1">
            <!-- No Category Selected: Direct chat -->
            <template x-if="!selectedCategory">
                <div class="flex flex-col h-[calc(100vh-73px)] animate-fade-in">
                    <div class="flex-1 overflow-y-auto px-5 sm:px-8 py-6">
                        <div class="max-w-3xl mx-auto">
                            <!-- Welcome message -->
                            <div class="text-center py-12 sm:py-20">
                                <h2 class="text-2xl sm:text-3xl font-semibold mb-3" style="color: var(--charcoal); letter-spacing: -0.02em;">
                                    Πώς μπορώ να σας βοηθήσω;
                                </h2>
                                <p class="text-sm sm:text-base mb-8" style="color: var(--warm-gray);">
                                    Ρωτήστε με για οποιοδήποτε συμπλήρωμα ή περιγράψτε τι χρειάζεστε
                                </p>
                                <div class="flex flex-wrap justify-center gap-2">
                                    <button @click="userInput = 'Θέλω μαγνήσιο για ύπνο'; sendMessage()" class="px-4 py-2 rounded-full text-sm transition-all" style="background: var(--card-bg); border: 1px solid var(--border); color: var(--charcoal);">Μαγνήσιο για ύπνο</button>
                                    <button @click="userInput = 'Τι ωμέγα-3 προτείνετε;'; sendMessage()" class="px-4 py-2 rounded-full text-sm transition-all" style="background: var(--card-bg); border: 1px solid var(--border); color: var(--charcoal);">Ωμέγα-3</button>
                                    <button @click="userInput = 'Έχω PCOS, τι να πάρω;'; sendMessage()" class="px-4 py-2 rounded-full text-sm transition-all" style="background: var(--card-bg); border: 1px solid var(--border); color: var(--charcoal);">PCOS</button>
                                    <button @click="userInput = 'Θέλω βιταμίνη D'; sendMessage()" class="px-4 py-2 rounded-full text-sm transition-all" style="background: var(--card-bg); border: 1px solid var(--border); color: var(--charcoal);">Βιταμίνη D</button>
                                    <button @click="userInput = 'Σύγκρινε Thorne vs NOW Foods'; sendMessage()" class="px-4 py-2 rounded-full text-sm transition-all" style="background: var(--card-bg); border: 1px solid var(--border); color: var(--charcoal);">Σύγκριση μαρκών</button>
                                </div>
                            </div>

                            <!-- Messages (appear after first message) -->
                            <template x-for="(msg, idx) in messages" :key="idx">
                                <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'" class="animate-fade-in mb-4">
                                    <div :class="msg.role === 'user' ? 'bubble-user' : 'bubble-assistant'"
                                         class="px-5 py-3 max-w-[85%] sm:max-w-[75%] text-[15px] leading-relaxed"
                                         x-html="formatMessage(msg.content)"></div>
                                </div>
                            </template>

                            <!-- Loading -->
                            <template x-if="loading">
                                <div class="flex justify-start">
                                    <div class="bubble-assistant px-6 py-4 flex items-center gap-2">
                                        <div class="loading-dot"></div>
                                        <div class="loading-dot"></div>
                                        <div class="loading-dot"></div>
                                    </div>
                                </div>
                            </template>

                            <!-- Smart Follow-up Chips -->
                            <template x-if="!loading && messages.length > 0 && getSmartChips().length > 0">
                                <div class="pt-3 animate-fade-in">
                                    <div class="flex flex-wrap gap-2">
                                        <template x-for="(chip, idx) in getSmartChips()" :key="idx">
                                            <button @click="userInput = chip; sendMessage()"
                                                    class="px-3 py-1.5 rounded-full text-xs transition-all"
                                                    style="background: var(--card-bg); border: 1px solid var(--border); color: var(--charcoal);"
                                                    x-text="chip">
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Input bar -->
                    <div class="border-t px-4 sm:px-8 py-4" style="background: var(--card-bg); border-color: var(--border);">
                        <div class="max-w-3xl mx-auto">
                            <form @submit.prevent="sendMessage()" class="flex gap-3">
                                <input x-model="userInput"
                                       type="text"
                                       placeholder="Γράψτε την ερώτησή σας..."
                                       class="flex-1 px-5 py-3 rounded-2xl text-[15px] outline-none transition-all"
                                       style="background: var(--warm-bg); border: 1px solid var(--border); color: var(--charcoal);"
                                       :disabled="loading">
                                <button type="submit"
                                        :disabled="!userInput.trim() || loading"
                                        class="btn-premium px-5 py-3 text-sm disabled:opacity-50">
                                    Αποστολή
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Chat View -->
            <template x-if="selectedCategory">
                <div class="flex flex-col h-[calc(100vh-73px)] animate-fade-in">
                    <!-- Category Header with Progress -->
                    <div class="border-b px-5 sm:px-8 py-4" style="background: var(--card-bg); border-color: var(--border);">
                        <div class="max-w-4xl mx-auto">
                            <!-- Category Info -->
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div x-html="getCategoryIcon(selectedCategory.slug)" style="color: var(--gold);"></div>
                                    <div>
                                        <h3 class="font-semibold" style="color: var(--charcoal);" x-text="selectedCategory.name"></h3>
                                        <p class="text-xs" style="color: var(--warm-gray);" x-text="selectedCategory.product_count.toLocaleString() + ' προϊόντα διαθέσιμα'"></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Progress Steps -->
                            <div class="flex items-center justify-center gap-3">
                                <div class="flex items-center gap-2">
                                    <div class="step-circle step-active">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    </div>
                                    <span class="text-xs font-medium" style="color: var(--gold);">Κατηγορία</span>
                                </div>
                                <div class="w-8 h-px" style="background: var(--border);"></div>
                                <div class="flex items-center gap-2">
                                    <div class="step-circle" :class="messages.filter(m => m.role === 'user').length > 0 ? 'step-active' : 'step-inactive'">
                                        <template x-if="messages.filter(m => m.role === 'user').length > 0">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        </template>
                                        <template x-if="messages.filter(m => m.role === 'user').length === 0">
                                            <span>2</span>
                                        </template>
                                    </div>
                                    <span class="text-xs font-medium" :style="messages.filter(m => m.role === 'user').length > 0 ? 'color: var(--gold)' : 'color: var(--warm-gray)'">Ανάγκες</span>
                                </div>
                                <div class="w-8 h-px" style="background: var(--border);"></div>
                                <div class="flex items-center gap-2">
                                    <div class="step-circle" :class="recommendations ? 'step-active' : 'step-inactive'">
                                        <template x-if="recommendations">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        </template>
                                        <template x-if="!recommendations">
                                            <span>3</span>
                                        </template>
                                    </div>
                                    <span class="text-xs font-medium" :style="recommendations ? 'color: var(--gold)' : 'color: var(--warm-gray)'">Προτάσεις</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Messages Area -->
                    <div class="flex-1 overflow-y-auto px-5 sm:px-8 py-6" id="chat-box">
                        <div class="max-w-3xl mx-auto space-y-4">
                            <!-- Messages -->
                            <template x-for="(msg, idx) in messages" :key="idx">
                                <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'" class="animate-fade-in">
                                    <div :class="msg.role === 'user' ? 'bubble-user' : 'bubble-assistant'"
                                         class="px-5 py-3 max-w-[85%] sm:max-w-[75%] text-[15px] leading-relaxed"
                                         x-html="formatMessage(msg.content)"></div>
                                </div>
                            </template>

                            <!-- Loading -->
                            <template x-if="loading">
                                <div class="flex justify-start">
                                    <div class="bubble-assistant px-6 py-4 flex items-center gap-2">
                                        <div class="loading-dot"></div>
                                        <div class="loading-dot"></div>
                                        <div class="loading-dot"></div>
                                    </div>
                                </div>
                            </template>

                            <!-- Quick Reply Chips -->
                            <template x-if="showQuickReplies && !loading && !recommendations && messages.length > 0 && getQuickReplies().length > 0">
                                <div class="pt-2 animate-fade-in">
                                    <p class="text-xs mb-3" style="color: var(--warm-gray);">Επιλέξτε ή πληκτρολογήστε</p>
                                    <div class="flex flex-wrap gap-2">
                                        <template x-for="(chip, idx) in getQuickReplies()" :key="idx">
                                            <button x-show="chip && chip.trim() !== ''"
                                                    @click="sendQuickReply(chip)"
                                                    class="chip"
                                                    x-text="chip">
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <!-- Recommendations -->
                            <template x-if="recommendations && recommendations.recommendations">
                                <div class="pt-6 animate-fade-in">
                                    <div class="flex items-center gap-2 mb-6">
                                        <div class="w-1 h-6 rounded-full" style="background: var(--gold);"></div>
                                        <h3 class="text-lg font-semibold" style="color: var(--charcoal);">Κορυφαίες Προτάσεις</h3>
                                    </div>

                                    <div class="space-y-4">
                                        <template x-for="(rec, idx) in recommendations.recommendations.slice(0, 5)" :key="idx">
                                            <div class="rec-card p-4 sm:p-5">
                                                <div class="flex flex-col sm:flex-row gap-4">
                                                    <!-- Rank & Image -->
                                                    <div class="flex sm:flex-col items-center gap-3">
                                                        <div class="rank-badge" x-text="rec.rank || (idx + 1)"></div>
                                                        <img :src="rec.image_url || '/images/placeholder.svg'"
                                                             :alt="rec.title"
                                                             class="w-16 h-16 sm:w-20 sm:h-20 object-contain rounded-xl"
                                                             style="background: var(--warm-bg);"
                                                             x-on:error="$event.target.src='/images/placeholder.svg'"
                                                             loading="lazy">
                                                        <!-- Mobile score -->
                                                        <div class="score-ring flex-shrink-0 sm:hidden" x-text="rec.score ? parseFloat(rec.score).toFixed(1) : '-'"></div>
                                                    </div>

                                                    <!-- Info -->
                                                    <div class="flex-1 min-w-0">
                                                        <div class="flex items-start justify-between gap-3 mb-2">
                                                            <div>
                                                                <h4 class="font-semibold text-sm sm:text-base" style="color: var(--charcoal);" x-text="rec.brand"></h4>
                                                                <p class="text-xs sm:text-sm line-clamp-2" style="color: var(--warm-gray);" x-text="rec.title"></p>
                                                            </div>
                                                            <!-- Desktop score -->
                                                            <div class="score-ring flex-shrink-0 hidden sm:flex" x-text="rec.score ? parseFloat(rec.score).toFixed(1) : '-'"></div>
                                                        </div>

                                                                <!-- Score Pills -->
                                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-4">
                                                            <div class="score-pill">
                                                                <div class="text-[10px] uppercase tracking-wide mb-1" style="color: var(--warm-gray);">Δόση</div>
                                                                <div class="text-sm font-semibold" :style="'color:' + ((rec.clinical_dose_score || 0) >= 7 ? '#34c759' : (rec.clinical_dose_score || 0) >= 4 ? '#c6a76b' : '#ff3b30')" x-text="rec.clinical_dose_score || '-'"></div>
                                                            </div>
                                                            <div class="score-pill">
                                                                <div class="text-[10px] uppercase tracking-wide mb-1" style="color: var(--warm-gray);">Απορρόφηση</div>
                                                                <div class="text-sm font-semibold" style="color: #f59e0b;" x-text="rec.bioavailability_score || '-'"></div>
                                                            </div>
                                                            <div class="score-pill">
                                                                <div class="text-[10px] uppercase tracking-wide mb-1" style="color: var(--warm-gray);">Ποιότητα</div>
                                                                <div class="text-sm font-semibold" style="color: #10b981;" x-text="rec.quality_score || '-'"></div>
                                                            </div>
                                                            <div class="score-pill">
                                                                <div class="text-[10px] uppercase tracking-wide mb-1" style="color: var(--warm-gray);">Μάρκα</div>
                                                                <div class="text-sm font-semibold" style="color: #8b5cf6;" x-text="rec.brand_trust_score || '-'"></div>
                                                            </div>
                                                        </div>

                                                        <!-- Red Flags -->
                                                        <template x-if="rec.red_flags && rec.red_flags.length > 0">
                                                            <div class="mt-3 p-2 rounded-xl" style="background: #fff2f0;">
                                                                <template x-for="flag in rec.red_flags" :key="flag">
                                                                    <p class="text-[11px] sm:text-xs" style="color: #ff3b30;" x-text="flag"></p>
                                                                </template>
                                                            </div>
                                                        </template>

                                                        <!-- Category Rank -->
                                                        <div x-show="rec.category_rank" class="mt-2">
                                                            <span class="text-[10px] sm:text-xs px-2 py-0.5 rounded-full" style="background: var(--warm-bg); color: var(--warm-gray);"
                                                                  x-text="'#' + rec.category_rank + ' στην κατηγορία'"></span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Why Best -->
                                                <template x-if="rec.why_best">
                                                    <div class="mt-4 pt-4" style="border-top: 1px solid var(--border);">
                                                        <p class="text-sm leading-relaxed" style="color: var(--charcoal-light);">
                                                            <span class="font-medium" style="color: var(--charcoal);">Γιατί το προτείνουμε:</span>
                                                            <span x-html="formatMessage(rec.why_best)"></span>
                                                        </p>
                                                    </div>
                                                </template>

                                                <!-- Dosage -->
                                                <template x-if="rec.dosage">
                                                    <p class="text-sm mt-3" style="color: var(--gold);">
                                                        <span class="font-medium">Δοσολογία:</span>
                                                        <span x-html="formatMessage(rec.dosage)"></span>
                                                    </p>
                                                </template>

                                                <!-- Expandable Details -->
                                                <div x-data="{ showDetails: false }" class="mt-3">
                                                    <button @click="showDetails = !showDetails" class="text-xs font-medium flex items-center gap-1" style="color: var(--gold);">
                                                        <span x-text="showDetails ? 'Κλείσιμο' : 'Αναλυτικά Στοιχεία'"></span>
                                                        <svg :class="showDetails ? 'rotate-180' : ''" class="w-3 h-3 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                                    </button>
                                                    <div x-show="showDetails" x-transition class="mt-3 p-3 rounded-xl text-xs space-y-2" style="background: var(--warm-bg);">
                                                        <div class="grid grid-cols-2 gap-2">
                                                            <div><span style="color: var(--warm-gray);">Μορφή:</span> <strong x-text="rec.dosage_form || '-'"></strong></div>
                                                            <div><span style="color: var(--warm-gray);">Δόσεις:</span> <strong x-text="rec.servings_per_container || '-'"></strong></div>
                                                            <div><span style="color: var(--warm-gray);">Αποτελεσματ.:</span> <strong x-text="rec.efficacy_score || '-'"></strong>/10</div>
                                                            <div><span style="color: var(--warm-gray);">Σύνθεση:</span> <strong x-text="rec.formulation_score || '-'"></strong>/10</div>
                                                        </div>
                                                        <template x-if="rec.active_ingredients && rec.active_ingredients.length > 0">
                                                            <div>
                                                                <p style="color: var(--warm-gray);" class="mb-1">Ενεργά συστατικά:</p>
                                                                <template x-for="ing in rec.active_ingredients.slice(0, 5)" :key="ing.name">
                                                                    <span class="inline-block px-2 py-0.5 mr-1 mb-1 rounded-full" style="background: var(--card-bg); border: 1px solid var(--border);" x-text="ing.name + (ing.amount ? ' ' + ing.amount + (ing.unit || '') : '')"></span>
                                                                </template>
                                                            </div>
                                                        </template>
                                                        <template x-if="rec.certification_flags && rec.certification_flags.length > 0">
                                                            <div>
                                                                <p style="color: var(--warm-gray);" class="mb-1">Πιστοποιήσεις:</p>
                                                                <template x-for="cert in rec.certification_flags.slice(0, 4)" :key="cert">
                                                                    <span class="inline-block px-2 py-0.5 mr-1 mb-1 rounded-full text-[10px]" style="background: #f0fdf4; color: #16a34a;" x-text="cert"></span>
                                                                </template>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>

                                                <!-- CTA -->
                                                <template x-if="rec.product_url">
                                                    <div class="mt-4 pt-4" style="border-top: 1px solid var(--border);">
                                                        <a :href="rec.product_url"
                                                           target="_blank"
                                                           rel="noopener noreferrer"
                                                           class="btn-premium w-full sm:w-auto px-5 py-2.5 text-sm inline-flex items-center justify-center gap-2">
                                                            <span>Δείτε το Προϊόν</span>
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                                            </svg>
                                                        </a>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>

                                    <!-- Comparison Summary -->
                                    <template x-if="recommendations.comparison">
                                        <div class="mt-6 p-5 rounded-2xl" style="background: var(--warm-bg); border: 1px solid var(--border);">
                                            <h4 class="font-semibold mb-2" style="color: var(--charcoal);">Συγκριτική Ανάλυση</h4>
                                            <p class="text-sm leading-relaxed" style="color: var(--charcoal-light);" x-text="recommendations.comparison"></p>
                                        </div>
                                    </template>

                                    <!-- Disclaimer -->
                                    <p class="text-xs mt-6 text-center" style="color: var(--warm-gray);">
                                        Τα συμπληρώματα δεν αντικαθιστούν την ιατρική συμβουλή. Συμβουλευτείτε τον γιατρό σας.
                                    </p>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Input Area -->
                    <div class="bottom-input-area border-t px-5 sm:px-8 py-4" style="background: var(--card-bg); border-color: var(--border);">
                        <div class="max-w-3xl mx-auto flex items-center gap-3">
                            <input @keyup.enter="sendMessage()"
                                   x-model="userInput"
                                   type="text"
                                   placeholder="Περιγράψτε τις ανάγκες σας..."
                                   class="input-premium flex-1 px-4 sm:px-5 py-3 sm:py-3.5 text-[15px]"
                                   :disabled="loading">
                            <button @click="sendMessage()"
                                    :disabled="loading || !userInput.trim()"
                                    class="btn-premium w-11 h-11 sm:w-12 sm:h-12 flex items-center justify-center disabled:opacity-40 disabled:cursor-not-allowed flex-shrink-0">
                                <svg x-show="!loading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                </svg>
                                <span x-show="loading" class="text-sm">...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </main>

        <!-- Footer (category view only) -->
        <template x-if="!selectedCategory">
            <footer class="py-8 text-center" style="background: var(--charcoal);">
                <p class="text-sm" style="color: #86868b;">VITA-COMPARE Ανάλυση Συμπληρωμάτων</p>
                <p class="text-xs mt-2" style="color: #6e6e73;">
                    Από την <a href="https://www.stolosofficial.gr" target="_blank" class="hover:underline" style="color: var(--gold);">stolosofficial</a>
                </p>
            </footer>
        </template>
    </div>

    <script>
        const categoryIcons = {
            'omega-3-fish-oil': '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 12c0 1.5-3.5 3-8 3s-8-1.5-8-3c0-2 2-4 4-4 1 0 2 .5 2 1.5S9 11 8 12c2-1 4-2 6-2 1.5 0 3 1 3 2.5S15 14 14 13c1 1 3 0 4-.5 1-.5 2-.5 2-.5z"/></svg>',
            'magnesium': '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>',
            'vitamin-d': '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>',
            'probiotics': '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>',
            'collagen': '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>',
            'protein-supplements': '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>',
            'vitamin-c': '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>',
            'coq10': '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>',
            'b-vitamins': '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
            'zinc': '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>',
            'vitamin-a': '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>',
            'calcium': '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>',
            'iron': '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>',
            'digestive-enzymes': '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>',
            'amino-acids': '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>',
            'electrolytes': '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>',
            'joint-support': '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>',
            'sleep-support': '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>',
            'herbaladaptogens': '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>',
            'fiber-supplements': '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>',
        };

        const categoryQuickReplies = {
            'magnesium': ['Για καλύτερο ύπνο', 'Για μείωση άγχους', 'Για μυϊκές κράμπες', 'Έχω διαβήτη'],
            'omega-3-fish-oil': ['Για την καρδιά', 'Για τον εγκέφαλο', 'Για φλεγμονές', 'Για χοληστερίνη'],
            'vitamin-d': ['Για τα οστά', 'Για το ανοσοποιητικό', 'Έχω έλλειψη', 'Για ενέργεια'],
            'probiotics': ['Για πέψη', 'Για φούσκωμα', 'Για ανοσοποιητικό', 'Μετά από αντιβιοτικά'],
            'collagen': ['Για το δέρμα', 'Για τις αρθρώσεις', 'Για τα μαλλιά', 'Αντιγήρανση'],
            'vitamin-c': ['Για κρυολόγημα', 'Για το ανοσοποιητικό', 'Για το δέρμα', 'Αντιοξειδωτικό'],
            'coq10': ['Για την καρδιά', 'Για ενέργεια', 'Παίρνω στατίνες', 'Αντιγήρανση'],
            'b-vitamins': ['Για ενέργεια', 'Για το νευρικό σύστημα', 'Για στρες', 'Είμαι vegan'],
            'zinc': ['Για ανοσοποιητικό', 'Για το δέρμα', 'Για τα μαλλιά', 'Για γονιμότητα'],
            'iron': ['Έχω αναιμία', 'Για ενέργεια', 'Είμαι έγκυος', 'Κουράζομαι εύκολα'],
            'calcium': ['Για τα οστά', 'Οστεοπόρωση', 'Είμαι σε εμμηνόπαυση', 'Δεν πίνω γάλα'],
            'sleep-support': ['Δυσκολεύομαι να κοιμηθώ', 'Ξυπνάω τη νύχτα', 'Αϋπνία', 'Θέλω φυσική λύση'],
            'joint-support': ['Πόνος στα γόνατα', 'Αρθρίτιδα', 'Αθλούμαι', 'Για χόνδρους'],
            'digestive-enzymes': ['Δυσπεψία', 'Φούσκωμα', 'Μετά το φαγητό', 'Δυσανεξία'],
            'protein-supplements': ['Για μυϊκή μάζα', 'Μετά την προπόνηση', 'Θέλω vegan', 'Για απώλεια βάρους'],
            'fiber-supplements': ['Για δυσκοιλιότητα', 'Για πέψη', 'Για χοληστερίνη', 'Για κορεσμό'],
            'amino-acids': ['Για μύες', 'Για αποκατάσταση', 'BCAAs', 'Για ενέργεια'],
            'electrolytes': ['Για αφυδάτωση', 'Μετά την άσκηση', 'Για κράμπες', 'Keto δίαιτα'],
            'herbaladaptogens': ['Για στρες', 'Για ενέργεια', 'Για συγκέντρωση', 'Φυσική λύση'],
            'vitamin-a': ['Για την όραση', 'Για το δέρμα', 'Για ανοσοποιητικό', 'Αντιοξειδωτικό'],
            'default': ['Για γενική υγεία', 'Έχω συγκεκριμένο πρόβλημα', 'Θέλω σύσταση', 'Για ενέργεια']
        };

        function getCategoryIcon(slug) {
            return categoryIcons[slug] || '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>';
        }

        function formatMessage(text) {
            if (!text) return '';
            // Strip markdown and convert to clean HTML
            return text
                .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')     // **bold** -> <strong>
                .replace(/\*(.+?)\*/g, '<em>$1</em>')                  // *italic* -> <em>
                .replace(/^[•\-]\s*/gm, '&bull; ')                     // bullet points -> clean dots
                .replace(/^#{1,3}\s+(.+)$/gm, '<strong>$1</strong>')   // headers -> bold
                .replace(/`(.+?)`/g, '$1')                             // `code` -> plain text
                .replace(/\n/g, '<br>')                                // newlines -> line breaks
                ;
        }

        function chatApp() {
            return {
                sessionId: 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
                categories: @json($categories),
                selectedCategory: null,
                messages: [],
                userInput: '',
                loading: false,
                recommendations: null,
                showQuickReplies: true,
                preselectedCategory: @json($preselectedCategory ?? null),
                preselectedSupplement: @json($preselectedSupplement ?? null),
                preselectedGoal: @json($preselectedGoal ?? null),
                preselectedBrand: @json($preselectedBrand ?? null),
                lastAssistantMessage: '',

                getSmartChips() {
                    if (this.messages.length === 0) return [];
                    // Find last REAL assistant message (skip errors)
                    const lastMsg = [...this.messages].reverse().find(m =>
                        m.role === 'assistant' &&
                        m.content &&
                        !m.content.includes('Αποτυχία σύνδεσης') &&
                        !m.content.includes('Παρουσιάστηκε σφάλμα') &&
                        m.content.length > 20
                    );
                    if (!lastMsg) return [];
                    const text = lastMsg.content.toLowerCase();

                    // Context rules: keyword patterns -> relevant follow-up chips
                    const rules = [
                        // Digestion
                        { match: ['πέψη', 'πεπτικ', 'έντερ', 'digest', 'στομάχ'], chips: ['Φούσκωμα', 'Δυσκοιλιότητα', 'Βαριά πέψη', 'Μετά από αντιβιοτικά'] },
                        // Sleep
                        { match: ['ύπνο', 'sleep', 'αϋπνία', 'μελατονίνη'], chips: ['Δυσκολεύομαι να κοιμηθώ', 'Ξυπνάω τη νύχτα', 'Χωρίς μελατονίνη', 'Θέλω φυσική λύση'] },
                        // Magnesium
                        { match: ['μαγνήσι', 'magnesium'], chips: ['Για ύπνο', 'Για κράμπες', 'Για άγχος', 'Ποια μορφή;'] },
                        // Omega-3
                        { match: ['ωμέγα', 'omega', 'ιχθυέλαιο', 'epa', 'dha'], chips: ['Για καρδιά', 'Για εγκέφαλο', 'Για φλεγμονές', 'Triglyceride ή Ethyl Ester;'] },
                        // PCOS / Inositol
                        { match: ['pcos', 'ινοσιτόλη', 'inositol', 'ορμον', 'ωοθηκ'], chips: ['Myo-inositol', 'Αναλογία 40:1', 'Σκόνη ή κάψουλα;', 'Με φολικό;'] },
                        // Vitamin D
                        { match: ['βιταμίνη d', 'vitamin d', 'cholecalciferol'], chips: ['Πόσα IU;', 'D3 ή D2;', 'Μαζί με K2;', 'Έχω έλλειψη'] },
                        // Vitamin C
                        { match: ['βιταμίνη c', 'vitamin c', 'ασκορβικ'], chips: ['Λιποσωμική;', 'Πόσα mg;', 'Για ανοσοποιητικό', 'Για δέρμα'] },
                        // Iron
                        { match: ['σίδηρο', 'iron', 'αναιμία', 'ferrous'], chips: ['Ποια μορφή;', 'Bisglycinate ή sulfate;', 'Με βιταμίνη C;', 'Έχω αναιμία'] },
                        // Zinc
                        { match: ['ψευδάργυρο', 'zinc'], chips: ['Picolinate ή oxide;', 'Για ανοσοποιητικό', 'Πόσα mg;', 'Χρειάζεται χαλκός;'] },
                        // Collagen
                        { match: ['κολλαγόνο', 'collagen', 'πεπτίδ'], chips: ['Για δέρμα', 'Για αρθρώσεις', 'Hydrolyzed;', 'Τύπος I ή II;'] },
                        // Probiotics
                        { match: ['προβιοτικ', 'probiotic', 'lactobacillus', 'bifidobacterium'], chips: ['Πόσα CFU;', 'Για φούσκωμα', 'Ποια στελέχη;', 'Μετά αντιβιοτικά'] },
                        // B-Vitamins
                        { match: ['βιταμίνη b', 'b-complex', 'b12', 'φολικό'], chips: ['Methylated μορφή;', 'Για ενέργεια', 'Για νευρικό σύστημα', 'Folic acid ή folate;'] },
                        // CoQ10
                        { match: ['coq10', 'ubiquinol', 'ubiquinone', 'συνένζυμο'], chips: ['Ubiquinol ή ubiquinone;', 'Για καρδιά', 'Παίρνω στατίνες', 'Πόσα mg;'] },
                        // Berberine
                        { match: ['βερβερίνη', 'berberine'], chips: ['Για σάκχαρο', 'Για χοληστερίνη', 'Πόσα mg;', 'Αντενδείξεις;'] },
                        // Mushrooms
                        { match: ['μανιτάρ', 'lion.*mane', 'reishi', 'cordyceps'], chips: ['Lion\'s Mane για μνήμη', 'Reishi για ύπνο', 'Cordyceps για ενέργεια', 'Fruiting body ή mycelium;'] },
                        // Creatine
                        { match: ['κρεατίνη', 'creatine'], chips: ['Monohydrate ή HCL;', 'Πόσα g/μέρα;', 'Χρειάζεται loading;', 'CreaPure;'] },
                        // Joint
                        { match: ['άρθρωσ', 'joint', 'γλυκοζαμίνη', 'χονδροϊτίνη'], chips: ['Γλυκοζαμίνη + χονδροϊτίνη', 'MSM', 'UC-II κολλαγόνο', 'Boswellia'] },
                        // Prenatal
                        { match: ['εγκυμοσύνη', 'prenatal', 'γονιμότητα', 'έγκυος'], chips: ['Methylfolate ή folic acid;', 'Πόσο DHA;', 'Χρειάζομαι σίδηρο;', 'Χολίνη;'] },
                        // Comparison / brands
                        { match: ['σύγκρι', 'compar', 'διαφορ', 'vs'], chips: ['Ποιο είναι καλύτερο;', 'Γιατί αυτό;', 'Εναλλακτικές;', 'Αξίζει η διαφορά;'] },
                        { match: ['thorne', 'now foods', 'life extension', 'μάρκα', 'brand', 'trust'], chips: ['Ποια μάρκα προτείνετε;', 'Thorne vs NOW', 'Ελληνικές μάρκες', 'Ποια είναι πιο αξιόπιστη;'] },
                        // Dosage / form questions
                        { match: ['δόση', 'δοσολογ', 'πόσ', 'dosage'], chips: ['Πρωί ή βράδυ;', 'Με φαγητό ή χωρίς;', 'Μπορώ να τα συνδυάσω;', 'Για πόσο καιρό;'] },
                        // Allergies / restrictions
                        { match: ['αλλεργ', 'allerg', 'vegan', 'χορτοφ', 'gluten'], chips: ['Χωρίς γλουτένη', 'Vegan', 'Χωρίς σόγια', 'Χωρίς λακτόζη'] },
                        // General health goals
                        { match: ['ενέργεια', 'energy', 'κούραση', 'fatigue'], chips: ['B-complex', 'Σίδηρος', 'CoQ10', 'Μαγνήσιο'] },
                        { match: ['ανοσοποιητ', 'immun', 'κρυολόγημα'], chips: ['Βιταμίνη C', 'Ψευδάργυρος', 'Βιταμίνη D', 'Προβιοτικά'] },
                        { match: ['δέρμα', 'μαλλιά', 'νύχια', 'skin', 'hair'], chips: ['Κολλαγόνο', 'Βιοτίνη', 'Βιταμίνη C', 'Ψευδάργυρος'] },
                        { match: ['άγχος', 'stress', 'ηρεμ'], chips: ['Ashwagandha', 'Μαγνήσιο glycinate', 'L-Theanine', 'Ροδιόλα'] },
                        { match: ['οστά', 'bone', 'οστεοπόρωση'], chips: ['Ασβέστιο', 'Βιταμίνη D + K2', 'Μαγνήσιο', 'Κολλαγόνο'] },
                        { match: ['καρδι', 'heart', 'χοληστερ', 'πίεση'], chips: ['Ωμέγα-3', 'CoQ10', 'Μαγνήσιο', 'Βερβερίνη'] },
                    ];

                    // Check all rules, collect matching chips
                    let chips = [];
                    for (const rule of rules) {
                        if (rule.match.some(kw => text.includes(kw))) {
                            chips.push(...rule.chips);
                            break; // first match wins
                        }
                    }

                    // If AI asked a question, add contextual answers
                    if (text.includes(';') || text.includes('?')) {
                        // AI asked something — if no chips matched, add universal follow-ups
                        if (chips.length === 0) {
                            chips.push('Ναι', 'Όχι', 'Δεν είμαι σίγουρος/η', 'Πες μου περισσότερα');
                        }
                    }

                    // Absolute fallback
                    if (chips.length === 0) {
                        chips.push('Δείξε μου τα top 3', 'Ποια μορφή είναι καλύτερη;', 'Έχω αλλεργία', 'Παίρνω ήδη φάρμακα');
                    }

                    // Filter out chips the user already said
                    const userMessages = this.messages.filter(m => m.role === 'user').map(m => m.content.toLowerCase());
                    const filtered = chips.filter(chip => !userMessages.some(um => um === chip.toLowerCase()));

                    return (filtered.length > 0 ? filtered : chips).slice(0, 4);
                },

                init() {
                    if (this.preselectedCategory) {
                        const cat = this.categories.find(c => c.id === this.preselectedCategory.id);
                        if (cat) {
                            this.selectCategory(cat).then(() => {
                                if (this.preselectedSupplement) {
                                    const supp = this.preselectedSupplement;
                                    this.userInput = 'Θέλω πληροφορίες για το ' + supp.brand + ' - ' + supp.title;
                                    this.showQuickReplies = false;
                                    this.sendMessage();
                                }
                            });
                        }
                    } else if (this.preselectedGoal) {
                        this.userInput = 'Θέλω συμπλήρωμα για ' + this.preselectedGoal.name_el;
                        this.showQuickReplies = false;
                        this.sendMessage();
                    } else if (this.preselectedBrand) {
                        this.userInput = 'Θέλω να δω τα καλύτερα προϊόντα της ' + this.preselectedBrand;
                        this.showQuickReplies = false;
                        this.sendMessage();
                    }
                },

                getQuickReplies() {
                    if (!this.selectedCategory) return [];
                    const slug = this.selectedCategory.slug;
                    return categoryQuickReplies[slug] || categoryQuickReplies['default'];
                },

                sendQuickReply(text) {
                    this.userInput = text;
                    this.showQuickReplies = false;
                    this.sendMessage();
                },

                resetChat() {
                    this.selectedCategory = null;
                    this.messages = [];
                    this.recommendations = null;
                    this.userInput = '';
                    this.showQuickReplies = true;
                    this.sessionId = 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                },

                async selectCategory(category) {
                    this.selectedCategory = category;
                    this.loading = true;
                    try {
                        const response = await fetch('/api/chat/select-category', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                session_id: this.sessionId,
                                category_id: category.id
                            })
                        });
                        if (!response.ok) {
                            const text = await response.text();
                            console.error('API Error:', response.status, text.substring(0, 500));
                            throw new Error('API returned ' + response.status);
                        }
                        const data = await response.json();
                        if (data.success && data.message) {
                            this.messages.push({ role: 'assistant', content: data.message });
                            setTimeout(() => {
                                const chatBox = document.getElementById('chat-box');
                                if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;
                            }, 100);
                        }
                    } catch (error) {
                        console.error('Σφάλμα:', error);
                        this.messages.push({ role: 'assistant', content: 'Παρουσιάστηκε σφάλμα. Παρακαλώ δοκιμάστε ξανά.' });
                    } finally {
                        this.loading = false;
                    }
                },

                async sendMessage() {
                    if (!this.userInput.trim()) return;

                    const message = this.userInput;
                    this.userInput = '';
                    this.messages.push({ role: 'user', content: message });
                    this.loading = true;

                    // Scroll to bottom
                    this.$nextTick(() => {
                        const containers = document.querySelectorAll('.overflow-y-auto');
                        containers.forEach(c => c.scrollTop = c.scrollHeight);
                    });

                    try {
                        if (this.selectedCategory) {
                            // Category selected: use full extraction pipeline
                            const response = await fetch('/api/chat/message', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: JSON.stringify({
                                    session_id: this.sessionId,
                                    message: message
                                })
                            });
                            if (!response.ok) throw new Error('API returned ' + response.status);
                            const data = await response.json();
                            this.messages.push({ role: 'assistant', content: data.message || 'Αναλύω τα δεδομένα σας...' });
                            if (data.recommendations) {
                                this.recommendations = data.recommendations;
                            }
                        } else {
                            // Free chat: use streaming endpoint for natural conversation
                            const response = await fetch('/api/chat/stream', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'text/event-stream',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: JSON.stringify({
                                    session_id: this.sessionId,
                                    message: message
                                })
                            });
                            if (!response.ok) throw new Error('API returned ' + response.status);

                            // Read SSE stream
                            const reader = response.body.getReader();
                            const decoder = new TextDecoder();
                            let fullContent = '';
                            const msgIndex = this.messages.length;
                            this.messages.push({ role: 'assistant', content: '' });
                            this.loading = false;

                            while (true) {
                                const { done, value } = await reader.read();
                                if (done) break;
                                const chunk = decoder.decode(value);
                                const lines = chunk.split('\n');
                                for (const line of lines) {
                                    if (line.startsWith('data: ') && line !== 'data: [DONE]') {
                                        try {
                                            const event = JSON.parse(line.slice(6));
                                            if (event.type === 'chunk') {
                                                fullContent += event.content;
                                            } else if (event.type === 'done') {
                                                fullContent = event.content;
                                            }
                                        } catch (e) {}
                                    }
                                }
                                // Replace the message to trigger Alpine reactivity
                                this.messages[msgIndex] = { role: 'assistant', content: fullContent };
                                this.messages = [...this.messages];
                            }
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        this.messages.push({ role: 'assistant', content: 'Αποτυχία σύνδεσης. Παρακαλώ δοκιμάστε ξανά.' });
                    } finally {
                        this.loading = false;
                        this.$nextTick(() => {
                            const containers = document.querySelectorAll('.overflow-y-auto');
                            containers.forEach(c => c.scrollTop = c.scrollHeight);
                        });
                    }
                }
            };
        }
    </script>
</body>
</html>
