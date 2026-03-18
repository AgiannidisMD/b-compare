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
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique();
            $table->string('user_ip')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            // Stored preferences
            $table->json('known_conditions')->nullable();
            $table->json('known_allergies')->nullable();
            $table->json('dietary_preferences')->nullable();
            $table->decimal('budget_preference', 10, 2)->nullable();
            $table->json('preferred_brands')->nullable();
            $table->json('disliked_brands')->nullable();
            $table->json('preferred_dosage_forms')->nullable();

            // Interaction history
            $table->json('categories_explored')->nullable();
            $table->json('supplements_viewed')->nullable();
            $table->json('supplements_recommended')->nullable();

            // Activity tracking
            $table->timestamp('last_active_at')->nullable();
            $table->integer('total_conversations')->default(0);

            $table->timestamps();

            $table->index('user_ip');
            $table->index('last_active_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
