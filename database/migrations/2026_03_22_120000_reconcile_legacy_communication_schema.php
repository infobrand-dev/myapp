<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureTenantColumn('conversations', 'conversations_tenant_last_message_idx', function (Blueprint $table) {
            $table->index(['tenant_id', 'last_message_at', 'updated_at'], 'conversations_tenant_last_message_idx');
        });

        $this->ensureTenantColumn('conversation_participants', 'conversation_participants_tenant_idx', function (Blueprint $table) {
            $table->index(['tenant_id', 'conversation_id', 'user_id'], 'conversation_participants_tenant_idx');
        });

        $this->ensureTenantColumn('conversation_messages', 'conversation_messages_tenant_created_idx', function (Blueprint $table) {
            $table->index(['tenant_id', 'conversation_id', 'created_at', 'id'], 'conversation_messages_tenant_created_idx');
        });

        $this->ensureTenantColumn('conversation_activity_logs', 'conversation_activity_logs_tenant_idx', function (Blueprint $table) {
            $table->index(['tenant_id', 'conversation_id', 'created_at'], 'conversation_activity_logs_tenant_idx');
        });

        $this->ensureTenantColumn('chatbot_sessions', 'chatbot_sessions_tenant_last_message_idx', function (Blueprint $table) {
            $table->index(['tenant_id', 'user_id', 'last_message_at'], 'chatbot_sessions_tenant_last_message_idx');
        });

        $this->ensureTenantColumn('chatbot_messages', 'chatbot_messages_tenant_session_idx', function (Blueprint $table) {
            $table->index(['tenant_id', 'session_id', 'id'], 'chatbot_messages_tenant_session_idx');
        });

        $this->ensureTenantColumn('chatbot_knowledge_documents', 'chatbot_docs_tenant_created_idx', function (Blueprint $table) {
            $table->index(['tenant_id', 'chatbot_account_id', 'created_at'], 'chatbot_docs_tenant_created_idx');
        });

        $this->ensureTenantColumn('chatbot_knowledge_chunks', 'chatbot_chunks_tenant_doc_idx', function (Blueprint $table) {
            $table->index(['tenant_id', 'chatbot_account_id', 'document_id'], 'chatbot_chunks_tenant_doc_idx');
        });

        $this->ensureTenantColumn('user_presences', 'user_presences_tenant_user_idx', function (Blueprint $table) {
            $table->index(['tenant_id', 'user_id'], 'user_presences_tenant_user_idx');
        });

        $this->ensureTenantColumn('whatsapp_webhook_events', 'wa_webhook_events_tenant_status_idx', function (Blueprint $table) {
            $table->index(['tenant_id', 'process_status', 'received_at'], 'wa_webhook_events_tenant_status_idx');
        });
    }

    public function down(): void
    {
        // Irreversible schema reconciliation for legacy local databases.
    }

    private function ensureTenantColumn(string $table, string $indexName, \Closure $addIndexes): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($table): void {
            if (!Schema::hasColumn($table, 'tenant_id')) {
                $blueprint->unsignedBigInteger('tenant_id')->default(1)->after('id');
            }
        });

        Schema::table($table, function (Blueprint $blueprint) use ($addIndexes, $table): void {
            if (Schema::hasColumn($table, 'tenant_id')) {
                $addIndexes($blueprint);
            }
        });
    }
};
