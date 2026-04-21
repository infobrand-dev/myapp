<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_stocks', function (Blueprint $table) {
            $table->decimal('average_unit_cost', 18, 2)->default(0)->after('reorder_quantity');
            $table->decimal('inventory_value', 18, 2)->default(0)->after('average_unit_cost');
        });

        Schema::table('inventory_stock_movements', function (Blueprint $table) {
            $table->decimal('unit_cost', 18, 2)->default(0)->after('after_quantity');
            $table->decimal('movement_value', 18, 2)->default(0)->after('unit_cost');
            $table->decimal('before_value', 18, 2)->default(0)->after('movement_value');
            $table->decimal('after_value', 18, 2)->default(0)->after('before_value');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_stock_movements', function (Blueprint $table) {
            $table->dropColumn([
                'unit_cost',
                'movement_value',
                'before_value',
                'after_value',
            ]);
        });

        Schema::table('inventory_stocks', function (Blueprint $table) {
            $table->dropColumn([
                'average_unit_cost',
                'inventory_value',
            ]);
        });
    }
};
