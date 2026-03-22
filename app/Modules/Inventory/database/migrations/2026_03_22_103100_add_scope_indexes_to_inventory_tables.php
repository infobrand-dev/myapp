<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_stocks', function (Blueprint $table) {
            $table->index(
                ['tenant_id', 'company_id', 'branch_id', 'updated_at'],
                'inventory_stocks_scope_updated_idx'
            );
            $table->index(
                ['tenant_id', 'company_id', 'branch_id', 'inventory_location_id', 'current_quantity'],
                'inventory_stocks_scope_location_qty_idx'
            );
        });

        Schema::table('inventory_stock_movements', function (Blueprint $table) {
            $table->index(
                ['tenant_id', 'company_id', 'branch_id', 'occurred_at'],
                'inventory_movements_scope_occurred_idx'
            );
            $table->index(
                ['tenant_id', 'company_id', 'branch_id', 'inventory_location_id', 'occurred_at'],
                'inventory_movements_scope_location_occurred_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('inventory_stock_movements', function (Blueprint $table) {
            $table->dropIndex('inventory_movements_scope_occurred_idx');
            $table->dropIndex('inventory_movements_scope_location_occurred_idx');
        });

        Schema::table('inventory_stocks', function (Blueprint $table) {
            $table->dropIndex('inventory_stocks_scope_updated_idx');
            $table->dropIndex('inventory_stocks_scope_location_qty_idx');
        });
    }
};
