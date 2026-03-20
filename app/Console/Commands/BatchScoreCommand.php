<?php

namespace App\Console\Commands;

use App\Models\Supplement;
use App\Models\SupplementCategory;
use App\Services\Scoring\ClinicalScorer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BatchScoreCommand extends Command
{
    protected $signature = 'supplements:batch-score {--batch=200 : Items per run} {--rounds=5 : Number of rounds}';
    protected $description = 'Score supplements in small batches (safe for timeouts)';

    public function handle()
    {
        $batchSize = (int) $this->option('batch');
        $rounds = (int) $this->option('rounds');
        $scorer = new ClinicalScorer();

        // Preload category slugs
        $categoryMap = SupplementCategory::pluck('slug', 'id')->toArray();

        $total = Supplement::count();
        $unscored = Supplement::whereNull('clinical_dose_score')->count();
        $this->info("Total: {$total} | Unscored: {$unscored}");

        if ($unscored === 0) {
            $this->info('All supplements already scored. Running rankings + brand trust...');
            $this->runRankingsAndTrust();
            return Command::SUCCESS;
        }

        $processed = 0;
        for ($round = 0; $round < $rounds; $round++) {
            $supplements = Supplement::whereNull('clinical_dose_score')
                ->limit($batchSize)
                ->get();

            if ($supplements->isEmpty()) {
                $this->info('All supplements scored!');
                break;
            }

            foreach ($supplements as $supplement) {
                $categorySlug = $categoryMap[$supplement->category_id] ?? null;
                $scores = $scorer->score($supplement, $categorySlug);
                $scores['value_score'] = $scores['overall_recommendation_score'];

                $supplement->update($scores);
                $processed++;
            }

            $remaining = Supplement::whereNull('clinical_dose_score')->count();
            $this->info("Round " . ($round + 1) . ": scored {$batchSize}. Remaining: {$remaining}");
        }

        $this->info("Scored {$processed} supplements this run.");

        // If all done, run rankings
        $remaining = Supplement::whereNull('clinical_dose_score')->count();
        if ($remaining === 0) {
            $this->runRankingsAndTrust();
        } else {
            $this->info("Still {$remaining} unscored. Run this command again.");
        }

        return Command::SUCCESS;
    }

    private function runRankingsAndTrust()
    {
        $this->info('Calculating category rankings...');
        $categories = SupplementCategory::all();
        foreach ($categories as $category) {
            $count = Supplement::where('category_id', $category->id)->count();
            if ($count === 0) continue;

            DB::statement("
                UPDATE supplements s
                JOIN (
                    SELECT id,
                        RANK() OVER (ORDER BY overall_recommendation_score DESC) as cat_rank
                    FROM supplements
                    WHERE category_id = ?
                ) ranked ON s.id = ranked.id
                SET s.category_rank = ranked.cat_rank,
                    s.category_percentile = ROUND((1 - (ranked.cat_rank - 1) / ?) * 100, 2)
            ", [$category->id, max(1, $count)]);
        }

        $this->info('Calculating brand trust scores...');
        $brands = DB::table('supplements')
            ->select('brand')
            ->selectRaw('AVG(overall_recommendation_score) as avg_overall')
            ->selectRaw('AVG(quality_score) as avg_quality')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN red_flags_detected IS NOT NULL AND JSON_LENGTH(red_flags_detected) > 0 THEN 1 ELSE 0 END) as flagged_count")
            ->whereNotNull('overall_recommendation_score')
            ->groupBy('brand')
            ->get();

        foreach ($brands as $brand) {
            $flagRate = $brand->total > 0 ? $brand->flagged_count / $brand->total : 0;
            $cleanRate = 1.0 - $flagRate;
            $trustScore = round(min(10, max(0,
                (($brand->avg_overall ?? 5) * 0.50) +
                (($brand->avg_quality ?? 5) * 0.30) +
                ($cleanRate * 10 * 0.20)
            )), 2);

            DB::table('supplements')
                ->where('brand', $brand->brand)
                ->update(['brand_trust_score' => $trustScore]);
        }

        // Update category counts
        foreach ($categories as $cat) {
            $cat->update(['product_count' => Supplement::where('category_id', $cat->id)->count()]);
        }

        $this->info('Rankings, brand trust, and counts updated!');
    }
}
