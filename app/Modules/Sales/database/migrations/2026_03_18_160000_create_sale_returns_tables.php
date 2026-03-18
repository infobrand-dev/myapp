<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_return_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('sequence_date', 8)->unique();
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
        });

        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number', 50)->unique();
            $table->foreignId('sale_id')->constrained('sales')->restrictOnDelete();
            $table->string('sale_number_snapshot', 50);
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('customer_name_snapshot')->nullable();
            $table->string('customer_email_snapshot')->nullable();
            $table->string('customer_phone_snapshot', 50)->nullable();
            $table->text('customer_address_snapshot')->nullable();
            $table->json('customer_snapshot')->nullable();
            $table->string('status', 30)->default('draft');
            $table->string('inventory_status', 30)->default('pending');
            $table->string('refund_status', 30)->default('not_required');
            $table->dateTime('return_date')->nullable();
            $table->dateTime('finalized_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->text('reason');
            $table->text('notes')->nullable();
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('discount_total', 18, 2)->default(0);
            $table->decimal('tax_total', 18, 2)->default(0);
            $table->decimal('grand_total', 18, 2)->default(0);
            $table->decimal('refunded_total', 18, 2)->default(0);
            $table->decimal('refund_balance', 18, 2)->default(0);
            $table->boolean('refund_required')->default(false);
            $table->boolean('inventory_restock_required')->default(false);
            $table->unsignedBigInteger('inventory_location_id')->nullable();
            $table->string('currency_code', 10)->default('IDR');
            $table->json('totals_snapshot')->nullable();
            $table->json('integration_snapshot')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['sale_id', 'status']);
            $table->index(['status', 'return_date']);
            $table->index(['refund_status', 'created_at']);
            $table->index(['inventory_status', 'created_at']);
            $table->index(['inventory_location_id']);
        });

        Schema::create('sale_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_return_id')->constrained('sale_returns')->cascadeOnDelete();
            $table->foreignId('sale_item_id')->nullable()->constrained('sale_items')->nullOnDelete();
            $table->unsignedInteger('line_no')->default(1);
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('product_name_snapshot');
            $table->string('variant_name_snapshot')->nullable();
            $table->string('sku_snapshot', 100)->nullable();
            $table->string('barcode_snapshot', 100)->nullable();
            $table->string('unit_snapshot', 100)->nullable();
            $table->json('product_snapshot')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('sale_qty_snapshot', 18, 4)->default(0);
            $table->decimal('previous_returned_qty_snapshot', 18, 4)->default(0);
            $table->decimal('qty_returned', 18, 4);
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('line_subtotal', 18, 2)->default(0);
            $table->decimal('discount_total', 18, 2)->default(0);
            $table->decimal('tax_total', 18, 2)->default(0);
            $table->decimal('line_total', 18, 2)->default(0);
            $table->json('pricing_snapshot')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['sale_return_id', 'line_no']);
            $table->index(['sale_item_id']);
            $table->index(['product_id', 'product_variant_id']);
        });

        Schema::create('sale_return_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_return_id')->constrained('sale_returns')->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->string('event', 50);
            $table->text('reason')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['sale_return_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_return_status_logs');
        Schema::dropIfExists('sale_return_items');
        Schema::dropIfExists('sale_returns');
        Schema::dropIfExists('sale_return_sequences');
    }
};
