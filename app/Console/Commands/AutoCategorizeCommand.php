<?php

namespace App\Console\Commands;

use App\Models\Supplement;
use App\Models\SupplementCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class AutoCategorizeCommand extends Command
{
    protected $signature = 'supplements:auto-categorize';
    protected $description = 'Auto-assign category_id to all uncategorized supplements';

    public function handle()
    {
        // Step 1: Ensure new categories exist
        $this->info('Step 1: Ensuring all categories exist...');
        $this->call('db:seed', ['--class' => 'SupplementCategorySeeder']);

        // Load all categories
        $categories = SupplementCategory::all()->keyBy('slug');
        $categoryByName = SupplementCategory::all()->keyBy('name');

        // Step 2: Map comparison_group → category
        $this->info('Step 2: Mapping comparison_group to categories...');

        $groupMap = [
            'omega-3/fish oil' => 'omega-3-fish-oil',
            'magnesium' => 'magnesium',
            'vitamin d' => 'vitamin-d',
            'probiotics' => 'probiotics',
            'collagen' => 'collagen',
            'protein' => 'protein-supplements',
            'vitamin c' => 'vitamin-c',
            'coq10' => 'coq10',
            'b-vitamins' => 'b-vitamins',
            'zinc' => 'zinc',
            'vitamin a' => 'vitamin-a',
            'calcium' => 'calcium',
            'iron' => 'iron',
            'digestive enzymes' => 'digestive-enzymes',
            'amino acids' => 'amino-acids',
            'electrolytes' => 'electrolytes',
            'joint support' => 'joint-support',
            'sleep support' => 'sleep-support',
            'herbal/adaptogen' => 'herbal-adaptogens',
            'fiber' => 'fiber-supplements',
            'multivitamin' => 'multivitamins',
            'creatine' => 'creatine',
            'greens/superfoods' => 'greens-superfoods',
            'nmn/nad+' => 'nmn-nad',
            'vitamins (other)' => 'vitamins-other',
            'inositol' => 'inositol',
            'mushroom' => 'mushroom-complex',
            'berberine' => 'berberine',
            'biotin' => 'biotin',
            'saw palmetto' => 'mens-health-prostate',
            'prostate' => 'mens-health-prostate',
            'vitamin k' => 'vitamin-k',
            'prenatal' => 'prenatal-fertility',
            'selenium' => 'selenium',
            'other supplement' => 'other-supplements',
            'other/uncategorized' => 'other-supplements',
            'multi-mineral' => 'other-supplements',
        ];

        $mapped = 0;
        foreach ($groupMap as $group => $slug) {
            $cat = $categories[$slug] ?? null;
            if (!$cat) {
                $this->warn("  Category not found for slug: {$slug}");
                continue;
            }

            $count = Supplement::whereNull('category_id')
                ->where('comparison_group', $group)
                ->update(['category_id' => $cat->id]);

            if ($count > 0) {
                $this->line("  {$group} → {$cat->name}: {$count}");
                $mapped += $count;
            }
        }
        $this->info("  Mapped {$mapped} by comparison_group.");

        // Step 3: For remaining uncategorized, scan title + ingredients
        $this->info('Step 3: Scanning titles for remaining uncategorized...');

        $keywordMap = [
            'omega-3-fish-oil' => ['omega-3', 'omega 3', 'fish oil', 'epa', 'dha', 'krill', 'ιχθυέλαιο', 'ωμέγα'],
            'magnesium' => ['magnesium', 'μαγνήσιο'],
            'vitamin-d' => ['vitamin d', 'βιταμίνη d', 'd3', 'cholecalciferol'],
            'probiotics' => ['probiotic', 'προβιοτικ', 'lactobacillus', 'bifidobacterium'],
            'collagen' => ['collagen', 'κολλαγόνο'],
            'protein-supplements' => ['whey', 'casein', 'protein powder', 'πρωτεΐνη'],
            'vitamin-c' => ['vitamin c', 'βιταμίνη c', 'ascorbic acid'],
            'coq10' => ['coq10', 'ubiquinol', 'ubiquinone', 'coenzyme q'],
            'b-vitamins' => ['b-complex', 'b complex', 'b12', 'b-12', 'βιταμίνη b', 'σύμπλεγμα β'],
            'zinc' => ['zinc', 'ψευδάργυρο'],
            'vitamin-a' => ['vitamin a', 'βιταμίνη a', 'retinol'],
            'calcium' => ['calcium', 'ασβέστιο'],
            'iron' => ['iron supplement', 'σίδηρο', 'ferrous', 'ferric'],
            'digestive-enzymes' => ['digestive enzyme', 'πεπτικά ένζυμα', 'protease', 'lipase', 'amylase'],
            'amino-acids' => ['amino acid', 'αμινοξ', 'bcaa', 'l-glutamine', 'l-theanine', 'l-carnitine', 'nac', 'taurine'],
            'electrolytes' => ['electrolyte', 'ηλεκτρολύτ'],
            'joint-support' => ['joint', 'glucosamine', 'chondroitin', 'msm', 'γλυκοζαμίνη', 'αρθρ'],
            'sleep-support' => ['sleep', 'melatonin', 'ύπνο', 'μελατονίνη'],
            'herbal-adaptogens' => ['ashwagandha', 'rhodiola', 'ginseng', 'turmeric', 'curcumin', 'milk thistle', 'silymarin', 'echinacea', 'valerian', 'adaptogen', 'lion\'s mane', 'reishi', 'cordyceps', 'maca', 'holy basil'],
            'fiber-supplements' => ['fiber', 'psyllium', 'φυτικές ίνες', 'inulin'],
            'multivitamins' => ['multivitamin', 'πολυβιταμίνη', 'multi vitamin', 'two-per-day', 'one daily'],
            'creatine' => ['creatine', 'κρεατίνη'],
            'greens-superfoods' => ['greens', 'superfood', 'spirulina', 'chlorella', 'barley grass', 'wheat grass'],
            'nmn-nad' => ['nmn', 'nad+', 'nicotinamide mononucleotide', 'nicotinamide riboside'],
            'inositol' => ['inositol', 'ινοσιτόλη', 'myo-inositol', 'd-chiro-inositol'],
            'mushroom-complex' => ['lion\'s mane', 'lions mane', 'reishi', 'cordyceps', 'chaga', 'turkey tail', 'maitake', 'mushroom complex', 'mushroom blend', 'μανιτάρι'],
            'berberine' => ['berberine', 'βερβερίνη'],
            'biotin' => ['biotin', 'βιοτίνη'],
            'mens-health-prostate' => ['saw palmetto', 'prostate', 'προστάτη', 'pygeum', 'beta-sitosterol'],
            'vitamin-k' => ['vitamin k2', 'vitamin k', 'βιταμίνη k', 'mk-7', 'menaquinone'],
            'prenatal-fertility' => ['prenatal', 'εγκυμοσύνη', 'fertility', 'γονιμότητα', 'preconception'],
            'selenium' => ['selenium', 'σελήνιο', 'selenomethionine'],
            'vitamins-other' => ['vitamin e', 'βιταμίνη e'],
        ];

        $scanned = 0;
        $remaining = Supplement::whereNull('category_id')->count();
        $this->info("  {$remaining} supplements still uncategorized.");

        Supplement::whereNull('category_id')->chunkById(500, function ($supplements) use ($keywordMap, $categories, &$scanned) {
            foreach ($supplements as $supplement) {
                $searchText = strtolower(
                    ($supplement->title ?? '') . ' ' .
                    ($supplement->comparison_group ?? '') . ' ' .
                    ($supplement->product_description ?? '')
                );

                $matched = false;
                foreach ($keywordMap as $slug => $keywords) {
                    foreach ($keywords as $keyword) {
                        if (str_contains($searchText, strtolower($keyword))) {
                            $cat = $categories[$slug] ?? null;
                            if ($cat) {
                                $supplement->update(['category_id' => $cat->id]);
                                $scanned++;
                                $matched = true;
                                break 2;
                            }
                        }
                    }
                }

                // If still no match, assign to "Other Supplements"
                if (!$matched) {
                    $otherCat = $categories['other-supplements'] ?? null;
                    if ($otherCat) {
                        $supplement->update(['category_id' => $otherCat->id]);
                        $scanned++;
                    }
                }
            }
        });

        $this->info("  Categorized {$scanned} by title/ingredient scan.");

        // Step 4: Fix miscategorized products — override wrong comparison_group
        // Only override if the keyword is the PRIMARY focus of the product title
        // (appears early in title or is the main ingredient name)
        $this->info('Step 4: Fixing miscategorized products by title...');

        // Primary keywords: if ANY of these appear in title, override category
        // These are specific enough to not cause false positives
        $overrideMap = [
            'inositol' => ['inositol', 'ινοσιτόλη', 'myo-inositol', 'd-chiro-inositol', 'myo inositol'],
            'berberine' => ['berberine', 'βερβερίνη'],
            'selenium' => ['selenium ', 'σελήνιο', 'selenomethionine'],
            'creatine' => ['creatine', 'κρεατίνη'],
            'multivitamins' => ['multivitamin', 'πολυβιταμίνη', 'multi vitamin', 'two-per-day', 'one daily multivitamin'],
            'prenatal-fertility' => ['prenatal', 'εγκυμοσύνη', 'γονιμότητα'],
            'amino-acids' => ['l-glutamine', 'l glutamine', 'γλουταμίνη'],
        ];

        // These need extra care — only override if it's clearly the MAIN product
        // (keyword appears in first 60 chars of title = primary ingredient)
        $carefulOverrideMap = [
            'biotin' => ['biotin', 'βιοτίνη'],
            'mushroom-complex' => ['lion\'s mane', 'lions mane', 'reishi', 'cordyceps', 'chaga', 'turkey tail', 'mushroom complex', 'mushroom blend', 'μανιτάρι'],
            'mens-health-prostate' => ['saw palmetto', 'prostate', 'προστάτη'],
            'vitamin-k' => ['vitamin k2', 'βιταμίνη k2', 'menaquinone-7'],
        ];

        $overridden = 0;

        // Direct overrides — keyword anywhere in title
        foreach ($overrideMap as $slug => $titleKeywords) {
            $cat = $categories[$slug] ?? null;
            if (!$cat) continue;

            Supplement::where('category_id', '!=', $cat->id)
                ->chunkById(500, function ($supplements) use ($titleKeywords, $cat, &$overridden) {
                    foreach ($supplements as $supplement) {
                        $title = strtolower($supplement->title ?? '');
                        foreach ($titleKeywords as $keyword) {
                            if (str_contains($title, strtolower($keyword))) {
                                $supplement->update(['category_id' => $cat->id]);
                                $overridden++;
                                break;
                            }
                        }
                    }
                });
        }

        // Careful overrides — keyword must be in first 60 chars (= primary ingredient)
        foreach ($carefulOverrideMap as $slug => $titleKeywords) {
            $cat = $categories[$slug] ?? null;
            if (!$cat) continue;

            Supplement::where('category_id', '!=', $cat->id)
                ->chunkById(500, function ($supplements) use ($titleKeywords, $cat, &$overridden) {
                    foreach ($supplements as $supplement) {
                        $title = strtolower($supplement->title ?? '');
                        $titleStart = mb_substr($title, 0, 60);
                        foreach ($titleKeywords as $keyword) {
                            if (str_contains($titleStart, strtolower($keyword))) {
                                $supplement->update(['category_id' => $cat->id]);
                                $overridden++;
                                break;
                            }
                        }
                    }
                });
        }

        $this->info("  Fixed {$overridden} miscategorized products.");

        // Step 5: Update category product counts
        $this->info('Step 5: Updating category counts...');
        $allCategories = SupplementCategory::all();
        foreach ($allCategories as $cat) {
            $count = Supplement::where('category_id', $cat->id)->count();
            $cat->update(['product_count' => $count]);
        }

        // Step 6: Summary
        $this->newLine();
        $nullCount = Supplement::whereNull('category_id')->count();
        $this->info("DONE! Uncategorized remaining: {$nullCount}");

        $this->newLine();
        $this->info('Category distribution:');
        $cats = SupplementCategory::where('product_count', '>', 0)->orderByDesc('product_count')->get();
        foreach ($cats as $cat) {
            $this->line("  {$cat->name}: {$cat->product_count}");
        }

        return Command::SUCCESS;
    }
}
