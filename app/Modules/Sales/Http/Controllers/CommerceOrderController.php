<?php

namespace App\Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Repositories\SaleRepository;
use App\Support\BranchContext;
use App\Support\Commerce\CommerceOrderLifecycleService;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommerceOrderController extends Controller
{
    private SaleRepository $repository;

    public function __construct(
        SaleRepository $repository,
        private readonly CommerceOrderLifecycleService $commerceOrders,
    ) {
        $this->repository = $repository;
    }

    public function index(Request $request): View
    {
        $filters = $request->only([
            'search',
            'status',
            'payment_status',
            'source',
            'contact_id',
            'date_from',
            'date_to',
            'commerce_status',
            'fulfillment_method',
            'shipping_status',
            'payment_state',
            'tab',
        ]);
        $filters = $this->applyQuickTabFilters($filters);
        $filters['source'] = $filters['source'] ?: Sale::SOURCE_ONLINE;

        $metricsQuery = Sale::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->where('source', Sale::SOURCE_ONLINE);
        BranchContext::applyScope($metricsQuery);
        $commerceSales = $metricsQuery->latest('transaction_date')->latest('id')->get();
        $shippingCollected = $commerceSales->sum(fn (Sale $sale) => (float) data_get($sale->totals_snapshot, 'shipping_total', data_get($sale->meta, 'commerce.shipping.amount', 0)));

        return view('sales::commerce.index', [
            'sales' => $this->paginateCommerceIndex($filters),
            'filters' => $filters,
            'metrics' => [
                'orders' => $commerceSales->count(),
                'gross' => (float) $commerceSales->sum('grand_total'),
                'shipping' => (float) $shippingCollected,
                'pending_payment' => $commerceSales->filter(fn (Sale $sale) => $this->commerceOrders->status($sale) === CommerceOrderLifecycleService::STATUS_PENDING_PAYMENT)->count(),
                'ready_to_ship' => $commerceSales->filter(fn (Sale $sale) => $this->commerceOrders->shippingStatus($sale) === CommerceOrderLifecycleService::SHIPPING_READY)->count(),
                'paid' => $commerceSales->where('payment_status', Sale::PAYMENT_PAID)->count(),
            ],
            'quickTabs' => [
                'all' => $commerceSales->count(),
                'pending_payment' => $commerceSales->filter(fn (Sale $sale) => $this->commerceOrders->status($sale) === CommerceOrderLifecycleService::STATUS_PENDING_PAYMENT)->count(),
                'ready_for_fulfillment' => $commerceSales->filter(fn (Sale $sale) => $this->commerceOrders->status($sale) === CommerceOrderLifecycleService::STATUS_READY_FOR_FULFILLMENT)->count(),
                'delivery' => $commerceSales->filter(fn (Sale $sale) => data_get($sale->meta, 'commerce.fulfillment_method') === 'delivery')->count(),
                'pickup' => $commerceSales->filter(fn (Sale $sale) => data_get($sale->meta, 'commerce.fulfillment_method') === 'pickup')->count(),
                'shipped' => $commerceSales->filter(fn (Sale $sale) => $this->commerceOrders->shippingStatus($sale) === CommerceOrderLifecycleService::SHIPPING_SHIPPED)->count(),
            ],
        ]);
    }

    private function applyQuickTabFilters(array $filters): array
    {
        $tab = (string) ($filters['tab'] ?? 'all');

        return match ($tab) {
            'pending_payment' => array_replace($filters, [
                'commerce_status' => CommerceOrderLifecycleService::STATUS_PENDING_PAYMENT,
            ]),
            'ready_for_fulfillment' => array_replace($filters, [
                'commerce_status' => CommerceOrderLifecycleService::STATUS_READY_FOR_FULFILLMENT,
            ]),
            'delivery' => array_replace($filters, [
                'fulfillment_method' => 'delivery',
            ]),
            'pickup' => array_replace($filters, [
                'fulfillment_method' => 'pickup',
            ]),
            'shipped' => array_replace($filters, [
                'shipping_status' => CommerceOrderLifecycleService::SHIPPING_SHIPPED,
            ]),
            default => $filters,
        };
    }

    private function paginateCommerceIndex(array $filters): LengthAwarePaginator
    {
        $query = Sale::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->where('source', Sale::SOURCE_ONLINE)
            ->with(['contact', 'creator'])
            ->withCount('items');

        BranchContext::applyScope($query);
        $this->applyCommerceFilters($query, $filters);

        return $query
            ->orderByDesc('transaction_date')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();
    }

    private function applyCommerceFilters(Builder $query, array $filters): void
    {
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('sale_number', 'like', "%{$search}%")
                    ->orWhere('external_reference', 'like', "%{$search}%")
                    ->orWhere('customer_name_snapshot', 'like', "%{$search}%")
                    ->orWhere('customer_phone_snapshot', 'like', "%{$search}%")
                    ->orWhereHas('items', function (Builder $item) use ($search): void {
                        $item->where(function (Builder $nested) use ($search): void {
                            $nested->where('product_name_snapshot', 'like', "%{$search}%")
                                ->orWhere('variant_name_snapshot', 'like', "%{$search}%")
                                ->orWhere('sku_snapshot', 'like', "%{$search}%");
                        });
                    });
            });
        }

        foreach (['payment_status', 'source'] as $field) {
            if (!empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        if (!empty($filters['commerce_status'])) {
            $query->where('meta->commerce->status', $filters['commerce_status']);
        }

        if (!empty($filters['fulfillment_method'])) {
            $query->where('meta->commerce->fulfillment_method', $filters['fulfillment_method']);
        }

        if (!empty($filters['shipping_status'])) {
            $query->where('meta->commerce->shipping->status', $filters['shipping_status']);
        }

        if (!empty($filters['payment_state'])) {
            $query->where('meta->commerce->payment->status', $filters['payment_state']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('transaction_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('transaction_date', '<=', $filters['date_to']);
        }
    }

    public function show(Sale $sale): View
    {
        return view('sales::commerce.show', [
            'sale' => $this->repository->findForDetail($sale),
        ]);
    }
}
