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
        Schema::dropIfExists('chatbot_accounts');
    }
};
