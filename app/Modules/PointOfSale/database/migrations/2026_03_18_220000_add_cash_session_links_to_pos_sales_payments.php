<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_carts', function (Blueprint $table) {
            $table->foreignId('pos_cash_session_id')->nullable()->after('cashier_user_id')->constrained('pos_cash_sessions')->nullOnDelete();
            $table->index(['pos_cash_session_id', 'status']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->unsignedBigInteger('outlet_id')->nullable()->after('source');
            $table->foreignId('pos_cash_session_id')->nullable()->after('outlet_id')->constrained('pos_cash_sessions')->nullOnDelete();
            $table->index(['pos_cash_session_id', 'status']);
            $table->index(['outlet_id', 'transaction_date']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('pos_cash_session_id')->nullable()->after('outlet_id')->constrained('pos_cash_sessions')->nullOnDelete();
            $table->index(['pos_cash_session_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['pos_cash_session_id', 'paid_at']);
            $table->dropConstrainedForeignId('pos_cash_session_id');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['pos_cash_session_id', 'status']);
            $table->dropIndex(['outlet_id', 'transaction_date']);
            $table->dropConstrainedForeignId('pos_cash_session_id');
            $table->dropColumn('outlet_id');
        });

        Schema::table('pos_carts', function (Blueprint $table) {
            $table->dropIndex(['pos_cash_session_id', 'status']);
            $table->dropConstrainedForeignId('pos_cash_session_id');
        });
    }
};
