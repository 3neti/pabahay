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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id')->index();
            $table->json('context')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('last_activity_at');
            $table->timestamps();
            
            $table->index(['user_id', 'session_id']);
            $table->index('last_activity_at');
        });

        Schema::create('conversation_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['user', 'assistant', 'system', 'tool']);
            $table->text('content')->nullable();
            $table->json('tool_calls')->nullable();
            $table->json('tool_results')->nullable();
            $table->string('ai_provider')->nullable();
            $table->string('ai_model')->nullable();
            $table->integer('tokens_used')->nullable();
            $table->timestamps();
            
            $table->index(['conversation_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation_messages');
        Schema::dropIfExists('conversations');
    }
};
