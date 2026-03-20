<?php

namespace App\Modules\Sales\Services;

use App\Modules\Contacts\Models\Contact;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\ProductVariant;

class SaleSnapshotService
{
    public function customerSnapshot(?Contact $contact): array
    {
        if (!$contact) {
            return [
                'name' => null,
                'email' => null,
                'phone' => null,
                'address' => null,
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

        return [
            'name' => $contact->name,
            'email' => $contact->email,
            'phone' => $contact->mobile ?: $contact->phone,
            'address' => $address ?: null,
            'payload' => [
                'id' => $contact->id,
                'type' => $contact->type,
                'name' => $contact->name,
                'email' => $contact->email,
                'phone' => $contact->phone,
                'mobile' => $contact->mobile,
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
            ],
        ];
    }

    public function productSnapshot(Product $product, ?ProductVariant $variant = null): array
    {
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
            ],
        ];
    }
}
