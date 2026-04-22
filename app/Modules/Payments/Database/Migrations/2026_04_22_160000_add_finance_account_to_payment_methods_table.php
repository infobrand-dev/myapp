<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_methods', 'finance_account_id')) {
                $table->foreignId('finance_account_id')
                    ->nullable()
                    ->after('type')
                    ->constrained('finance_accounts')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            if (Schema::hasColumn('payment_methods', 'finance_account_id')) {
                $table->dropConstrainedForeignId('finance_account_id');
            }
        });
    }
};
