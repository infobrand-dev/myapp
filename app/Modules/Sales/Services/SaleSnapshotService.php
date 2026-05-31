<?php

namespace App\Modules\Sales\Services;

use App\Modules\Contacts\Models\Contact;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\ProductVariant;

class SaleSnapshotService
{
    public function customerSnapshotFromPayload(?Contact $contact, array $payload = [], ?array $fallback = null): array
    {
        if ($contact) {
            return $this->customerSnapshot($contact);
        }

        $name = $this->nullableString($payload['customer_name'] ?? data_get($fallback, 'name'));
        $email = $this->nullableString($payload['customer_email'] ?? data_get($fallback, 'email'));
        $phone = $this->nullableString($payload['customer_phone'] ?? data_get($fallback, 'phone'));
        $address = $this->nullableString($payload['customer_address'] ?? data_get($fallback, 'address'));
        $taxAddress = $this->nullableString($payload['customer_tax_address'] ?? data_get($fallback, 'tax_address') ?? $address);

        if ($name === null && $email === null && $phone === null && $address === null && $taxAddress === null) {
            return [
                'name' => null,
                'email' => null,
                'phone' => null,
                'address' => null,
                'tax_address' => null,
                'payload' => null,
            ];
        }

        return [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'tax_address' => $taxAddress,
            'payload' => [
                'type' => 'guest',
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'mobile' => $phone,
                'tax_address' => $taxAddress,
                'tax_profile_complete' => false,
                'address' => [
                    'formatted' => $address,
                ],
            ],
        ];
    }

    public function customerSnapshot(?Contact $contact): array
    {
        if (!$contact) {
            return [
                'name' => null,
                'email' => null,
                'phone' => null,
                'address' => null,
                'tax_address' => null,
                'payload' => null,
            ];
        }

        $address = collect([
            $contact->street,
            $contact->street2,
            $contact->city,
            $contact->state,
            $contact->zip,
            $contact->country,
        ])->filter()->implode(', ');
        $taxAddress = $contact->tax_address ?: $contact->billing_address ?: $address;

        return [
            'name' => $contact->name,
            'email' => $contact->email,
            'phone' => $contact->mobile ?: $contact->phone,
            'address' => $address ?: null,
            'tax_address' => $taxAddress ?: null,
            'payload' => [
                'id' => $contact->id,
                'type' => $contact->type,
                'name' => $contact->name,
                'email' => $contact->email,
                'phone' => $contact->phone,
                'mobile' => $contact->mobile,
                'vat' => $contact->vat,
                'tax_name' => $contact->tax_name,
                'tax_address' => $taxAddress ?: null,
                'tax_is_pkp' => (bool) $contact->tax_is_pkp,
                'billing_address' => $contact->billing_address,
                'parent_contact_id' => $contact->parent_contact_id,
                'parent_contact_name' => $contact->parentContact ? $contact->parentContact->name : null,
                'scope_company_id' => $contact->company_id,
                'scope_branch_id' => $contact->branch_id,
                'address' => [
                    'street' => $contact->street,
                    'street2' => $contact->street2,
                    'city' => $contact->city,
                    'state' => $contact->state,
                    'zip' => $contact->zip,
                    'country' => $contact->country,
                ],
                'tax_profile_complete' => filled($contact->vat) && filled($contact->tax_name) && filled($taxAddress),
            ],
        ];
    }

    public function productSnapshot(Product $product, ?ProductVariant $variant = null): array
    {
        $meta = is_array($product->meta) ? $product->meta : [];
        $publicOffer = is_array(data_get($meta, 'public_offer')) ? data_get($meta, 'public_offer') : [];

        return [
            'product_name' => $product->name,
            'variant_name' => $variant ? $variant->name : null,
            'sku' => $variant && $variant->sku ? $variant->sku : $product->sku,
            'barcode' => $variant && $variant->barcode ? $variant->barcode : $product->barcode,
            'unit' => $product->unit ? $product->unit->name : null,
            'payload' => [
                'product_id' => $product->id,
                'product_variant_id' => $variant ? $variant->id : null,
                'product_name' => $product->name,
                'product_type' => $product->type,
                'variant_name' => $variant ? $variant->name : null,
                'sku' => $variant && $variant->sku ? $variant->sku : $product->sku,
                'barcode' => $variant && $variant->barcode ? $variant->barcode : $product->barcode,
                'unit' => $product->unit ? $product->unit->name : null,
                'track_stock' => $variant ? $variant->track_stock : $product->track_stock,
                'base_sell_price' => (float) ($variant ? $variant->sell_price : $product->sell_price),
                'attribute_summary' => $variant ? $variant->attribute_summary : null,
                'public_offer' => [
                    'visibility' => (string) ($publicOffer['visibility'] ?? 'catalog'),
                    'headline' => $this->nullableString($publicOffer['headline'] ?? null),
                    'subtitle' => $this->nullableString($publicOffer['subtitle'] ?? null),
                    'delivery_type' => (string) ($publicOffer['delivery_type'] ?? ($product->track_stock ? 'physical' : 'service')),
                    'delivery_instructions' => $this->nullableString($publicOffer['delivery_instructions'] ?? null),
                    'download_url' => $this->nullableString($publicOffer['download_url'] ?? null),
                    'external_url' => $this->nullableString($publicOffer['external_url'] ?? null),
                    'slot_note' => $this->nullableString($publicOffer['slot_note'] ?? null),
                    'cta_label' => $this->nullableString($publicOffer['cta_label'] ?? null),
                ],
            ],
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
