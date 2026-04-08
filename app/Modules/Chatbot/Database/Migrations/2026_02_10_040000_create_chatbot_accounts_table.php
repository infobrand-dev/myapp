<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('chatbot_accounts')) {
            Schema::create('chatbot_accounts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->default(1)->index();
                $table->string('name');
                $table->string('provider')->default('openai');
                $table->string('model')->nullable();
                $table->text('system_prompt')->nullable();
                $table->text('focus_scope')->nullable();
                $table->string('response_style', 30)->default('balanced');
                $table->string('operation_mode', 30)->default('ai_only');
                $table->text('api_key');
                $table->string('status')->default('active');
                $table->boolean('mirror_to_conversations')->default(false);
                $table->boolean('rag_enabled')->default(false);
                $table->unsignedTinyInteger('rag_top_k')->default(3);
                $table->json('metadata')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_accounts');
    }
};
