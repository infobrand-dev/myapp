<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rename table or recreate if missing
        if (Schema::hasTable('ai_accounts') && !Schema::hasTable('chatbot_accounts')) {
            Schema::rename('ai_accounts', 'chatbot_accounts');
        } elseif (!Schema::hasTable('chatbot_accounts')) {
            Schema::create('chatbot_accounts', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('provider')->default('openai');
                $table->string('model')->nullable();
                $table->text('api_key');
                $table->string('status')->default('active');
                $table->json('metadata')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

    }

    public function down(): void
    {
        if (!Schema::hasTable('ai_accounts') && Schema::hasTable('chatbot_accounts')) {
            Schema::rename('chatbot_accounts', 'ai_accounts');
        }
    }
};
