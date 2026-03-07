<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('chatbot_knowledge_documents')) {
            Schema::create('chatbot_knowledge_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('chatbot_account_id')->constrained('chatbot_accounts')->cascadeOnDelete();
                $table->string('title');
                $table->longText('content');
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['chatbot_account_id', 'created_at'], 'cb_kdocs_acc_created_idx');
            });
        }

        if (!Schema::hasTable('chatbot_knowledge_chunks')) {
            Schema::create('chatbot_knowledge_chunks', function (Blueprint $table) {
                $table->id();
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
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_knowledge_chunks');
        Schema::dropIfExists('chatbot_knowledge_documents');
    }
};
