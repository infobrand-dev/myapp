<?php

namespace App\Support\Shipping;

use App\Modules\Storefront\Exceptions\StorefrontCheckoutException;
use App\Modules\Products\Models\Product;
use App\Models\Company;
use App\Support\CompanyContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Arr;

class CheckoutShippingQuoteService
{
    public function __construct(
        private readonly ShippingProviderManager $shippingProviders,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    public function quoteForCheckout(Product $product, array $payload): ?array
    {
        return $this->quoteForItems(collect([[
            'product' => $product,
            'qty' => max(1, (int) ($payload['qty'] ?? 1)),
        ]]), $payload);
    }

    /**
     * @param  Collection<int, array{product:Product,qty:int}>  $items
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    public function quoteForItems(Collection $items, array $payload): ?array
    {
        if (($payload['fulfillment_method'] ?? 'pickup') !== 'delivery') {
            return null;
        }

        $driver = $this->shippingProviders->assertQuoteReady();

        $provider = $driver->provider();
        $requestPayload = $this->buildPayload($items, $payload, CompanyContext::currentCompany(), $provider);
        $result = $this->shippingProviders->quoteRates($requestPayload, $provider);
        $options = collect((array) ($result['options'] ?? []))
            ->filter(fn ($option) => is_array($option))
            ->sortBy(fn ($option) => (float) ($option['price'] ?? 0))
            ->map(function (array $option) use ($provider): array {
                $normalized = [
                    'provider' => $provider,
                    'courier_code' => (string) ($option['courier_code'] ?? ''),
                    'courier_name' => (string) ($option['courier_name'] ?? $option['courier_code'] ?? '-'),
                    'service_code' => (string) ($option['service_code'] ?? ''),
                    'service_name' => (string) ($option['service_name'] ?? $option['service_code'] ?? '-'),
                    'price' => (float) ($option['price'] ?? 0),
                    'currency' => (string) ($option['currency'] ?? 'IDR'),
                    'etd' => $option['etd'] ?? null,
                    'raw' => (array) ($option['raw'] ?? $option),
                ];
                $normalized['selection_key'] = sha1(json_encode([
                    $normalized['provider'],
                    $normalized['courier_code'],
                    $normalized['service_code'],
                    $normalized['price'],
                    $normalized['currency'],
                    $normalized['etd'],
                ]));

                return $normalized;
            })
            ->values();

        if ($options->isEmpty()) {
            throw new StorefrontCheckoutException(
                'Ongkir belum bisa dihitung untuk alamat tujuan ini.',
                ['fulfillment_method' => 'Ongkir belum bisa dihitung untuk alamat tujuan ini.'],
            );
        }

        $selected = $this->resolveSelectedRate($options->all(), $payload);

        return [
            'provider' => $result['provider'] ?? $provider,
            'request' => $requestPayload,
            'selected_rate' => array_merge($selected, [
                'selected_at' => now()->toIso8601String(),
            ]),
            'options' => $options->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function buildPayload(Collection $items, array $payload, ?Company $company, string $provider): array
    {
        $companyMeta = is_array($company?->meta) ? $company->meta : [];
        $normalizedItems = $items
            ->filter(fn (array $item): bool => isset($item['product']) && $item['product'] instanceof Product && (int) ($item['qty'] ?? 0) > 0)
            ->values();

        if ($normalizedItems->isEmpty()) {
            throw new StorefrontCheckoutException(
                'Cart Anda masih kosong.',
                ['cart' => 'Tambahkan produk ke cart terlebih dahulu.'],
            );
        }

        $shippableItems = $normalizedItems
            ->filter(fn (array $item): bool => (bool) $item['product']->track_stock)
            ->values();

        if ($shippableItems->isEmpty()) {
            throw new StorefrontCheckoutException(
                'Cart ini tidak memerlukan pengiriman.',
                ['fulfillment_method' => 'Metode delivery hanya tersedia untuk produk fisik.'],
            );
        }

        $weight = 0;
        $maxLength = 0;
        $maxWidth = 0;
        $totalHeight = 0;

        foreach ($shippableItems as $item) {
            $product = $item['product'];
            $qty = max(1, (int) ($item['qty'] ?? 1));
            $productMeta = is_array($product->meta) ? $product->meta : [];
            $shippingMeta = is_array($productMeta['shipping'] ?? null) ? $productMeta['shipping'] : [];
            $itemWeight = $this->normalizedWeight($product, $shippingMeta);

            if ($itemWeight === null) {
                throw new StorefrontCheckoutException(
                    'Berat produk belum diatur untuk pengiriman.',
                    ['cart' => 'Masih ada produk fisik di cart yang belum memiliki berat pengiriman.'],
                );
            }

            $weight += $itemWeight * $qty;
            $maxLength = max($maxLength, $this->nullablePositiveInt($shippingMeta['length_cm'] ?? $shippingMeta['length'] ?? null) ?? 0);
            $maxWidth = max($maxWidth, $this->nullablePositiveInt($shippingMeta['width_cm'] ?? $shippingMeta['width'] ?? null) ?? 0);
            $totalHeight += ($this->nullablePositiveInt($shippingMeta['height_cm'] ?? $shippingMeta['height'] ?? null) ?? 0) * $qty;
        }

        $firstProduct = $normalizedItems->first()['product'];
        $totalQty = (int) $normalizedItems->sum(fn (array $item) => (int) ($item['qty'] ?? 0));
        $title = $normalizedItems->count() > 1
            ? $firstProduct->name . ' +' . ($normalizedItems->count() - 1) . ' item lain'
            : $firstProduct->name;

        $base = [
            'couriers' => trim((string) ($payload['couriers'] ?? '')),
            'item_name' => $title,
            'item_description' => $normalizedItems->count() > 1 ? 'Cart storefront multi-item' : ($firstProduct->description ?: null),
            'item_value' => (float) $normalizedItems->sum(fn (array $item) => (float) $item['product']->sell_price * (int) $item['qty']),
            'item_weight' => $weight,
            'item_quantity' => $totalQty,
            'item_length' => $maxLength > 0 ? $maxLength : null,
            'item_width' => $maxWidth > 0 ? $maxWidth : null,
            'item_height' => $totalHeight > 0 ? $totalHeight : null,
        ];

        if ($provider === 'biteship') {
            $base['origin_postal_code'] = trim((string) ($companyMeta['shipping_origin_postal_code'] ?? $companyMeta['postal_code'] ?? ''));
            $base['destination_postal_code'] = trim((string) ($payload['destination_postal_code'] ?? ''));

            if ($base['origin_postal_code'] === '') {
                throw new StorefrontCheckoutException(
                    'Origin pengiriman toko belum lengkap.',
                    ['fulfillment_method' => 'Origin pengiriman toko belum diatur.'],
                );
            }

            if ($base['destination_postal_code'] === '') {
                throw new StorefrontCheckoutException(
                    'Kode pos tujuan wajib diisi untuk delivery.',
                    ['destination_postal_code' => 'Kode pos tujuan wajib diisi untuk delivery.'],
                );
            }
        }

        if ($provider === 'rajaongkir') {
            $base['origin_area_id'] = trim((string) ($companyMeta['shipping_origin_area_id'] ?? ''));
            $base['destination_area_id'] = trim((string) ($payload['destination_area_id'] ?? ''));

            if ($base['origin_area_id'] === '') {
                throw new StorefrontCheckoutException(
                    'Origin pengiriman toko belum lengkap.',
                    ['fulfillment_method' => 'Origin area pengiriman toko belum diatur.'],
                );
            }

            if ($base['destination_area_id'] === '') {
                throw new StorefrontCheckoutException(
                    'Area tujuan wajib diisi untuk delivery.',
                    ['destination_area_id' => 'Area tujuan wajib diisi untuk delivery.'],
                );
            }
        }

        return array_filter($base, fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @param  array<int, array<string, mixed>>  $options
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function resolveSelectedRate(array $options, array $payload): array
    {
        if (count($options) === 1) {
            return $options[0];
        }

        $selectedKey = trim((string) ($payload['selected_shipping_rate'] ?? ''));

        if ($selectedKey === '') {
            throw new StorefrontCheckoutException(
                'Pilih layanan pengiriman terlebih dahulu.',
                ['selected_shipping_rate' => 'Pilih salah satu layanan pengiriman yang tersedia.'],
                ['storefront.shipping_options' => $options]
            );
        }

        foreach ($options as $option) {
            if ((string) ($option['selection_key'] ?? '') === $selectedKey) {
                return $option;
            }
        }

        throw new StorefrontCheckoutException(
            'Pilihan layanan pengiriman tidak lagi tersedia.',
            ['selected_shipping_rate' => 'Pilihan layanan pengiriman sudah berubah. Silakan pilih ulang.'],
            ['storefront.shipping_options' => $options]
        );
    }

    private function nullablePositiveInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : null;
    }

    /**
     * @param  array<string, mixed>  $shippingMeta
     */
    private function normalizedWeight(Product $product, array $shippingMeta): ?int
    {
        $weight = $shippingMeta['weight_grams'] ?? $shippingMeta['weight'] ?? null;

        if ($weight === null || $weight === '') {
            return $product->track_stock ? null : 1;
        }

        $normalized = (int) $weight;

        return $normalized > 0 ? $normalized : null;
    }
}
