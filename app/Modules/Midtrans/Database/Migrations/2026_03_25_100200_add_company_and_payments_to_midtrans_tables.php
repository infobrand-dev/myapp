<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $supportsAfter = in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);

        // Store company_id on each transaction so webhook can restore CompanyContext
        Schema::table('midtrans_transactions', function (Blueprint $table) use ($supportsAfter) {
            $column = $table->unsignedBigInteger('company_id')->nullable();

            if ($supportsAfter) {
                $column->after('tenant_id');
            }
        });

        // Store which payment methods are enabled in Midtrans dashboard
        Schema::table('midtrans_settings', function (Blueprint $table) use ($supportsAfter) {
            $column = $table->json('enabled_payments')->nullable();

            if ($supportsAfter) {
                $column->after('merchant_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('midtrans_transactions', function (Blueprint $table) {
            $table->dropColumn('company_id');
        });
        Schema::table('midtrans_settings', function (Blueprint $table) {
            $table->dropColumn('enabled_payments');
        });
    }
};
