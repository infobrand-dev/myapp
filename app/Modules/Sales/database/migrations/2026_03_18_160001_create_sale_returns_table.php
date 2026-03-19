<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->string('return_number', 50);
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

            $table->unique(['tenant_id', 'return_number']);
            $table->index(['sale_id', 'status']);
            $table->index(['status', 'return_date']);
            $table->index(['refund_status', 'created_at']);
            $table->index(['inventory_status', 'created_at']);
            $table->index(['inventory_location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_returns');
    }
};
