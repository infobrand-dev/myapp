<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_price_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('minimum_qty', 18, 4)->default(1);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
        });

        DB::table('product_price_levels')->insert([
            [
                'tenant_id' => 1,
                'code' => 'default',
                'name' => 'Retail',
                'description' => 'Harga jual retail standar.',
                'minimum_qty' => 1,
                'is_default' => true,
                'is_active' => true,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => 1,
                'code' => 'wholesale',
                'name' => 'Wholesale',
                'description' => 'Harga grosir dasar.',
                'minimum_qty' => 1,
                'is_default' => false,
                'is_active' => true,
                'sort_order' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => 1,
                'code' => 'member',
                'name' => 'Member',
                'description' => 'Harga khusus member.',
                'minimum_qty' => 1,
                'is_default' => false,
                'is_active' => true,
                'sort_order' => 20,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('product_price_levels');
    }
};
