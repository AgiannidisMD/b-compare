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
        // Chat messages for detailed conversation tracking
        if (!Schema::hasTable('chat_messages')) {
            Schema::create('chat_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('conversation_id');
                $table->enum('role', ['user', 'assistant']);
                $table->text('content');
                $table->json('extracted_data')->nullable();
                $table->timestamps();

                $table->foreign('conversation_id')->references('id')->on('chat_conversations')->onDelete('cascade');
                $table->index('conversation_id');
                $table->index('created_at');
            });
        }

        // Recommendation events
        if (!Schema::hasTable('recommendation_events')) {
            Schema::create('recommendation_events', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('conversation_id')->nullable();
                $table->unsignedBigInteger('category_id')->nullable();
                $table->json('condition_ids')->nullable();
                $table->json('supplement_ids');
                $table->integer('supplements_count')->default(0);
                $table->string('session_id')->nullable();
                $table->ipAddress('user_ip')->nullable();
                $table->timestamps();

                $table->foreign('conversation_id')->references('id')->on('chat_conversations')->onDelete('set null');
                $table->foreign('category_id')->references('id')->on('supplement_categories')->onDelete('set null');
                $table->index('created_at');
                $table->index('category_id');
            });
        }

        // Click/interaction events
        if (!Schema::hasTable('analytics_events')) {
            Schema::create('analytics_events', function (Blueprint $table) {
                $table->id();
                $table->string('event_type');
                $table->unsignedBigInteger('supplement_id')->nullable();
                $table->unsignedBigInteger('category_id')->nullable();
                $table->unsignedBigInteger('condition_id')->nullable();
                $table->unsignedBigInteger('conversation_id')->nullable();
                $table->string('session_id')->nullable();
                $table->ipAddress('user_ip')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->foreign('supplement_id')->references('id')->on('supplements')->onDelete('set null');
                $table->foreign('category_id')->references('id')->on('supplement_categories')->onDelete('set null');
                $table->foreign('condition_id')->references('id')->on('medical_conditions')->onDelete('set null');
                $table->index('event_type');
                $table->index('created_at');
            });
        }

        // Daily aggregated stats
        if (!Schema::hasTable('daily_stats')) {
            Schema::create('daily_stats', function (Blueprint $table) {
                $table->id();
                $table->date('date')->unique();
                $table->integer('conversations_count')->default(0);
                $table->integer('messages_count')->default(0);
                $table->integer('recommendations_count')->default(0);
                $table->integer('unique_sessions')->default(0);
                $table->json('top_categories')->nullable();
                $table->json('top_conditions')->nullable();
                $table->json('top_supplements')->nullable();
                $table->json('hourly_distribution')->nullable();
                $table->timestamps();

                $table->index('date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_stats');
        Schema::dropIfExists('analytics_events');
        Schema::dropIfExists('recommendation_events');
        Schema::dropIfExists('chat_messages');
    }
};
