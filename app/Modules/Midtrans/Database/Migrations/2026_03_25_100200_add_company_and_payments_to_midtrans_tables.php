<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('midtrans_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable();
        });

        Schema::table('midtrans_settings', function (Blueprint $table) {
            $table->json('enabled_payments')->nullable();
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
