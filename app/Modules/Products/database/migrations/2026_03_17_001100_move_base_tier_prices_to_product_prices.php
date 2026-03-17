<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $levelIds = DB::table('product_price_levels')
            ->whereIn('code', ['wholesale', 'member'])
            ->pluck('id', 'code');

        foreach (DB::table('products')->select(['id', 'wholesale_price', 'member_price'])->get() as $product) {
            $this->upsertBaseTierPrice((int) $product->id, null, $levelIds['wholesale'] ?? null, $product->wholesale_price);
            $this->upsertBaseTierPrice((int) $product->id, null, $levelIds['member'] ?? null, $product->member_price);
        }

        foreach (DB::table('product_variants')->select(['id', 'product_id', 'wholesale_price', 'member_price'])->get() as $variant) {
            $this->upsertBaseTierPrice((int) $variant->product_id, (int) $variant->id, $levelIds['wholesale'] ?? null, $variant->wholesale_price);
            $this->upsertBaseTierPrice((int) $variant->product_id, (int) $variant->id, $levelIds['member'] ?? null, $variant->member_price);
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['wholesale_price', 'member_price']);
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['wholesale_price', 'member_price']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('wholesale_price', 18, 2)->nullable()->after('sell_price');
            $table->decimal('member_price', 18, 2)->nullable()->after('wholesale_price');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->decimal('wholesale_price', 18, 2)->nullable()->after('sell_price');
            $table->decimal('member_price', 18, 2)->nullable()->after('wholesale_price');
        });

        $levelIds = DB::table('product_price_levels')
            ->whereIn('code', ['wholesale', 'member'])
            ->pluck('id', 'code');

        $productWholesale = $this->pricesByOwner(null, $levelIds['wholesale'] ?? null);
        $productMember = $this->pricesByOwner(null, $levelIds['member'] ?? null);
        $variantWholesale = $this->pricesByOwner('variant', $levelIds['wholesale'] ?? null);
        $variantMember = $this->pricesByOwner('variant', $levelIds['member'] ?? null);

        foreach (DB::table('products')->select('id')->get() as $product) {
            DB::table('products')
                ->where('id', $product->id)
                ->update([
                    'wholesale_price' => $productWholesale[(int) $product->id] ?? null,
                    'member_price' => $productMember[(int) $product->id] ?? null,
                ]);
        }

        foreach (DB::table('product_variants')->select('id')->get() as $variant) {
            DB::table('product_variants')
                ->where('id', $variant->id)
                ->update([
                    'wholesale_price' => $variantWholesale[(int) $variant->id] ?? null,
                    'member_price' => $variantMember[(int) $variant->id] ?? null,
                ]);
        }
    }

    private function upsertBaseTierPrice(int $productId, ?int $variantId, ?int $levelId, mixed $price): void
    {
        if (!$levelId || $price === null) {
            return;
        }

        DB::table('product_prices')->updateOrInsert(
            [
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'product_price_level_id' => $levelId,
            ],
            [
                'currency_code' => 'IDR',
                'price' => $price,
                'minimum_qty' => 1,
                'is_active' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    private function pricesByOwner(?string $ownerType, ?int $levelId): array
    {
        if (!$levelId) {
            return [];
        }

        return DB::table('product_prices')
            ->where('product_price_level_id', $levelId)
            ->when($ownerType === 'variant', fn ($query) => $query->whereNotNull('product_variant_id'))
            ->when($ownerType !== 'variant', fn ($query) => $query->whereNull('product_variant_id'))
            ->pluck('price', $ownerType === 'variant' ? 'product_variant_id' : 'product_id')
            ->all();
    }
};
