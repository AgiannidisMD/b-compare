<?php

namespace Database\Seeders;

use App\Models\HealthFunction;
use App\Models\SupplementCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class HealthFunctionSeeder extends Seeder
{
    public function run(): void
    {
        $functions = [
            ['name' => 'Sleep', 'name_el' => 'Ύπνος', 'icon' => '😴', 'description_el' => 'Βελτίωση ποιότητας ύπνου'],
            ['name' => 'Digestion', 'name_el' => 'Πέψη', 'icon' => '🍽️', 'description_el' => 'Υγεία πεπτικού συστήματος'],
            ['name' => 'Immunity', 'name_el' => 'Ανοσοποιητικό', 'icon' => '🛡️', 'description_el' => 'Ενίσχυση ανοσοποιητικού'],
            ['name' => 'Energy', 'name_el' => 'Ενέργεια', 'icon' => '⚡', 'description_el' => 'Ενέργεια και αντοχή'],
            ['name' => 'Stress Relief', 'name_el' => 'Διαχείριση Άγχους', 'icon' => '😌', 'description_el' => 'Μείωση άγχους και χαλάρωση'],
            ['name' => 'Joint Health', 'name_el' => 'Υγεία Αρθρώσεων', 'icon' => '💪', 'description_el' => 'Υποστήριξη αρθρώσεων'],
            ['name' => 'Heart Health', 'name_el' => 'Υγεία Καρδιάς', 'icon' => '❤️', 'description_el' => 'Καρδιαγγειακή υγεία'],
            ['name' => 'Brain & Focus', 'name_el' => 'Εγκέφαλος & Συγκέντρωση', 'icon' => '🧠', 'description_el' => 'Γνωστική λειτουργία και μνήμη'],
            ['name' => 'Skin, Hair & Nails', 'name_el' => 'Δέρμα, Μαλλιά & Νύχια', 'icon' => '💇', 'description_el' => 'Ομορφιά από μέσα'],
            ['name' => 'Bone Health', 'name_el' => 'Υγεία Οστών', 'icon' => '🦴', 'description_el' => 'Ισχυρά οστά και δόντια'],
            ['name' => 'Weight Management', 'name_el' => 'Διαχείριση Βάρους', 'icon' => '⚖️', 'description_el' => 'Υποστήριξη υγιούς βάρους'],
            ['name' => 'Athletic Performance', 'name_el' => 'Αθλητική Απόδοση', 'icon' => '🏋️', 'description_el' => 'Δύναμη, αντοχή και ανάρρωση'],
            ['name' => 'Eye Health', 'name_el' => 'Υγεία Ματιών', 'icon' => '👁️', 'description_el' => 'Προστασία όρασης'],
            ['name' => 'Mood Support', 'name_el' => 'Διάθεση', 'icon' => '😊', 'description_el' => 'Υποστήριξη ψυχικής υγείας'],
            ['name' => 'Detox & Liver', 'name_el' => 'Αποτοξίνωση & Ήπαρ', 'icon' => '🫀', 'description_el' => 'Υγεία ήπατος και αποτοξίνωση'],
        ];

        foreach ($functions as $i => $func) {
            HealthFunction::updateOrCreate(
                ['slug' => Str::slug($func['name'])],
                [
                    'name' => $func['name'],
                    'name_el' => $func['name_el'],
                    'icon' => $func['icon'],
                    'description_el' => $func['description_el'],
                    'sort_order' => $i,
                ]
            );
        }

        // Now create category-function mappings
        // Format: function_slug => [category_name => relevance_score]
        $mappings = [
            'sleep' => [
                'Sleep Support' => 10,
                'Magnesium' => 9,
                'Herbal/Adaptogens' => 8,
                'Amino Acids' => 5,
            ],
            'digestion' => [
                'Probiotics' => 10,
                'Digestive Enzymes' => 9,
                'Fiber Supplements' => 8,
                'Herbal/Adaptogens' => 4,
            ],
            'immunity' => [
                'Vitamin C' => 10,
                'Vitamin D' => 9,
                'Zinc' => 9,
                'Probiotics' => 7,
                'Herbal/Adaptogens' => 5,
                'Multivitamins' => 6,
            ],
            'energy' => [
                'B-Vitamins' => 10,
                'Iron' => 8,
                'CoQ10' => 8,
                'Creatine' => 7,
                'Magnesium' => 6,
                'Greens & Superfoods' => 5,
                'Multivitamins' => 6,
            ],
            'stress-relief' => [
                'Herbal/Adaptogens' => 10,
                'Magnesium' => 9,
                'B-Vitamins' => 7,
                'Amino Acids' => 6,
                'Inositol' => 7,
            ],
            'joint-health' => [
                'Joint Support' => 10,
                'Collagen' => 9,
                'Omega-3 & Fish Oil' => 7,
                'Vitamin D' => 5,
            ],
            'heart-health' => [
                'Omega-3 & Fish Oil' => 10,
                'CoQ10' => 9,
                'Magnesium' => 7,
                'Fiber Supplements' => 6,
                'Vitamin D' => 5,
            ],
            'brain-focus' => [
                'Omega-3 & Fish Oil' => 10,
                'B-Vitamins' => 8,
                'Herbal/Adaptogens' => 8,
                'Amino Acids' => 6,
                'NMN / NAD+' => 5,
            ],
            'skin-hair-nails' => [
                'Collagen' => 10,
                'Vitamin C' => 7,
                'Zinc' => 6,
                'B-Vitamins' => 5,
                'Vitamins (Other)' => 4,
            ],
            'bone-health' => [
                'Calcium' => 10,
                'Vitamin D' => 10,
                'Magnesium' => 8,
                'Collagen' => 6,
                'Vitamin A' => 4,
                'Vitamins (Other)' => 5,
            ],
            'weight-management' => [
                'Fiber Supplements' => 9,
                'Protein Supplements' => 8,
                'Inositol' => 7,
                'Greens & Superfoods' => 6,
                'B-Vitamins' => 5,
            ],
            'athletic-performance' => [
                'Protein Supplements' => 10,
                'Creatine' => 10,
                'Amino Acids' => 9,
                'Electrolytes' => 9,
                'Magnesium' => 7,
                'CoQ10' => 5,
            ],
            'eye-health' => [
                'Vitamin A' => 10,
                'Omega-3 & Fish Oil' => 7,
                'Zinc' => 6,
                'Vitamin C' => 5,
            ],
            'mood-support' => [
                'Herbal/Adaptogens' => 10,
                'Magnesium' => 8,
                'B-Vitamins' => 8,
                'Inositol' => 8,
                'Omega-3 & Fish Oil' => 7,
                'Amino Acids' => 6,
                'Vitamin D' => 5,
            ],
            'detox-liver' => [
                'Herbal/Adaptogens' => 10,
                'Greens & Superfoods' => 7,
                'Amino Acids' => 5,
                'Fiber Supplements' => 5,
            ],
        ];

        $categories = SupplementCategory::all()->pluck('id', 'name');

        foreach ($mappings as $funcSlug => $categoryData) {
            $func = HealthFunction::where('slug', $funcSlug)->first();
            if (!$func) continue;

            foreach ($categoryData as $catName => $relevance) {
                $catId = $categories[$catName] ?? null;
                if (!$catId) continue;

                $func->categories()->syncWithoutDetaching([
                    $catId => ['relevance_score' => $relevance],
                ]);
            }
        }
    }
}
