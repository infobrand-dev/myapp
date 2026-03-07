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
            if (!Schema::hasColumn('chatbot_accounts', 'system_prompt')) {
                $table->text('system_prompt')->nullable()->after('model');
            }
            if (!Schema::hasColumn('chatbot_accounts', 'focus_scope')) {
                $table->text('focus_scope')->nullable()->after('system_prompt');
            }
            if (!Schema::hasColumn('chatbot_accounts', 'response_style')) {
                $table->string('response_style', 30)->default('balanced')->after('focus_scope');
            }
            if (!Schema::hasColumn('chatbot_accounts', 'rag_enabled')) {
                $table->boolean('rag_enabled')->default(false)->after('mirror_to_conversations');
            }
            if (!Schema::hasColumn('chatbot_accounts', 'rag_top_k')) {
                $table->unsignedTinyInteger('rag_top_k')->default(3)->after('rag_enabled');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('chatbot_accounts')) {
            return;
        }

        Schema::table('chatbot_accounts', function (Blueprint $table) {
            foreach (['rag_top_k', 'rag_enabled', 'response_style', 'focus_scope', 'system_prompt'] as $col) {
                if (Schema::hasColumn('chatbot_accounts', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

