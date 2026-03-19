<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_functions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('name_el')->nullable();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('description_el')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('category_function', function (Blueprint $table) {
            $table->id();
            $table->foreignId('health_function_id')->constrained('health_functions')->onDelete('cascade');
            $table->foreignId('supplement_category_id')->constrained('supplement_categories')->onDelete('cascade');
            $table->decimal('relevance_score', 3, 1)->default(5.0);
            $table->unique(['health_function_id', 'supplement_category_id'], 'cf_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_function');
        Schema::dropIfExists('health_functions');
    }
};
