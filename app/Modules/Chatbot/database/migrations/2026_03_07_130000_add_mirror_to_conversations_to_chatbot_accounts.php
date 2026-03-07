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
            if (!Schema::hasColumn('chatbot_accounts', 'mirror_to_conversations')) {
                $table->boolean('mirror_to_conversations')->default(false)->after('status');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('chatbot_accounts')) {
            return;
        }

        Schema::table('chatbot_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('chatbot_accounts', 'mirror_to_conversations')) {
                $table->dropColumn('mirror_to_conversations');
            }
        });
    }
};

