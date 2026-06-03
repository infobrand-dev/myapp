<?php

namespace App\Modules\Storefront\Services;

use App\Models\Tenant;
use App\Services\StorageAccessService;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class BrandPageService
{
    private const DEFAULT_SECTIONS = [
        'hero' => ['label' => 'Hero', 'enabled' => true, 'order' => 10],
        'about' => ['label' => 'About', 'enabled' => true, 'order' => 20],
        'featured_offers' => ['label' => 'Featured Offers', 'enabled' => true, 'order' => 30],
        'catalog' => ['label' => 'Catalog', 'enabled' => true, 'order' => 40],
        'testimonials' => ['label' => 'Testimonials', 'enabled' => false, 'order' => 50],
        'faq' => ['label' => 'FAQ', 'enabled' => false, 'order' => 60],
        'footer_links' => ['label' => 'Footer Links', 'enabled' => true, 'order' => 70],
    ];

    /**
     * @return array<string, mixed>
     */
    public function profile(?Tenant $tenant = null): array
    {
        $tenant ??= TenantContext::currentTenant();
        $tenantMeta = is_array($tenant?->meta) ? $tenant->meta : [];
        $brand = is_array(data_get($tenantMeta, 'commerce_creator.brand')) ? data_get($tenantMeta, 'commerce_creator.brand') : [];

        $name = $this->stringValue($brand['name'] ?? $tenantMeta['public_brand_name'] ?? $tenant?->name ?? config('app.name'));
        $description = $this->nullableString($brand['description'] ?? $tenantMeta['public_brand_description'] ?? null);
        $heroTitle = $this->stringValue($brand['hero_title'] ?? $name);
        $heroSubtitle = $this->nullableString(
            $brand['hero_subtitle']
                ?? $description
                ?? 'Katalog, layanan, dan offer digital dari brand ini tersedia langsung dari halaman publik.'
        );

        return [
            'name' => $name,
            'description' => $description,
            'hero_title' => $heroTitle,
            'hero_subtitle' => $heroSubtitle,
            'logo_path' => $this->nullableString($brand['logo_path'] ?? $tenantMeta['public_brand_logo_path'] ?? null),
            'logo_url' => $this->assetUrl($brand['logo_path'] ?? $tenantMeta['public_brand_logo_path'] ?? null),
            'cover_path' => $this->nullableString($brand['cover_path'] ?? null),
            'cover_url' => $this->assetUrl($brand['cover_path'] ?? null),
            'accent' => $this->stringValue($brand['accent'] ?? '#223756'),
            'cta_links' => $this->normalizeLinks($brand['cta_links'] ?? []),
            'testimonials' => $this->normalizeTextRows($brand['testimonials'] ?? [], ['quote', 'author']),
            'faq' => $this->normalizeTextRows($brand['faq'] ?? [], ['question', 'answer']),
            'footer_links' => $this->normalizeLinks($brand['footer_links'] ?? []),
            'sections' => $this->normalizeSections($brand['sections'] ?? []),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function updateFromRequest(Request $request, ?Tenant $tenant = null): array
    {
        $tenant ??= TenantContext::currentTenant();
        abort_unless($tenant, 404);

        $validated = validator($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'hero_title' => ['nullable', 'string', 'max:255'],
            'hero_subtitle' => ['nullable', 'string', 'max:1000'],
            'accent' => ['nullable', 'string', 'max:20'],
            'cta_links' => ['nullable', 'array'],
            'cta_links.*.label' => ['nullable', 'string', 'max:80'],
            'cta_links.*.url' => ['nullable', 'string', 'max:500'],
            'footer_links' => ['nullable', 'array'],
            'footer_links.*.label' => ['nullable', 'string', 'max:80'],
            'footer_links.*.url' => ['nullable', 'string', 'max:500'],
            'testimonials' => ['nullable', 'array'],
            'testimonials.*.quote' => ['nullable', 'string', 'max:500'],
            'testimonials.*.author' => ['nullable', 'string', 'max:120'],
            'faq' => ['nullable', 'array'],
            'faq.*.question' => ['nullable', 'string', 'max:255'],
            'faq.*.answer' => ['nullable', 'string', 'max:1000'],
            'sections' => ['nullable', 'array'],
            'sections.*.enabled' => ['nullable', 'boolean'],
            'sections.*.order' => ['nullable', 'integer', 'min:1', 'max:999'],
        ])->validate();

        $meta = is_array($tenant->meta) ? $tenant->meta : [];
        data_set($meta, 'commerce_creator.brand', [
            'name' => $this->stringValue($validated['name'] ?? $tenant->name),
            'description' => $this->nullableString($validated['description'] ?? null),
            'hero_title' => $this->nullableString($validated['hero_title'] ?? null),
            'hero_subtitle' => $this->nullableString($validated['hero_subtitle'] ?? null),
            'accent' => $this->stringValue($validated['accent'] ?? '#223756'),
            'cta_links' => $this->normalizeLinks($validated['cta_links'] ?? []),
            'footer_links' => $this->normalizeLinks($validated['footer_links'] ?? []),
            'testimonials' => $this->normalizeTextRows($validated['testimonials'] ?? [], ['quote', 'author']),
            'faq' => $this->normalizeTextRows($validated['faq'] ?? [], ['question', 'answer']),
            'sections' => $this->normalizeSections($validated['sections'] ?? []),
        ]);

        $tenant->update(['meta' => $meta]);

        return $this->profile($tenant->fresh());
    }

    /**
     * @return array<int, array{key:string,label:string,enabled:bool,order:int}>
     */
    private function normalizeSections(array $sections): array
    {
        if (array_is_list($sections)) {
            $sections = collect($sections)
                ->filter(fn ($row) => is_array($row) && !empty($row['key']))
                ->keyBy(fn (array $row) => (string) $row['key'])
                ->all();
        }

        $normalized = [];

        foreach (self::DEFAULT_SECTIONS as $key => $defaults) {
            $payload = is_array($sections[$key] ?? null) ? $sections[$key] : [];
            $normalized[] = [
                'key' => $key,
                'label' => $defaults['label'],
                'enabled' => filter_var($payload['enabled'] ?? $defaults['enabled'], FILTER_VALIDATE_BOOLEAN),
                'order' => max(1, (int) ($payload['order'] ?? $defaults['order'])),
            ];
        }

        return collect($normalized)->sortBy('order')->values()->all();
    }

    /**
     * @param  array<int, mixed>  $rows
     * @param  array<int, string>  $fields
     * @return array<int, array<string, string>>
     */
    private function normalizeTextRows(array $rows, array $fields): array
    {
        return collect($rows)
            ->filter(fn ($row) => is_array($row))
            ->map(function (array $row) use ($fields): array {
                $normalized = [];

                foreach ($fields as $field) {
                    $normalized[$field] = $this->stringValue($row[$field] ?? '');
                }

                return $normalized;
            })
            ->filter(fn (array $row) => collect($row)->filter()->isNotEmpty())
            ->values()
            ->all();
    }

    /**
     * @param  array<int, mixed>  $links
     * @return array<int, array{label:string,url:string}>
     */
    private function normalizeLinks(array $links): array
    {
        return collect($links)
            ->filter(fn ($row) => is_array($row))
            ->map(fn (array $row): array => [
                'label' => $this->stringValue($row['label'] ?? ''),
                'url' => $this->stringValue($row['url'] ?? ''),
            ])
            ->filter(fn (array $row) => $row['label'] !== '' && $row['url'] !== '')
            ->values()
            ->all();
    }

    private function assetUrl(mixed $path): ?string
    {
        $path = $this->nullableString($path);

        if ($path === null) {
            return null;
        }

        return app(StorageAccessService::class)->publicUrlFromPath($path, 'public');
    }

    private function stringValue(mixed $value): string
    {
        return trim((string) $value);
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
