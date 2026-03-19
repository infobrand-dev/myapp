<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('inventory_location_id')->constrained('inventory_locations')->cascadeOnDelete();
            $table->date('opname_date');
            $table->string('status', 20)->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('adjustment_id')->nullable()->constrained('inventory_stock_adjustments')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['status', 'opname_date']);
        });

        Schema::create('inventory_stock_opname_items', function (Blueprint $table) {
            $table->id();
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
        Schema::dropIfExists('inventory_stock_opnames');
    }
};
