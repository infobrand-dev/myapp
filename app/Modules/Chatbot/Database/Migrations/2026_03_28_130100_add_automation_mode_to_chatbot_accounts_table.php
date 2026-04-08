<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('chatbot_accounts')) {
            return;
        }

        Schema::table('chatbot_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('chatbot_accounts', 'automation_mode')) {
                $table->string('automation_mode', 30)->default('ai_first')->after('model');
            }
        });

        DB::table('chatbot_accounts')
            ->whereNull('automation_mode')
            ->update(['automation_mode' => 'ai_first']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('chatbot_accounts') || !Schema::hasColumn('chatbot_accounts', 'automation_mode')) {
            return;
        }

        Schema::table('chatbot_accounts', function (Blueprint $table) {
            $table->dropColumn('automation_mode');
        });
    }
};
