<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SupplementCategory;
use Illuminate\Support\Str;

class SupplementCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Omega-3 & Fish Oil', 'icon' => '🐟'],
            ['name' => 'Magnesium', 'icon' => '⚡'],
            ['name' => 'Vitamin D', 'icon' => '☀️'],
            ['name' => 'Probiotics', 'icon' => '🦠'],
            ['name' => 'Collagen', 'icon' => '💪'],
            ['name' => 'Protein Supplements', 'icon' => '🥊'],
            ['name' => 'Vitamin C', 'icon' => '🍊'],
            ['name' => 'CoQ10', 'icon' => '❤️'],
            ['name' => 'B-Vitamins', 'icon' => '⚙️'],
            ['name' => 'Zinc', 'icon' => '🛡️'],
            ['name' => 'Vitamin A', 'icon' => '👁️'],
            ['name' => 'Calcium', 'icon' => '🦴'],
            ['name' => 'Iron', 'icon' => '💉'],
            ['name' => 'Digestive Enzymes', 'icon' => '🍽️'],
            ['name' => 'Amino Acids', 'icon' => '🧬'],
            ['name' => 'Electrolytes', 'icon' => '⚡'],
            ['name' => 'Joint Support', 'icon' => '🧘'],
            ['name' => 'Sleep Support', 'icon' => '😴'],
            ['name' => 'Herbal/Adaptogens', 'icon' => '🌿'],
            ['name' => 'Fiber Supplements', 'icon' => '🥬'],
            ['name' => 'Multivitamins', 'icon' => '💊'],
            ['name' => 'Creatine', 'icon' => '🏋️'],
            ['name' => 'Greens & Superfoods', 'icon' => '🥦'],
            ['name' => 'NMN / NAD+', 'icon' => '🧪'],
            ['name' => 'Vitamins (Other)', 'icon' => '💎'],
            ['name' => 'Other Supplements', 'icon' => '📦'],
        ];

        foreach ($categories as $category) {
            SupplementCategory::updateOrCreate(
                ['name' => $category['name']],
                [
                    'slug' => Str::slug($category['name']),
                    'icon' => $category['icon'],
                ]
            );
        }
    }
}
