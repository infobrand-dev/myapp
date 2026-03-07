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
            if (!Schema::hasColumn('chatbot_accounts', 'operation_mode')) {
                $table->string('operation_mode', 30)->default('ai_only')->after('response_style');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('chatbot_accounts')) {
            return;
        }

        Schema::table('chatbot_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('chatbot_accounts', 'operation_mode')) {
                $table->dropColumn('operation_mode');
            }
        });
    }
};

