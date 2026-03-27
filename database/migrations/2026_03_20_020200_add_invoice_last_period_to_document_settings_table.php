<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('document_settings') || Schema::hasColumn('document_settings', 'invoice_last_period')) {
            return;
        }

        $supportsAfter = in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);

        Schema::table('document_settings', function (Blueprint $table) use ($supportsAfter) {
            $column = $table->string('invoice_last_period', 20)->nullable();

            if ($supportsAfter) {
                $column->after('invoice_next_number');
            }
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
