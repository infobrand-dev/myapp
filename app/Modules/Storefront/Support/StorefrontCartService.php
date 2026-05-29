<?php

namespace App\Modules\Storefront\Support;

use App\Modules\Products\Models\Product;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class StorefrontCartService
{
    /**
     * @return array<int, array{product_id:int,qty:int}>
     */
    public function rawItems(): array
    {
        return array_values(array_filter((array) Session::get($this->sessionKey(), []), function ($item): bool {
            return is_array($item) && isset($item['product_id'], $item['qty']);
        }));
    }

    public function add(Product $product, int $qty = 1): void
    {
        $items = collect($this->rawItems())->keyBy(fn (array $item) => (int) $item['product_id']);
        $current = (int) data_get($items->get($product->id), 'qty', 0);

        $items->put($product->id, [
            'product_id' => (int) $product->id,
            'qty' => max(1, $current + $qty),
        ]);

        Session::put($this->sessionKey(), $items->values()->all());
    }

    public function replaceWith(Product $product, int $qty = 1): void
    {
        Session::put($this->sessionKey(), [[
            'product_id' => (int) $product->id,
            'qty' => max(1, $qty),
        ]]);
    }

    public function update(Product $product, int $qty): void
    {
        if ($qty <= 0) {
            $this->remove($product);
            return;
        }

        $items = collect($this->rawItems())->keyBy(fn (array $item) => (int) $item['product_id']);
        $items->put($product->id, [
            'product_id' => (int) $product->id,
            'qty' => max(1, $qty),
        ]);

        Session::put($this->sessionKey(), $items->values()->all());
    }

    public function remove(Product $product): void
    {
        $items = collect($this->rawItems())
            ->reject(fn (array $item) => (int) $item['product_id'] === (int) $product->id)
            ->values()
            ->all();

        Session::put($this->sessionKey(), $items);
    }

    public function clear(): void
    {
        Session::forget($this->sessionKey());
    }

    public function count(): int
    {
        return (int) collect($this->rawItems())->sum(fn (array $item) => (int) ($item['qty'] ?? 0));
    }

    public function subtotal(): float
    {
        return (float) $this->items()->sum('line_total');
    }

    /**
     * @return Collection<int, array{product:Product,qty:int,line_total:float}>
     */
    public function items(): Collection
    {
        $rawItems = collect($this->rawItems());
        if ($rawItems->isEmpty()) {
            return collect();
        }

        $products = Product::query()
            ->with('media')
            ->where('tenant_id', TenantContext::currentId())
            ->whereIn('id', $rawItems->pluck('product_id')->all())
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        return $rawItems
            ->map(function (array $item) use ($products): ?array {
                $product = $products->get((int) $item['product_id']);
                if (!$product) {
                    return null;
                }

                $qty = max(1, (int) ($item['qty'] ?? 1));

                return [
                    'product' => $product,
                    'qty' => $qty,
                    'line_total' => (float) $product->sell_price * $qty,
                ];
            })
            ->filter()
            ->values();
    }

    private function sessionKey(): string
    {
        return sprintf(
            'storefront.cart.tenant_%d.company_%d',
            TenantContext::currentId() ?? 0,
            CompanyContext::currentId() ?? 0
        );
    }
}
