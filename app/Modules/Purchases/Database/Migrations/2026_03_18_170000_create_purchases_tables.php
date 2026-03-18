<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('sequence_date', 8)->unique();
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
        });

        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_number', 50)->unique();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('supplier_name_snapshot')->nullable();
            $table->string('supplier_email_snapshot')->nullable();
            $table->string('supplier_phone_snapshot', 50)->nullable();
            $table->text('supplier_address_snapshot')->nullable();
            $table->json('supplier_snapshot')->nullable();
            $table->string('supplier_reference', 100)->nullable();
            $table->string('supplier_invoice_number', 100)->nullable();
            $table->text('supplier_notes')->nullable();
            $table->string('status', 30)->default('draft');
            $table->string('payment_status', 30)->default('unpaid');
            $table->dateTime('purchase_date')->nullable();
            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->dateTime('voided_at')->nullable();
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('discount_total', 18, 2)->default(0);
            $table->decimal('tax_total', 18, 2)->default(0);
            $table->decimal('grand_total', 18, 2)->default(0);
            $table->decimal('received_total_qty', 18, 4)->default(0);
            $table->decimal('paid_total', 18, 2)->default(0);
            $table->decimal('balance_due', 18, 2)->default(0);
            $table->string('currency_code', 10)->default('IDR');
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->text('void_reason')->nullable();
            $table->json('totals_snapshot')->nullable();
            $table->json('integration_snapshot')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'purchase_date']);
            $table->index(['payment_status', 'purchase_date']);
            $table->index(['contact_id', 'purchase_date']);
            $table->index(['supplier_invoice_number']);
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->unsignedInteger('line_no')->default(1);
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('product_name_snapshot');
            $table->string('variant_name_snapshot')->nullable();
            $table->string('sku_snapshot', 100)->nullable();
            $table->string('unit_snapshot', 100)->nullable();
            $table->json('product_snapshot')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('qty', 18, 4);
            $table->decimal('qty_received', 18, 4)->default(0);
            $table->decimal('unit_cost', 18, 2);
            $table->decimal('line_subtotal', 18, 2)->default(0);
            $table->decimal('discount_total', 18, 2)->default(0);
            $table->decimal('tax_total', 18, 2)->default(0);
            $table->decimal('line_total', 18, 2)->default(0);
            $table->json('pricing_snapshot')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['purchase_id', 'line_no']);
            $table->index(['product_id', 'product_variant_id']);
        });

        Schema::create('purchase_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->string('receipt_number', 50)->unique();
            $table->foreignId('inventory_location_id')->nullable()->constrained('inventory_locations')->nullOnDelete();
            $table->string('status', 30)->default('posted');
            $table->dateTime('receipt_date');
            $table->text('notes')->nullable();
            $table->decimal('total_received_qty', 18, 4)->default(0);
            $table->json('integration_snapshot')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['purchase_id', 'receipt_date']);
        });

        Schema::create('purchase_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_receipt_id')->constrained('purchase_receipts')->cascadeOnDelete();
            $table->foreignId('purchase_item_id')->constrained('purchase_items')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->decimal('qty_received', 18, 4);
            $table->json('inventory_snapshot')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->string('event', 50);
            $table->text('reason')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['purchase_id', 'created_at']);
        });

        Schema::create('purchase_void_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->string('status_before', 30)->nullable();
            $table->text('reason');
            $table->json('snapshot')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_void_logs');
        Schema::dropIfExists('purchase_status_histories');
        Schema::dropIfExists('purchase_receipt_items');
        Schema::dropIfExists('purchase_receipts');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('purchase_sequences');
    }
};
