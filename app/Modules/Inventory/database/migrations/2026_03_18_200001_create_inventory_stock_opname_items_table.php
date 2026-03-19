<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_stock_opname_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->foreignId('opname_id')->constrained('inventory_stock_opnames')->cascadeOnDelete();
            $table->foreignId('inventory_stock_id')->nullable()->constrained('inventory_stocks')->nullOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->decimal('system_quantity', 18, 4);
            $table->decimal('physical_quantity', 18, 4)->nullable();
            $table->decimal('difference_quantity', 18, 4)->nullable();
            $table->decimal('final_system_quantity', 18, 4)->nullable();
            $table->decimal('adjustment_quantity', 18, 4)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['opname_id', 'product_id', 'product_variant_id'], 'inventory_stock_opname_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stock_opname_items');
    }
};
