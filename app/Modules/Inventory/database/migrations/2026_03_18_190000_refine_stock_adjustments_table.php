<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_stock_adjustments', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('reason_text');
            $table->foreignId('finalized_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable()->after('finalized_by');
            $table->index(['status', 'adjustment_date']);
        });

        DB::table('inventory_stock_adjustments')
            ->where('status', 'posted')
            ->update([
                'status' => 'finalized',
            ]);

        DB::table('inventory_stock_adjustments')
            ->whereNull('finalized_by')
            ->update([
                'finalized_by' => DB::raw('approved_by'),
                'finalized_at' => DB::raw('approved_at'),
            ]);
    }

    public function down(): void
    {
        DB::table('inventory_stock_adjustments')
            ->where('status', 'finalized')
            ->update([
                'status' => 'posted',
            ]);

        Schema::table('inventory_stock_adjustments', function (Blueprint $table) {
            $table->dropIndex(['status', 'adjustment_date']);
            $table->dropConstrainedForeignId('finalized_by');
            $table->dropColumn(['notes', 'finalized_at']);
        });
    }
};
