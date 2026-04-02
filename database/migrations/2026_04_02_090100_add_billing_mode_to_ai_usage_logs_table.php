<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ai_usage_logs')) {
            return;
        }

        Schema::table('ai_usage_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_usage_logs', 'billing_mode')) {
                $table->string('billing_mode', 20)->nullable()->after('model')->index();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('ai_usage_logs')) {
            return;
        }

        Schema::table('ai_usage_logs', function (Blueprint $table) {
            if (Schema::hasColumn('ai_usage_logs', 'billing_mode')) {
                $table->dropColumn('billing_mode');
            }
        });
    }
};
