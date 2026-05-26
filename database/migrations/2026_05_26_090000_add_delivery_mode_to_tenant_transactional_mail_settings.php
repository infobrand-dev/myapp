<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenant_transactional_mail_settings')) {
            return;
        }

        if (!Schema::hasColumn('tenant_transactional_mail_settings', 'delivery_mode')) {
            Schema::table('tenant_transactional_mail_settings', function (Blueprint $table) {
                $table->string('delivery_mode', 50)->default('managed');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('tenant_transactional_mail_settings')) {
            return;
        }

        if (Schema::hasColumn('tenant_transactional_mail_settings', 'delivery_mode')) {
            Schema::table('tenant_transactional_mail_settings', function (Blueprint $table) {
                $table->dropColumn('delivery_mode');
            });
        }
    }
};
