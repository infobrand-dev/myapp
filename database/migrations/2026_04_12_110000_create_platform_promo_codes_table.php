<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_promo_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('label');
            $table->unsignedTinyInteger('discount_percent');
            $table->json('applicable_product_lines')->nullable()->comment('null = all product lines');
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('max_uses')->nullable()->comment('null = unlimited');
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamps();
        });

        // Seed: Meetra 2nd Anniversary promo — 50% off all plans
        DB::table('platform_promo_codes')->insert([
            'code' => 'MEETRA2ND',
            'label' => 'Promo Meetra Anniversary ke-2 — 50% Off',
            'discount_percent' => 50,
            'applicable_product_lines' => null,
            'is_active' => $this->dbBool(true),
            'expires_at' => null,
            'max_uses' => null,
            'used_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_promo_codes');
    }

    private function dbBool(bool $value): bool|string
    {
        return DB::connection()->getDriverName() === 'pgsql'
            ? ($value ? 'true' : 'false')
            : $value;
    }
};
