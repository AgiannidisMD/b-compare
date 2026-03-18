<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplements', function (Blueprint $table) {
            $table->decimal('clinical_dose_score', 4, 2)->nullable()->after('overall_recommendation_score');
            $table->unsignedInteger('category_rank')->nullable()->after('clinical_dose_score');
            $table->decimal('category_percentile', 5, 2)->nullable()->after('category_rank');
            $table->json('red_flags_detected')->nullable()->after('category_percentile');
            $table->decimal('brand_trust_score', 4, 2)->nullable()->after('red_flags_detected');

            $table->index(['category_id', 'overall_recommendation_score'], 'idx_category_score');
            $table->index(['category_id', 'category_rank'], 'idx_category_rank');
            $table->index('brand_trust_score');
        });
    }

    public function down(): void
    {
        Schema::table('supplements', function (Blueprint $table) {
            $table->dropIndex('idx_category_score');
            $table->dropIndex('idx_category_rank');
            $table->dropIndex('brand_trust_score');
            $table->dropColumn([
                'clinical_dose_score',
                'category_rank',
                'category_percentile',
                'red_flags_detected',
                'brand_trust_score',
            ]);
        });
    }
};
