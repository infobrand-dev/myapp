<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('chatbot_accounts') || Schema::hasColumn('chatbot_accounts', 'access_scope')) {
            return;
        }

        Schema::table('chatbot_accounts', function (Blueprint $table): void {
            $table->string('access_scope', 20)->default('public')->after('name');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('chatbot_accounts') || !Schema::hasColumn('chatbot_accounts', 'access_scope')) {
            return;
        }

        Schema::table('chatbot_accounts', function (Blueprint $table): void {
            $table->dropColumn('access_scope');
        });
    }
};
