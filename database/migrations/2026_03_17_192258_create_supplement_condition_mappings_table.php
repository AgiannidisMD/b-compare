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
        Schema::create('supplement_condition_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplement_id');
            $table->unsignedBigInteger('condition_id');
            $table->decimal('efficacy_rating', 3, 2)->nullable();
            $table->enum('evidence_level', ['strong', 'moderate', 'limited', 'anecdotal'])->nullable();
            $table->boolean('contraindication')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('supplement_id')->references('id')->on('supplements')->onDelete('cascade');
            $table->foreign('condition_id')->references('id')->on('medical_conditions')->onDelete('cascade');

            // Unique constraint
            $table->unique(['supplement_id', 'condition_id']);

            // Indices
            $table->index('condition_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplement_condition_mappings');
    }
};
