<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_usage_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->foreignId('discount_usage_id')->constrained('discount_usages')->cascadeOnDelete();
            $table->foreignId('discount_id')->constrained('discounts')->cascadeOnDelete();
            $table->foreignId('voucher_id')->nullable()->constrained('discount_vouchers')->nullOnDelete();
            $table->string('line_key', 100)->nullable();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->decimal('quantity', 18, 4)->default(0);
            $table->decimal('subtotal_before', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('total_after', 18, 2)->default(0);
            $table->json('snapshot')->nullable();
            $table->timestamps();

            $table->index(['discount_usage_id', 'discount_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_usage_lines');
    }
};
