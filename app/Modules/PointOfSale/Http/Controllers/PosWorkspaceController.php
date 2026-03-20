<?php

namespace App\Modules\PointOfSale\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Payments\Models\PaymentMethod;
use App\Modules\PointOfSale\Models\PosCart;
use App\Modules\PointOfSale\Services\PosCashSessionService;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\ProductVariant;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosWorkspaceController extends Controller
{
    private $cashSessions;

    public function __construct(PosCashSessionService $cashSessions)
    {
        $this->cashSessions = $cashSessions;
    }

    public function show(Request $request): JsonResponse
    {
        $activeShift = $this->cashSessions->activeSessionFor($request->user());

        return response()->json([
            'products' => $this->productOptions(),
            'customers' => $this->customerOptions(),
            'payment_methods' => $this->paymentMethodOptions(),
            'active_shift' => $activeShift ? [
                'id' => $activeShift->id,
                'code' => $activeShift->code,
                'branch_id' => $activeShift->branch_id,
                'opened_at' => $activeShift->opened_at ? $activeShift->opened_at->toDateTimeString() : null,
            ] : null,
            'held_count' => PosCart::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('cashier_user_id', $request->user()->id)
                ->where('status', PosCart::STATUS_HELD)
                ->tap(fn ($query) => BranchContext::applyScope($query))
                ->count(),
        ]);
    }

    public function searchProducts(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if ($q === '') {
            return response()->json([
                'data' => $this->productOptions(),
            ]);
        }

        $products = Product::query()
            ->with(['unit', 'variants'])
            ->where('is_active', true)
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', '%' . $q . '%')
                    ->orWhere('sku', 'like', '%' . $q . '%')
                    ->orWhere('barcode', 'like', '%' . $q . '%');
            })
            ->orderBy('name')
            ->limit(24)
            ->get();

        $variants = ProductVariant::query()
            ->with(['product.unit'])
            ->where('is_active', true)
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', '%' . $q . '%')
                    ->orWhere('sku', 'like', '%' . $q . '%')
                    ->orWhere('barcode', 'like', '%' . $q . '%');
            })
            ->orderBy('name')
            ->limit(24)
            ->get();

        return response()->json([
            'data' => collect()
                ->merge($products->map(function (Product $product) {
                    return $this->serializeProduct($product);
                }))
                ->merge($variants->map(function (ProductVariant $variant) {
                    return $this->serializeVariant($variant);
                }))
                ->unique(function (array $item) {
                    return $item['sellable_key'];
                })
                ->values()
                ->all(),
        ]);
    }

    public function searchCustomers(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        $query = Contact::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('is_active', true)
            ->orderBy('name');

        if ($q !== '') {
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', '%' . $q . '%')
                    ->orWhere('email', 'like', '%' . $q . '%')
                    ->orWhere('phone', 'like', '%' . $q . '%')
                    ->orWhere('mobile', 'like', '%' . $q . '%');
            });
        }

        return response()->json([
            'data' => $query->limit(20)->get()->map(function (Contact $contact) {
                return [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'phone' => $contact->mobile ?: $contact->phone,
                    'email' => $contact->email,
                ];
            })->values()->all(),
        ]);
    }

    private function productOptions(): array
    {
        return Product::query()
            ->with('unit')
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(18)
            ->get()
            ->map(function (Product $product) {
                return $this->serializeProduct($product);
            })
            ->values()
            ->all();
    }

    private function customerOptions(): array
    {
        return Contact::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(12)
            ->get()
            ->map(function (Contact $contact) {
                return [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'phone' => $contact->mobile ?: $contact->phone,
                    'email' => $contact->email,
                ];
            })
            ->values()
            ->all();
    }

    private function paymentMethodOptions(): array
    {
        return PaymentMethod::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(function (PaymentMethod $method) {
                return [
                    'id' => $method->id,
                    'code' => $method->code,
                    'name' => $method->name,
                    'type' => $method->type,
                    'requires_reference' => (bool) $method->requires_reference,
                ];
            })
            ->values()
            ->all();
    }

    private function serializeProduct(Product $product): array
    {
        return [
            'sellable_key' => 'product:' . $product->id,
            'product_id' => $product->id,
            'product_variant_id' => null,
            'name' => $product->name,
            'variant_name' => null,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'price' => (float) $product->sell_price,
            'unit' => $product->unit ? $product->unit->name : null,
        ];
    }

    private function serializeVariant(ProductVariant $variant): array
    {
        return [
            'sellable_key' => 'variant:' . $variant->id,
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'name' => $variant->product ? $variant->product->name : $variant->name,
            'variant_name' => $variant->name,
            'sku' => $variant->sku,
            'barcode' => $variant->barcode,
            'price' => (float) $variant->sell_price,
            'unit' => $variant->product && $variant->product->unit ? $variant->product->unit->name : null,
        ];
    }
}
