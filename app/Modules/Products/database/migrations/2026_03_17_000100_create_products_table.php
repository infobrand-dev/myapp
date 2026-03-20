<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->string('type', 50)->default('simple');
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('product_brands')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('product_units')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('sku');
            $table->string('barcode')->nullable();
            $table->longText('description')->nullable();
            $table->decimal('cost_price', 18, 2)->default(0);
            $table->decimal('sell_price', 18, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('track_stock')->default(true);
            $table->string('featured_image_path')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'slug']);
            $table->unique(['tenant_id', 'sku']);
            $table->unique(['tenant_id', 'barcode']);
            $table->index(['type', 'is_active']);
            $table->index(['category_id', 'brand_id']);
            $table->index(['tenant_id', 'is_active', 'created_at']);
            $table->fullText(['name', 'description'], 'products_search_fulltext');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
