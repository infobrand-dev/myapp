<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbot_knowledge_chunks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->foreignId('document_id')->constrained('chatbot_knowledge_documents')->cascadeOnDelete();
            $table->foreignId('chatbot_account_id')->constrained('chatbot_accounts')->cascadeOnDelete();
            $table->unsignedInteger('chunk_index')->default(0);
            $table->text('content');
            $table->unsignedInteger('content_length')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['chatbot_account_id', 'document_id', 'chunk_index'], 'cb_kchunks_acc_doc_chunk_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_knowledge_chunks');
    }
};
