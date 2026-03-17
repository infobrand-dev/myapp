<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('inventory_locations')->nullOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('type', 50)->default('warehouse');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['type', 'is_active']);
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('inventory_stocks', function (Blueprint $table) {
            $table->id();
            $table->string('stock_key')->unique();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('inventory_location_id')->constrained('inventory_locations')->cascadeOnDelete();
            $table->decimal('current_quantity', 18, 4)->default(0);
            $table->decimal('reserved_quantity', 18, 4)->default(0);
            $table->decimal('minimum_quantity', 18, 4)->default(0);
            $table->decimal('reorder_quantity', 18, 4)->default(0);
            $table->boolean('allow_negative_stock')->default(false);
            $table->timestamp('last_movement_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'product_variant_id']);
            $table->index(['inventory_location_id', 'current_quantity']);
        });

        Schema::create('inventory_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->string('stock_key')->index();
            $table->foreignId('inventory_stock_id')->nullable()->constrained('inventory_stocks')->nullOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('inventory_location_id')->constrained('inventory_locations')->cascadeOnDelete();
            $table->string('movement_type', 50);
            $table->string('direction', 10);
            $table->decimal('quantity', 18, 4);
            $table->decimal('before_quantity', 18, 4)->default(0);
            $table->decimal('after_quantity', 18, 4)->default(0);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reason_code', 100)->nullable();
            $table->text('reason_text')->nullable();
            $table->timestamp('occurred_at');
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'product_variant_id', 'occurred_at']);
            $table->index(['inventory_location_id', 'occurred_at']);
            $table->index(['movement_type', 'occurred_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stock_movements');
        Schema::dropIfExists('inventory_stocks');
        Schema::dropIfExists('inventory_locations');
    }
};
