<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_request_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('purchase_request_id')->index();
            $table->unsignedInteger('line_no')->default(1);
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->unsignedBigInteger('product_variant_id')->nullable()->index();
            $table->string('product_name_snapshot');
            $table->string('variant_name_snapshot')->nullable();
            $table->string('sku_snapshot')->nullable();
            $table->string('unit_snapshot')->nullable();
            $table->json('product_snapshot')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('qty', 18, 4)->default(0);
            $table->decimal('unit_cost', 18, 2)->default(0);
            $table->decimal('line_subtotal', 18, 2)->default(0);
            $table->decimal('discount_total', 18, 2)->default(0);
            $table->decimal('tax_total', 18, 2)->default(0);
            $table->decimal('line_total', 18, 2)->default(0);
            $table->json('pricing_snapshot')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['purchase_request_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_request_items');
    }
};
