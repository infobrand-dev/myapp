<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('chatbot_sessions')) {
            Schema::create('chatbot_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('chatbot_account_id')->constrained('chatbot_accounts')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('title')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('last_message_at')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'last_message_at']);
            });
        }

        if (!Schema::hasTable('chatbot_messages')) {
            Schema::create('chatbot_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('session_id')->constrained('chatbot_sessions')->cascadeOnDelete();
                $table->string('role', 20); // user|assistant|system
                $table->longText('content')->nullable();
                $table->json('provider_response')->nullable();
                $table->unsignedInteger('prompt_tokens')->nullable();
                $table->unsignedInteger('completion_tokens')->nullable();
                $table->unsignedInteger('total_tokens')->nullable();
                $table->timestamps();
                $table->index(['session_id', 'id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_messages');
        Schema::dropIfExists('chatbot_sessions');
    }
};

