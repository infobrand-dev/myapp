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

        Schema::table('whatsapp_instances', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_instances', 'auto_reply')) {
                $table->boolean('auto_reply')->default(false);
            }
            if (!Schema::hasColumn('whatsapp_instances', 'chatbot_account_id')) {
                $table->foreignId('chatbot_account_id')->nullable()->constrained('chatbot_accounts')->nullOnDelete();
            }
        });

        Schema::table('social_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('social_accounts', 'auto_reply')) {
                $table->boolean('auto_reply')->default(false);
            }
            if (!Schema::hasColumn('social_accounts', 'chatbot_account_id')) {
                $table->foreignId('chatbot_account_id')->nullable()->constrained('chatbot_accounts')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('chatbot_account_id');
            $table->dropColumn(['auto_reply']);
        });
        Schema::table('whatsapp_instances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('chatbot_account_id');
            $table->dropColumn(['auto_reply']);
        });
        Schema::dropIfExists('chatbot_accounts');
    }
};
