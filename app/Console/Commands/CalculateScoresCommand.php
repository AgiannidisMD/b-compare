<?php

namespace App\Console\Commands;

use App\Models\MedicalCondition;
use App\Models\Supplement;
use App\Models\SupplementCategory;
use App\Models\SupplementConditionMapping;
use App\Services\SupplementScoringService;
use Illuminate\Console\Command;

class CalculateScoresCommand extends Command
{
    protected $signature = 'supplements:calculate-scores {--mappings : Also create condition mappings} {--scores-only : Only calculate scores, skip rankings and brand trust}';

    protected $description = 'Calculate clinical scores, category rankings, and brand trust for all supplements';

    public function handle(SupplementScoringService $scoringService)
    {
        $total = Supplement::count();

        if ($this->option('scores-only')) {
            $this->info("Phase 1: Calculating clinical scores for {$total} supplements...");
            $bar = $this->output->createProgressBar($total);
            $bar->start();

            $processed = $scoringService->calculateAllScores(function ($count) use ($bar) {
                $bar->setProgress($count);
            });

            $bar->finish();
            $this->newLine();
            $this->info("Scored {$processed} supplements.");

            $scoringService->updateCategoryCounts();
        } else {
            // Full pipeline: scores → rankings → brand trust → counts
            $this->info("Running full scoring pipeline on {$total} supplements...");
            $this->newLine();

            // Phase 1: Clinical scores
            $this->info('Phase 1/4: Calculating clinical scores...');
            $bar = $this->output->createProgressBar($total);
            $bar->start();

            $scoringService->calculateAllScores(function ($count) use ($bar) {
                $bar->setProgress($count);
            });

            $bar->finish();
            $this->newLine();

            // Phase 2: Category rankings
            $this->info('Phase 2/4: Calculating category rankings...');
            $ranked = $scoringService->calculateCategoryRankings();
            $this->info("  Ranked {$ranked} supplements across categories.");

            // Phase 3: Brand trust scores
            $this->info('Phase 3/4: Calculating brand trust scores...');
            $brands = $scoringService->calculateBrandTrustScores();
            $this->info("  Updated {$brands} supplements with brand trust scores.");

            // Phase 4: Category counts
            $this->info('Phase 4/4: Updating category counts...');
            $scoringService->updateCategoryCounts();
        }

        // Show category distribution
        $this->newLine();
        $this->info('Category Distribution:');
        $categories = SupplementCategory::orderBy('product_count', 'desc')->get();
        foreach ($categories as $cat) {
            $this->line("  {$cat->icon} {$cat->name}: {$cat->product_count} products");
        }

        // Show top 5 per category sample
        $this->newLine();
        $this->info('Top supplement per category:');
        foreach ($categories->take(10) as $cat) {
            $top = Supplement::where('category_id', $cat->id)
                ->where('category_rank', 1)
                ->first();
            if ($top) {
                $flags = !empty($top->red_flags_detected) ? ' [' . count($top->red_flags_detected) . ' flags]' : '';
                $this->line("  {$cat->icon} {$cat->name}: [{$top->overall_recommendation_score}] {$top->brand} - {$top->title}{$flags}");
                $this->line("    Dose: {$top->clinical_dose_score} | Efficacy: {$top->efficacy_score} | Quality: {$top->quality_score} | Bio: {$top->bioavailability_score} | Brand Trust: {$top->brand_trust_score}");
            }
        }

        // Create condition mappings if requested
        if ($this->option('mappings')) {
            $this->newLine();
            $this->info('Creating condition mappings...');
            $this->createConditionMappings();
        }

        $this->newLine();
        $this->info('Score calculation complete!');

        return Command::SUCCESS;
    }

    private function createConditionMappings()
    {
        $mappings = [
            'Diabetes' => [
                'Magnesium' => ['efficacy' => 8.5, 'evidence' => 'strong'],
                'Vitamin D' => ['efficacy' => 7.5, 'evidence' => 'strong'],
                'Omega-3 & Fish Oil' => ['efficacy' => 7.0, 'evidence' => 'moderate'],
                'B-Vitamins' => ['efficacy' => 6.5, 'evidence' => 'moderate'],
                'Fiber Supplements' => ['efficacy' => 7.0, 'evidence' => 'strong'],
                'Probiotics' => ['efficacy' => 6.0, 'evidence' => 'moderate'],
            ],
            'Hypertension' => [
                'Magnesium' => ['efficacy' => 8.0, 'evidence' => 'strong'],
                'Omega-3 & Fish Oil' => ['efficacy' => 7.5, 'evidence' => 'strong'],
                'CoQ10' => ['efficacy' => 7.0, 'evidence' => 'moderate'],
                'Potassium' => ['efficacy' => 8.0, 'evidence' => 'strong'],
            ],
            'Arthritis / Joint Pain' => [
                'Omega-3 & Fish Oil' => ['efficacy' => 8.5, 'evidence' => 'strong'],
                'Collagen' => ['efficacy' => 8.0, 'evidence' => 'strong'],
                'Joint Support' => ['efficacy' => 8.5, 'evidence' => 'strong'],
                'Vitamin D' => ['efficacy' => 6.5, 'evidence' => 'moderate'],
            ],
            'Osteoporosis' => [
                'Calcium' => ['efficacy' => 9.0, 'evidence' => 'strong'],
                'Vitamin D' => ['efficacy' => 9.0, 'evidence' => 'strong'],
                'Magnesium' => ['efficacy' => 7.5, 'evidence' => 'strong'],
                'Collagen' => ['efficacy' => 6.5, 'evidence' => 'moderate'],
            ],
            'Digestive Issues' => [
                'Probiotics' => ['efficacy' => 9.0, 'evidence' => 'strong'],
                'Digestive Enzymes' => ['efficacy' => 8.5, 'evidence' => 'strong'],
                'Fiber Supplements' => ['efficacy' => 8.0, 'evidence' => 'strong'],
            ],
            'Immune Support' => [
                'Vitamin C' => ['efficacy' => 8.0, 'evidence' => 'strong'],
                'Vitamin D' => ['efficacy' => 8.5, 'evidence' => 'strong'],
                'Zinc' => ['efficacy' => 8.0, 'evidence' => 'strong'],
                'Probiotics' => ['efficacy' => 7.5, 'evidence' => 'strong'],
            ],
            'Anxiety / Stress' => [
                'Magnesium' => ['efficacy' => 8.0, 'evidence' => 'strong'],
                'B-Vitamins' => ['efficacy' => 7.5, 'evidence' => 'strong'],
                'Herbal/Adaptogens' => ['efficacy' => 8.5, 'evidence' => 'strong'],
            ],
            'Sleep Disorders' => [
                'Magnesium' => ['efficacy' => 8.0, 'evidence' => 'strong'],
                'Sleep Support' => ['efficacy' => 9.0, 'evidence' => 'strong'],
                'Herbal/Adaptogens' => ['efficacy' => 7.5, 'evidence' => 'moderate'],
            ],
            'High Cholesterol' => [
                'Omega-3 & Fish Oil' => ['efficacy' => 9.0, 'evidence' => 'strong'],
                'Fiber Supplements' => ['efficacy' => 8.0, 'evidence' => 'strong'],
                'CoQ10' => ['efficacy' => 6.5, 'evidence' => 'moderate'],
            ],
            'Anemia' => [
                'Iron' => ['efficacy' => 9.5, 'evidence' => 'strong'],
                'B-Vitamins' => ['efficacy' => 8.0, 'evidence' => 'strong'],
                'Vitamin C' => ['efficacy' => 7.0, 'evidence' => 'strong'],
            ],
            'Thyroid Issues' => [
                'Zinc' => ['efficacy' => 7.5, 'evidence' => 'moderate'],
                'Selenium' => ['efficacy' => 8.0, 'evidence' => 'strong'],
                'B-Vitamins' => ['efficacy' => 6.5, 'evidence' => 'moderate'],
            ],
            'PCOS' => [
                'B-Vitamins' => ['efficacy' => 7.5, 'evidence' => 'moderate'],
                'Omega-3 & Fish Oil' => ['efficacy' => 7.0, 'evidence' => 'moderate'],
                'Magnesium' => ['efficacy' => 7.0, 'evidence' => 'moderate'],
            ],
            'Menopause Symptoms' => [
                'Herbal/Adaptogens' => ['efficacy' => 8.0, 'evidence' => 'strong'],
                'Calcium' => ['efficacy' => 7.5, 'evidence' => 'strong'],
                'Vitamin D' => ['efficacy' => 7.5, 'evidence' => 'strong'],
            ],
            'Athletic Performance' => [
                'Protein Supplements' => ['efficacy' => 9.0, 'evidence' => 'strong'],
                'Amino Acids' => ['efficacy' => 8.5, 'evidence' => 'strong'],
                'Electrolytes' => ['efficacy' => 9.0, 'evidence' => 'strong'],
                'Magnesium' => ['efficacy' => 7.5, 'evidence' => 'strong'],
                'CoQ10' => ['efficacy' => 6.5, 'evidence' => 'moderate'],
            ],
            'Cognitive Function' => [
                'Omega-3 & Fish Oil' => ['efficacy' => 8.5, 'evidence' => 'strong'],
                'B-Vitamins' => ['efficacy' => 8.0, 'evidence' => 'strong'],
                'Herbal/Adaptogens' => ['efficacy' => 7.5, 'evidence' => 'moderate'],
            ],
        ];

        $conditions = MedicalCondition::all()->pluck('id', 'name');
        $categories = SupplementCategory::all()->pluck('id', 'name');

        $created = 0;
        foreach ($mappings as $conditionName => $categoryData) {
            $conditionId = $conditions[$conditionName] ?? null;
            if (!$conditionId) continue;

            foreach ($categoryData as $categoryName => $data) {
                $categoryId = $categories[$categoryName] ?? null;
                if (!$categoryId) continue;

                $supplementIds = Supplement::where('category_id', $categoryId)
                    ->whereNotNull('overall_recommendation_score')
                    ->orderBy('overall_recommendation_score', 'desc')
                    ->limit(100)
                    ->pluck('id');

                foreach ($supplementIds as $supplementId) {
                    SupplementConditionMapping::updateOrCreate(
                        [
                            'supplement_id' => $supplementId,
                            'condition_id' => $conditionId,
                        ],
                        [
                            'efficacy_rating' => $data['efficacy'],
                            'evidence_level' => $data['evidence'],
                            'contraindication' => false,
                        ]
                    );
                    $created++;
                }
            }
        }

        $this->info("Created $created supplement-condition mappings.");
    }
}
