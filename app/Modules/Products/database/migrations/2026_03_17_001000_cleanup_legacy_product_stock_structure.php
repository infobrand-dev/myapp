<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (Schema::hasColumn('products', 'alert_low_stock')) {
                    $table->dropColumn('alert_low_stock');
                }

                if (Schema::hasColumn('products', 'min_stock')) {
                    $table->dropColumn('min_stock');
                }
            });
        }

        Schema::dropIfExists('product_stocks');
        Schema::dropIfExists('stock_locations');
    }

    public function down(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (!Schema::hasColumn('products', 'alert_low_stock')) {
                    $table->boolean('alert_low_stock')->default(true)->after('track_stock');
                }

                if (!Schema::hasColumn('products', 'min_stock')) {
                    $table->decimal('min_stock', 18, 4)->default(0)->after('alert_low_stock');
                }
            });
        }

        if (!Schema::hasTable('stock_locations')) {
            Schema::create('stock_locations', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->string('type', 50)->default('warehouse');
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('product_stocks')) {
            Schema::create('product_stocks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
                $table->foreignId('stock_location_id')->constrained('stock_locations')->cascadeOnDelete();
                $table->decimal('quantity', 18, 4)->default(0);
                $table->decimal('reserved_quantity', 18, 4)->default(0);
                $table->decimal('reorder_level', 18, 4)->default(0);
                $table->timestamps();

                $table->unique(['product_id', 'product_variant_id', 'stock_location_id'], 'product_stock_unique');
            });
        }
    }
};
