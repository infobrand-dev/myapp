<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('chatbot_decision_logs')) {
            return;
        }

        Schema::create('chatbot_decision_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('conversation_id')->nullable()->index();
            $table->foreignId('chatbot_account_id')->nullable()->constrained('chatbot_accounts')->nullOnDelete();
            $table->string('channel', 60)->nullable()->index();
            $table->string('action', 40)->index();
            $table->string('reason', 120)->nullable()->index();
            $table->decimal('confidence_score', 8, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_decision_logs');
    }
};
