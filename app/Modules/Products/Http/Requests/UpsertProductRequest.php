<?php

namespace App\Modules\Products\Http\Requests;

use App\Modules\Products\Models\ProductBrand;
use App\Modules\Products\Models\ProductCategory;
use App\Modules\Products\Models\ProductMedia;
use App\Modules\Products\Models\ProductPriceLevel;
use App\Modules\Products\Models\ProductUnit;
use App\Modules\Products\Models\ProductVariant;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpsertProductRequest extends FormRequest
{

    public function authorize(): bool
    {
        $user = $this->user();

        if (!$user) {
            return false;
        }

        return $this->route('product')
            ? $user->can('products.update')
            : $user->can('products.create');
    }

    public function rules(): array
    {
        $product = $this->route('product');
        $productId = $product ? $product->id : null;

        return [
            'type' => ['required', Rule::in(['simple', 'variant', 'service'])],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'slug')->ignore($productId)->where(
                    fn ($query) => $query
                        ->where('tenant_id', TenantContext::currentId())
                        ->whereNull('deleted_at')
                ),
            ],
            'sku' => ['nullable', 'string', 'max:100'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'integer', Rule::exists('product_categories', 'id')->where(fn ($query) => $query->where('tenant_id', TenantContext::currentId()))],
            'brand_id' => ['nullable', 'integer', Rule::exists('product_brands', 'id')->where(fn ($query) => $query->where('tenant_id', TenantContext::currentId()))],
            'unit_id' => ['nullable', 'integer', Rule::exists('product_units', 'id')->where(fn ($query) => $query->where('tenant_id', TenantContext::currentId()))],
            'new_category_name' => ['nullable', 'string', 'max:255'],
            'new_brand_name' => ['nullable', 'string', 'max:255'],
            'new_unit_name' => ['nullable', 'string', 'max:255'],
            'new_unit_code' => ['nullable', 'string', 'max:50'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'sell_price' => ['required', 'numeric', 'min:0'],
            'wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'member_price' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'track_stock' => ['nullable', 'boolean'],
            'featured_image' => ['nullable', 'image', 'max:4096'],
            'gallery_images' => ['nullable', 'array'],
            'gallery_images.*' => ['image', 'max:4096'],
            'remove_gallery_media_ids' => ['nullable', 'array'],
            'remove_gallery_media_ids.*' => ['integer', Rule::exists('product_media', 'id')->where(fn ($query) => $query->where('tenant_id', TenantContext::currentId()))],
            'price_levels' => ['nullable', 'array'],
            'price_levels.*.price_level_id' => ['nullable', 'integer', Rule::exists('product_price_levels', 'id')->where(fn ($query) => $query->where('tenant_id', TenantContext::currentId()))],
            'price_levels.*.price' => ['nullable', 'numeric', 'min:0'],
            'price_levels.*.minimum_qty' => ['nullable', 'numeric', 'min:1'],
            'variants' => ['nullable', 'array'],
            'variants.*.id' => ['nullable', 'integer', Rule::exists('product_variants', 'id')->where(fn ($query) => $query->where('tenant_id', TenantContext::currentId()))],
            'variants.*.name' => ['nullable', 'string', 'max:255'],
            'variants.*.attribute_summary' => ['nullable', 'string', 'max:255'],
            'variants.*.sku' => ['nullable', 'string', 'max:100'],
            'variants.*.barcode' => ['nullable', 'string', 'max:100'],
            'variants.*.cost_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.sell_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.member_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.is_active' => ['nullable', 'boolean'],
            'variants.*.track_stock' => ['nullable', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            fn (Validator $validator) => $this->validateTenantRelations($validator),
            fn (Validator $validator) => $this->validateBusinessRules($validator),
        ];
    }

    protected function prepareForValidation(): void
    {
        $type = (string) $this->input('type', 'simple');
        $priceLevels = collect($this->input('price_levels', []))
            ->map(function ($row) {
                if (!is_array($row)) {
                    return $row;
                }

                if (($row['price'] ?? null) === '') {
                    $row['price'] = null;
                }

                return $row;
            })
            ->values()
            ->all();

        $variants = collect($this->input('variants', []))
            ->map(function ($variant) {
                if (!is_array($variant)) {
                    return $variant;
                }

                foreach (['barcode', 'attribute_summary'] as $field) {
                    if (array_key_exists($field, $variant) && trim((string) $variant[$field]) === '') {
                        $variant[$field] = null;
                    }
                }

                if (array_key_exists('sku', $variant) && trim((string) $variant['sku']) === '') {
                    $variant['sku'] = null;
                }

                foreach (['is_active', 'track_stock'] as $field) {
                    $variant[$field] = filter_var($variant[$field] ?? false, FILTER_VALIDATE_BOOLEAN);
                }

                return $variant;
            })
            ->values()
            ->all();

        $this->merge([
            'slug' => null,
            'sku' => $this->filled('sku') ? trim((string) $this->input('sku')) : null,
            'barcode' => $this->filled('barcode') ? trim((string) $this->input('barcode')) : null,
            'is_active' => $this->boolean('is_active'),
            'track_stock' => $type === 'service'
                ? false
                : ($this->has('track_stock') ? $this->boolean('track_stock') : true),
            'price_levels' => $priceLevels,
            'variants' => $variants,
        ]);
    }

    private function validateBusinessRules(Validator $validator): void
    {
        $product = $this->route('product');
        $productId = $product ? $product->id : null;
        $variants = collect($this->input('variants', []))->filter(fn ($variant) => is_array($variant))->values();
        $seenSkus = collect([trim((string) $this->input('sku'))])->filter()->values();
        $seenBarcodes = collect([trim((string) $this->input('barcode'))])->filter()->values();

        $variantsSubmitted = $this->has('variants');

        if ($this->input('type') === 'variant' && $variants->isEmpty() && (!$productId || $variantsSubmitted)) {
            $validator->errors()->add('variants', 'Produk variant wajib memiliki minimal satu child variant.');
        }

        $this->validateIdentityUniqueness($validator, trim((string) $this->input('sku')), trim((string) $this->input('barcode')), $productId, null, 'sku', 'barcode');

        foreach ($variants as $index => $variant) {
            $row = $index + 1;
            $sku = trim((string) ($variant['sku'] ?? ''));
            $barcode = trim((string) ($variant['barcode'] ?? ''));

            if ($this->input('type') === 'variant' && trim((string) ($variant['name'] ?? '')) === '') {
                $validator->errors()->add("variants.{$index}.name", "Nama variant pada baris {$row} wajib diisi.");
            }

            if ($sku !== '' && $seenSkus->contains($sku)) {
                $validator->errors()->add("variants.{$index}.sku", "SKU variant pada baris {$row} duplikat.");
            }

            if ($barcode !== '' && $seenBarcodes->contains($barcode)) {
                $validator->errors()->add("variants.{$index}.barcode", "Barcode variant pada baris {$row} duplikat.");
            }

            if (trim((string) ($variant['attribute_summary'] ?? '')) !== '') {
                foreach (preg_split('/\s*\|\s*/', (string) $variant['attribute_summary']) as $pair) {
                    if (!str_contains($pair, ':')) {
                        $validator->errors()->add("variants.{$index}.attribute_summary", "Format atribut variant pada baris {$row} harus seperti Ukuran:M|Warna:Merah.");
                        break;
                    }
                }
            }

            $this->validateIdentityUniqueness(
                $validator,
                $sku,
                $barcode,
                $productId,
                $variant['id'] ?? null,
                "variants.{$index}.sku",
                "variants.{$index}.barcode",
                $row
            );

            if ($sku !== '') {
                $seenSkus->push($sku);
            }

            if ($barcode !== '') {
                $seenBarcodes->push($barcode);
            }
        }
    }

    private function validateTenantRelations(Validator $validator): void
    {
        $this->validateLookupBelongsToTenant(
            $validator,
            'category_id',
            ProductCategory::class,
            'Kategori tidak tersedia untuk tenant aktif.'
        );
        $this->validateLookupBelongsToTenant(
            $validator,
            'brand_id',
            ProductBrand::class,
            'Brand tidak tersedia untuk tenant aktif.'
        );
        $this->validateLookupBelongsToTenant(
            $validator,
            'unit_id',
            ProductUnit::class,
            'Unit tidak tersedia untuk tenant aktif.'
        );

        foreach ((array) $this->input('remove_gallery_media_ids', []) as $index => $mediaId) {
            if (!ProductMedia::query()->where('tenant_id', TenantContext::currentId())->find($mediaId)) {
                $validator->errors()->add("remove_gallery_media_ids.$index", 'Media gallery tidak tersedia untuk tenant aktif.');
            }
        }

        foreach ((array) $this->input('price_levels', []) as $index => $priceLevel) {
            $priceLevelId = is_array($priceLevel) ? ($priceLevel['price_level_id'] ?? null) : null;
            if ($priceLevelId && !ProductPriceLevel::query()->where('tenant_id', TenantContext::currentId())->find($priceLevelId)) {
                $validator->errors()->add("price_levels.$index.price_level_id", 'Level harga tidak tersedia untuk tenant aktif.');
            }
        }

        foreach ((array) $this->input('variants', []) as $index => $variant) {
            $variantId = is_array($variant) ? ($variant['id'] ?? null) : null;
            if ($variantId && !ProductVariant::query()->where('tenant_id', TenantContext::currentId())->find($variantId)) {
                $validator->errors()->add("variants.$index.id", 'Variant tidak tersedia untuk tenant aktif.');
            }
        }
    }

    private function validateLookupBelongsToTenant(
        Validator $validator,
        string $field,
        string $modelClass,
        string $message
    ): void {
        $value = $this->input($field);
        if (!$value) {
            return;
        }

        if (!$modelClass::query()->where('tenant_id', TenantContext::currentId())->find($value)) {
            $validator->errors()->add($field, $message);
        }
    }

    private function validateIdentityUniqueness(
        Validator $validator,
        string $sku,
        string $barcode,
        ?int $productId,
        ?int $variantId,
        string $skuKey,
        string $barcodeKey,
        ?int $row = null
    ): void {
        if ($sku !== '') {
            $productSkuExists = DB::table('products')
                ->where('tenant_id', TenantContext::currentId())
                ->where('sku', $sku)
                ->whereNull('deleted_at')
                ->when($productId, fn ($query) => $query->where('id', '!=', $productId))
                ->exists();

            $variantSkuExists = DB::table('product_variants')
                ->where('tenant_id', TenantContext::currentId())
                ->where('sku', $sku)
                ->whereNull('deleted_at')
                ->when($variantId, fn ($query) => $query->where('id', '!=', $variantId))
                ->exists();

            if ($productSkuExists || $variantSkuExists) {
                $validator->errors()->add($skuKey, $row ? "SKU variant pada baris {$row} harus unik." : 'SKU harus unik di seluruh produk dan varian.');
            }
        }

        if ($barcode !== '') {
            $productBarcodeExists = DB::table('products')
                ->where('tenant_id', TenantContext::currentId())
                ->where('barcode', $barcode)
                ->whereNull('deleted_at')
                ->when($productId, fn ($query) => $query->where('id', '!=', $productId))
                ->exists();

            $variantBarcodeExists = DB::table('product_variants')
                ->where('tenant_id', TenantContext::currentId())
                ->where('barcode', $barcode)
                ->whereNull('deleted_at')
                ->when($variantId, fn ($query) => $query->where('id', '!=', $variantId))
                ->exists();

            if ($productBarcodeExists || $variantBarcodeExists) {
                $validator->errors()->add($barcodeKey, $row ? "Barcode variant pada baris {$row} harus unik." : 'Barcode harus unik jika diisi.');
            }
        }
    }
}
