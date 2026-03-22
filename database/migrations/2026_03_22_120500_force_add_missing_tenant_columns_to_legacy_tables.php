<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureTenantColumn('conversations');
        $this->ensureTenantColumn('conversation_participants');
        $this->ensureTenantColumn('conversation_messages');
        $this->ensureTenantColumn('conversation_activity_logs');
        $this->ensureTenantColumn('chatbot_sessions');
        $this->ensureTenantColumn('chatbot_messages');
        $this->ensureTenantColumn('chatbot_knowledge_documents');
        $this->ensureTenantColumn('chatbot_knowledge_chunks');
        $this->ensureTenantColumn('user_presences');
        $this->ensureTenantColumn('whatsapp_webhook_events');

        $this->ensureIndex('conversations', 'conversations_tenant_last_message_idx', ['tenant_id', 'last_message_at', 'updated_at']);
        $this->ensureIndex('conversation_participants', 'conversation_participants_tenant_idx', ['tenant_id', 'conversation_id', 'user_id']);
        $this->ensureIndex('conversation_messages', 'conversation_messages_tenant_created_idx', ['tenant_id', 'conversation_id', 'created_at', 'id']);
        $this->ensureIndex('conversation_activity_logs', 'conversation_activity_logs_tenant_idx', ['tenant_id', 'conversation_id', 'created_at']);
        $this->ensureIndex('chatbot_sessions', 'chatbot_sessions_tenant_last_message_idx', ['tenant_id', 'user_id', 'last_message_at']);
        $this->ensureIndex('chatbot_messages', 'chatbot_messages_tenant_session_idx', ['tenant_id', 'session_id', 'id']);
        $this->ensureIndex('chatbot_knowledge_documents', 'chatbot_docs_tenant_created_idx', ['tenant_id', 'chatbot_account_id', 'created_at']);
        $this->ensureIndex('chatbot_knowledge_chunks', 'chatbot_chunks_tenant_doc_idx', ['tenant_id', 'chatbot_account_id', 'document_id']);
        $this->ensureIndex('user_presences', 'user_presences_tenant_user_idx', ['tenant_id', 'user_id']);
        $this->ensureIndex('whatsapp_webhook_events', 'wa_webhook_events_tenant_status_idx', ['tenant_id', 'process_status', 'received_at']);
    }

    public function down(): void
    {
        // Local schema reconciliation only.
    }

    private function ensureTenantColumn(string $table): void
    {
        if (!Schema::hasTable($table) || Schema::hasColumn($table, 'tenant_id')) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint): void {
            $blueprint->unsignedBigInteger('tenant_id')->default(1);
        });
    }

    private function ensureIndex(string $table, string $indexName, array $columns): void
    {
        if (!Schema::hasTable($table) || !$this->hasIndexableColumns($table, $columns) || $this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName): void {
            $blueprint->index($columns, $indexName);
        });
    }

    private function hasIndexableColumns(string $table, array $columns): bool
    {
        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $database = DB::connection()->getDatabaseName();

        return DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }
};
