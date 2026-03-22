<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_stock_movements', function (Blueprint $table) {
            $table->index(
                ['tenant_id', 'company_id', 'occurred_at', 'id'],
                'inventory_movements_ordering_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('inventory_stock_movements', function (Blueprint $table) {
            $table->dropIndex('inventory_movements_ordering_idx');
        });
    }
};
