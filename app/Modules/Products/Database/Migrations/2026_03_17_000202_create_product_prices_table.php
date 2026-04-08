<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->foreignId('product_id')->nullable()->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('product_price_level_id')->nullable()->constrained('product_price_levels')->nullOnDelete();
            $table->string('currency_code', 3)->default('IDR');
            $table->decimal('price', 18, 2);
            $table->decimal('minimum_qty', 18, 4)->default(1);
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'product_variant_id'], 'product_prices_owner_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};
