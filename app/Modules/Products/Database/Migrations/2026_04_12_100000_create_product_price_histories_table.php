<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_price_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->decimal('cost_price', 18, 2)->default(0);
            $table->decimal('sell_price', 18, 2)->default(0);
            $table->string('reason', 50)->default('updated');
            $table->json('meta')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('recorded_at');
            $table->timestamps();

            $table->index(['tenant_id', 'product_id', 'recorded_at'], 'product_price_histories_product_date_idx');
            $table->index(['tenant_id', 'product_variant_id', 'recorded_at'], 'product_price_histories_variant_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_price_histories');
    }
};
