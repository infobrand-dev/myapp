<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('document_settings') || Schema::hasColumn('document_settings', 'invoice_last_period')) {
            return;
        }

        Schema::table('document_settings', function (Blueprint $table) {
            $table->string('invoice_last_period', 20)->nullable()->after('invoice_next_number');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('document_settings') || !Schema::hasColumn('document_settings', 'invoice_last_period')) {
            return;
        }

        Schema::table('document_settings', function (Blueprint $table) {
            $table->dropColumn('invoice_last_period');
        });
    }
};
