<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rename table or recreate if missing
        if (Schema::hasTable('ai_accounts') && !Schema::hasTable('chatbot_accounts')) {
            Schema::rename('ai_accounts', 'chatbot_accounts');
        } elseif (!Schema::hasTable('chatbot_accounts')) {
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

        // whatsapp_instances column rename
        if (Schema::hasColumn('whatsapp_instances', 'ai_account_id')) {
            Schema::table('whatsapp_instances', function (Blueprint $table) {
                $table->dropForeign(['ai_account_id']);
                $table->dropColumn('ai_account_id');
            });
        }
        if (!Schema::hasColumn('whatsapp_instances', 'chatbot_account_id')) {
            Schema::table('whatsapp_instances', function (Blueprint $table) {
                $table->foreignId('chatbot_account_id')->nullable()->after('auto_reply')->constrained('chatbot_accounts')->nullOnDelete();
            });
        }

        // social_accounts column rename
        if (Schema::hasColumn('social_accounts', 'ai_account_id')) {
            Schema::table('social_accounts', function (Blueprint $table) {
                $table->dropForeign(['ai_account_id']);
                $table->dropColumn('ai_account_id');
            });
        }
        if (!Schema::hasColumn('social_accounts', 'chatbot_account_id')) {
            Schema::table('social_accounts', function (Blueprint $table) {
                $table->foreignId('chatbot_account_id')->nullable()->after('auto_reply')->constrained('chatbot_accounts')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // reverse columns
        if (Schema::hasColumn('whatsapp_instances', 'chatbot_account_id')) {
            Schema::table('whatsapp_instances', function (Blueprint $table) {
                $table->dropForeign(['chatbot_account_id']);
                $table->dropColumn('chatbot_account_id');
            });
        }
        if (Schema::hasColumn('social_accounts', 'chatbot_account_id')) {
            Schema::table('social_accounts', function (Blueprint $table) {
                $table->dropForeign(['chatbot_account_id']);
                $table->dropColumn('chatbot_account_id');
            });
        }

        if (!Schema::hasTable('ai_accounts') && Schema::hasTable('chatbot_accounts')) {
            Schema::rename('chatbot_accounts', 'ai_accounts');
        }
    }
};
