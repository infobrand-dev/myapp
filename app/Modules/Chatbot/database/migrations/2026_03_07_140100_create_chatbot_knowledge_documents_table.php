<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbot_knowledge_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->foreignId('chatbot_account_id')->constrained('chatbot_accounts')->cascadeOnDelete();
            $table->string('title');
            $table->longText('content');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['chatbot_account_id', 'created_at'], 'cb_kdocs_acc_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_knowledge_documents');
    }
};
