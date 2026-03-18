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
        Schema::create('supplement_recommendations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->json('recommended_supplements')->nullable();
            $table->longText('comparison_analysis')->nullable();
            $table->json('dosage_recommendations')->nullable();
            $table->timestamps();

            // Foreign key
            $table->foreign('conversation_id')->references('id')->on('chat_conversations')->onDelete('cascade');

            // Index
            $table->index('conversation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplement_recommendations');
    }
};
