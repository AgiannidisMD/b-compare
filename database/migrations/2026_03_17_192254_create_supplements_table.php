<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('supplements', function (Blueprint $table) {
            $table->id();
            $table->string('pid')->unique()->nullable();
            $table->string('sku')->nullable();
            $table->string('brand');
            $table->string('title');
            $table->text('product_url')->nullable();
            $table->text('image_url')->nullable();
            $table->decimal('current_price', 10, 2)->nullable();
            $table->string('currency')->default('EUR');
            $table->decimal('rating', 3, 2)->nullable();
            $table->unsignedInteger('review_count')->default(0);
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('comparison_group')->nullable();
            $table->longText('product_description')->nullable();
            $table->longText('nutritional_facts')->nullable();
            $table->decimal('serving_size_value', 10, 2)->nullable();
            $table->string('serving_size_unit')->nullable();
            $table->unsignedInteger('servings_per_container')->nullable();
            $table->decimal('cost_per_serving', 10, 4)->nullable();
            $table->decimal('cost_per_day', 10, 4)->nullable();
            $table->enum('dosage_form', ['capsule', 'softgel', 'tablet', 'powder', 'liquid', 'gummy', 'unknown'])->default('unknown');
            $table->json('certification_flags')->nullable();
            $table->longText('suggested_use')->nullable();
            $table->longText('other_ingredients')->nullable();
            $table->longText('warnings')->nullable();
            $table->json('allergen_contains_flags')->nullable();
            $table->json('allergen_free_from_flags')->nullable();
            $table->json('active_ingredients')->nullable();
            $table->string('daily_dose_recommended')->nullable();
            $table->decimal('daily_servings_estimate', 5, 2)->nullable();
            $table->unsignedInteger('days_of_supply')->nullable();
            $table->json('warning_flags')->nullable();
            $table->decimal('extraction_confidence', 3, 2)->nullable();

            // B-COMPARE specific fields
            $table->decimal('efficacy_score', 4, 2)->nullable();
            $table->decimal('bioavailability_score', 4, 2)->nullable();
            $table->decimal('quality_score', 4, 2)->nullable();
            $table->decimal('value_score', 4, 2)->nullable();
            $table->decimal('overall_recommendation_score', 5, 2)->nullable();

            $table->timestamps();

            // Foreign key
            $table->foreign('category_id')->references('id')->on('supplement_categories')->onDelete('set null');

            // Indices
            $table->index('category_id');
            $table->index('comparison_group');
            $table->index('brand');
            $table->index('current_price');
            $table->index('rating');
            $table->index('title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplements');
    }
};
