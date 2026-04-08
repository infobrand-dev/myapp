<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_usages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->foreignId('discount_id')->constrained('discounts')->cascadeOnDelete();
            $table->foreignId('voucher_id')->nullable()->constrained('discount_vouchers')->nullOnDelete();
            $table->string('usage_reference_type', 100)->nullable();
            $table->string('usage_reference_id', 100)->nullable();
            $table->string('customer_reference_type', 100)->nullable();
            $table->string('customer_reference_id', 100)->nullable();
            $table->string('outlet_reference', 100)->nullable();
            $table->string('sales_channel', 50)->nullable();
            $table->string('usage_status', 30)->default('applied');
            $table->string('currency_code', 3)->default('IDR');
            $table->decimal('subtotal_before', 18, 2)->default(0);
            $table->decimal('discount_total', 18, 2)->default(0);
            $table->decimal('grand_total_after', 18, 2)->default(0);
            $table->timestamp('evaluated_at')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->json('snapshot')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['discount_id', 'usage_status']);
            $table->index(['voucher_id', 'usage_status']);
            $table->index(['usage_reference_type', 'usage_reference_id'], 'discount_usage_reference_index');
            $table->index(['customer_reference_type', 'customer_reference_id'], 'discount_usage_customer_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_usages');
    }
};
