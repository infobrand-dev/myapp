<?php

namespace App\Modules\Products\Services;

use App\Models\User;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\ProductMedia;
use App\Modules\Products\Models\ProductOptionGroup;
use App\Modules\Products\Models\ProductOptionValue;
use App\Modules\Products\Models\ProductPriceLevel;
use App\Modules\Products\Models\ProductVariant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductService
{
    private const TENANT_ID = 1;

    private ProductLookupService $lookupService;

    public function __construct(ProductLookupService $lookupService)
    {
        $this->lookupService = $lookupService;
    }

    public function create(array $data, ?User $actor = null): Product
    {
        return DB::transaction(function () use ($data, $actor) {
            $data = $this->lookupService->resolveLookupIds($data);

            $product = Product::query()->create($this->productPayload($data, $actor, true));
            $this->syncProductGraph($product, $data);

            return $product->fresh();
        });
    }

    public function update(Product $product, array $data, ?User $actor = null): Product
    {
        return DB::transaction(function () use ($product, $data, $actor) {
            $data = $this->lookupService->resolveLookupIds($data);

            $product->update($this->productPayload($data, $actor, false));
            $this->syncProductGraph($product, $data);

            return $product->fresh();
        });
    }

    public function delete(Product $product, ?User $actor = null): void
    {
        DB::transaction(function () use ($product, $actor) {
            $product->deleted_by = $actor ? $actor->id : null;
            $product->save();
            $product->delete();
        });
    }

    public function toggleStatus(Product $product, ?bool $targetStatus = null): Product
    {
        $product->is_active = $targetStatus ?? !$product->is_active;
        $product->save();

        return $product->fresh();
    }

    public function bulkAction(array $productIds, string $action, ?User $actor = null): void
    {
        foreach (Product::query()->where('tenant_id', self::TENANT_ID)->whereIn('id', $productIds)->get() as $product) {
            if ($action === 'activate') {
                $this->toggleStatus($product, true);
            }

            if ($action === 'deactivate') {
                $this->toggleStatus($product, false);
            }

            if ($action === 'delete') {
                $this->delete($product, $actor);
            }
        }
    }

    private function syncProductGraph(Product $product, array $data): void
    {
        $this->syncProductPrices(
            $product,
            $this->normalizePriceLevels(
                $data['price_levels'] ?? [],
                [
                    'wholesale' => $data['wholesale_price'] ?? null,
                    'member' => $data['member_price'] ?? null,
                ]
            )
        );
        $this->syncProductMedia($product, $data);
        $this->syncVariants($product, $data['variants'] ?? []);
    }

    private function productPayload(array $data, ?User $actor, bool $isCreate): array
    {
        $slug = trim((string) ($data['slug'] ?? ''));
        if ($slug === '') {
            $slug = Str::slug((string) $data['name']);
        }

        $payload = [
            'tenant_id' => self::TENANT_ID,
            'type' => $data['type'],
            'category_id' => $data['category_id'] ?? null,
            'brand_id' => $data['brand_id'] ?? null,
            'unit_id' => $data['unit_id'] ?? null,
            'name' => trim((string) $data['name']),
            'slug' => $slug,
            'sku' => trim((string) $data['sku']),
            'barcode' => $this->nullableString($data['barcode'] ?? null),
            'description' => $this->nullableString($data['description'] ?? null),
            'cost_price' => $data['cost_price'] ?? 0,
            'sell_price' => $data['sell_price'] ?? 0,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'track_stock' => $data['type'] === 'service' ? false : (bool) ($data['track_stock'] ?? false),
            'meta' => [
                'module' => 'products',
                'inventory_managed' => true,
            ],
            'updated_by' => $actor ? $actor->id : null,
        ];

        if ($isCreate) {
            $payload['created_by'] = $actor ? $actor->id : null;
        }

        return $payload;
    }

    private function syncProductPrices(Product $product, array $prices): void
    {
        $product->prices()->delete();

        foreach ($prices as $priceRow) {
            $levelId = $priceRow['price_level_id'] ?? null;
            $price = $priceRow['price'] ?? null;
            if (!$levelId || $price === null || $price === '') {
                continue;
            }

            $product->prices()->create([
                'tenant_id' => self::TENANT_ID,
                'product_price_level_id' => $levelId,
                'currency_code' => 'IDR',
                'price' => $price,
                'minimum_qty' => $priceRow['minimum_qty'] ?? 1,
                'is_active' => true,
            ]);
        }
    }

    private function syncProductMedia(Product $product, array $data): void
    {
        if (!empty($data['remove_gallery_media_ids'])) {
            $mediaItems = ProductMedia::query()
                ->where('tenant_id', self::TENANT_ID)
                ->where('product_id', $product->id)
                ->whereIn('id', $data['remove_gallery_media_ids'])
                ->get();

            foreach ($mediaItems as $media) {
                Storage::disk($media->disk)->delete($media->path);
                $media->delete();
            }
        }

        if (!empty($data['featured_image']) && $data['featured_image'] instanceof UploadedFile) {
            if ($product->featured_image_path) {
                Storage::disk('public')->delete($product->featured_image_path);
            }

            $path = $data['featured_image']->store('products/featured', 'public');
            $product->update(['featured_image_path' => $path]);

            $product->media()->where('collection_name', 'primary')->delete();
            $product->media()->create([
                'tenant_id' => self::TENANT_ID,
                'disk' => 'public',
                'path' => $path,
                'collection_name' => 'primary',
                'sort_order' => 0,
                'alt_text' => $product->name,
            ]);
        }

        foreach (($data['gallery_images'] ?? []) as $index => $image) {
            if (!$image instanceof UploadedFile) {
                continue;
            }

            $path = $image->store('products/gallery', 'public');
            $product->media()->create([
                'tenant_id' => self::TENANT_ID,
                'disk' => 'public',
                'path' => $path,
                'collection_name' => 'gallery',
                'sort_order' => $index,
                'alt_text' => $product->name,
            ]);
        }
    }

    private function syncVariants(Product $product, array $variants): void
    {
        if ($product->type !== 'variant') {
            $product->variants()->get()->each(function (ProductVariant $variant) {
                $variant->prices()->delete();
                $variant->optionValues()->detach();
                $variant->delete();
            });
            $product->optionGroups()->delete();

            return;
        }

        $existingIds = $product->variants()->pluck('id')->all();
        $keptIds = [];
        $attributeMatrix = [];

        foreach (array_values($variants) as $index => $variantData) {
            $variant = ProductVariant::query()->updateOrCreate(
                [
                    'id' => $variantData['id'] ?? null,
                    'tenant_id' => self::TENANT_ID,
                    'product_id' => $product->id,
                ],
                [
                    'tenant_id' => self::TENANT_ID,
                    'name' => trim((string) $variantData['name']),
                    'attribute_summary' => $this->nullableString($variantData['attribute_summary'] ?? null),
                    'sku' => trim((string) $variantData['sku']),
                    'barcode' => $this->nullableString($variantData['barcode'] ?? null),
                    'cost_price' => $variantData['cost_price'] ?? 0,
                    'sell_price' => $variantData['sell_price'] ?? 0,
                    'is_active' => (bool) ($variantData['is_active'] ?? true),
                    'track_stock' => (bool) ($variantData['track_stock'] ?? $product->track_stock),
                    'position' => $index,
                    'meta' => [],
                ]
            );

            $this->syncVariantPrices(
                $variant,
                $this->normalizePriceLevels([], [
                    'wholesale' => $variantData['wholesale_price'] ?? null,
                    'member' => $variantData['member_price'] ?? null,
                ])
            );

            $keptIds[] = $variant->id;
            $attributeMatrix[$variant->id] = $this->parseAttributeSummary($variant->attribute_summary);
        }

        $toDeleteIds = array_diff($existingIds, $keptIds);
        if (!empty($toDeleteIds)) {
            ProductVariant::query()->where('tenant_id', self::TENANT_ID)->whereIn('id', $toDeleteIds)->get()->each(function (ProductVariant $variant) {
                $variant->optionValues()->detach();
                $variant->delete();
            });
        }

        $this->syncOptionMatrix($product, $attributeMatrix);
    }

    private function syncOptionMatrix(Product $product, array $attributeMatrix): void
    {
        $groupNames = collect($attributeMatrix)
            ->flatMap(fn (array $attributes) => array_keys($attributes))
            ->filter()
            ->unique()
            ->values();

        $existingGroups = $product->optionGroups()->with('values')->get()->keyBy('name');
        $groupIdsToKeep = [];
        $valueIdsToKeep = [];

        foreach ($groupNames as $groupIndex => $groupName) {
            $group = $existingGroups->get($groupName)
                ?? $product->optionGroups()->create([
                    'tenant_id' => self::TENANT_ID,
                    'name' => $groupName,
                    'sort_order' => $groupIndex,
                ]);

            $group->sort_order = $groupIndex;
            $group->save();
            $groupIdsToKeep[] = $group->id;

            $existingValues = $group->values()->get()->keyBy('value');
            $values = collect($attributeMatrix)
                ->map(fn (array $attributes) => $attributes[$groupName] ?? null)
                ->filter()
                ->unique()
                ->values();

            foreach ($values as $valueIndex => $valueName) {
                $value = $existingValues->get($valueName)
                    ?? $group->values()->create([
                        'tenant_id' => self::TENANT_ID,
                        'value' => $valueName,
                        'sort_order' => $valueIndex,
                    ]);

                $value->sort_order = $valueIndex;
                $value->save();
                $valueIdsToKeep[] = $value->id;
            }
        }

        $product->optionGroups()->whereNotIn('id', $groupIdsToKeep ?: [0])->delete();

        ProductOptionValue::query()
            ->whereHas('group', fn ($query) => $query->where('product_id', $product->id))
            ->whereNotIn('id', $valueIdsToKeep ?: [0])
            ->delete();

        foreach ($attributeMatrix as $variantId => $attributes) {
            $variant = ProductVariant::query()
                ->where('tenant_id', self::TENANT_ID)
                ->find($variantId);
            if (!$variant) {
                continue;
            }

            $optionValueIds = [];
            foreach ($attributes as $groupName => $valueName) {
                $group = ProductOptionGroup::query()
                    ->where('tenant_id', self::TENANT_ID)
                    ->where('product_id', $product->id)
                    ->where('name', $groupName)
                    ->first();

                if (!$group) {
                    continue;
                }

                $value = ProductOptionValue::query()
                    ->where('tenant_id', self::TENANT_ID)
                    ->where('product_option_group_id', $group->id)
                    ->where('value', $valueName)
                    ->first();

                if ($value) {
                    $optionValueIds[] = $value->id;
                }
            }

            $variant->optionValues()->sync($optionValueIds);
        }
    }

    private function syncVariantPrices(ProductVariant $variant, array $prices): void
    {
        $variant->prices()->delete();

        foreach ($prices as $priceRow) {
            $levelId = $priceRow['price_level_id'] ?? null;
            $price = $priceRow['price'] ?? null;
            if (!$levelId || $price === null || $price === '') {
                continue;
            }

            $variant->prices()->create([
                'tenant_id' => self::TENANT_ID,
                'product_id' => $variant->product_id,
                'product_price_level_id' => $levelId,
                'currency_code' => 'IDR',
                'price' => $price,
                'minimum_qty' => $priceRow['minimum_qty'] ?? 1,
                'is_active' => true,
            ]);
        }
    }

    private function parseAttributeSummary(?string $summary): array
    {
        $summary = trim((string) $summary);
        if ($summary === '') {
            return [];
        }

        $attributes = [];
        foreach (preg_split('/\s*\|\s*/', $summary) as $pair) {
            [$name, $value] = array_pad(explode(':', $pair, 2), 2, null);
            $name = trim((string) $name);
            $value = trim((string) $value);

            if ($name === '' || $value === '') {
                continue;
            }

            $attributes[$name] = $value;
        }

        return $attributes;
    }

    private function nullableString($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizePriceLevels(array $prices, array $baseTierPrices = []): array
    {
        $levelsByCode = ProductPriceLevel::query()
            ->where('tenant_id', self::TENANT_ID)
            ->whereIn('code', array_keys($baseTierPrices))
            ->get()
            ->keyBy('code');

        $normalized = collect($prices)
            ->filter(fn ($row) => is_array($row))
            ->map(function (array $row) {
                return [
                    'price_level_id' => $row['price_level_id'] ?? null,
                    'price' => $row['price'] ?? null,
                    'minimum_qty' => $row['minimum_qty'] ?? 1,
                ];
            });

        foreach ($baseTierPrices as $code => $price) {
            $level = $levelsByCode->get($code);
            if (!$level) {
                continue;
            }

            $normalized = $normalized
                ->reject(fn (array $row) => (int) ($row['price_level_id'] ?? 0) === (int) $level->id)
                ->values();

            if ($price === null || $price === '') {
                continue;
            }

            $normalized->push([
                'price_level_id' => $level->id,
                'price' => $price,
                'minimum_qty' => 1,
            ]);
        }

        return $normalized
            ->filter(fn (array $row) => !empty($row['price_level_id']) && $row['price'] !== null && $row['price'] !== '')
            ->values()
            ->all();
    }
}
