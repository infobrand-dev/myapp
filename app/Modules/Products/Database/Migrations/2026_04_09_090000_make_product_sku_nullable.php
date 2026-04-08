<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')->where('sku', '')->update(['sku' => null]);
        DB::table('product_variants')->where('sku', '')->update(['sku' => null]);

        Schema::table('products', function (Blueprint $table) {
            $table->string('sku')->nullable()->change();
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('sku')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('products')->whereNull('sku')->update(['sku' => '']);
        DB::table('product_variants')->whereNull('sku')->update(['sku' => '']);

        Schema::table('products', function (Blueprint $table) {
            $table->string('sku')->nullable(false)->change();
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('sku')->nullable(false)->change();
        });
    }
};
