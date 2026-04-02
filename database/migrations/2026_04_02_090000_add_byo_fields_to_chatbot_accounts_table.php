<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('chatbot_accounts')) {
            return;
        }

        Schema::table('chatbot_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('chatbot_accounts', 'ai_source')) {
                $table->string('ai_source', 20)->default('managed')->after('model');
                $table->index(['tenant_id', 'ai_source'], 'chatbot_accounts_tenant_ai_source_idx');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('chatbot_accounts')) {
            return;
        }

        Schema::table('chatbot_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('chatbot_accounts', 'ai_source')) {
                $table->dropIndex('chatbot_accounts_tenant_ai_source_idx');
                $table->dropColumn('ai_source');
            }
        });
    }
};
