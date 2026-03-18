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
        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique();
            $table->ipAddress('user_ip')->nullable();
            $table->unsignedBigInteger('selected_category_id')->nullable();
            $table->json('extracted_conditions')->nullable();
            $table->json('extracted_preferences')->nullable();
            $table->decimal('confidence_score', 3, 2)->nullable();
            $table->boolean('is_ready_to_recommend')->default(false);
            $table->longText('conversation_history')->nullable();
            $table->timestamps();

            // Foreign key
            $table->foreign('selected_category_id')->references('id')->on('supplement_categories')->onDelete('set null');

            // Index
            $table->index('session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_conversations');
    }
};
