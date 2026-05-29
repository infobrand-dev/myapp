<?php

namespace App\Modules\Shipping\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Shipping\Http\Requests\ShippingQuoteRequest;
use App\Modules\Sales\Models\Sale;
use App\Support\BranchContext;
use App\Support\Commerce\CommerceOrderLifecycleService;
use App\Support\Commerce\CommerceSalePricingService;
use App\Support\CompanyContext;
use App\Support\Shipping\ShippingProviderManager;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShippingController extends Controller
{
    public function __construct(
        private readonly CommerceOrderLifecycleService $commerceOrders,
        private readonly CommerceSalePricingService $commercePricing,
    ) {
    }

    public function __invoke(Request $request, ShippingProviderManager $shippingProviders): View
    {
        $activeProvider = $shippingProviders->activeProviderRecord();
        $driver = $shippingProviders->driver();
        $filter = (string) $request->query('filter', 'active');
        $orders = Sale::query()
            ->where('tenant_id', \App\Support\TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->where('source', Sale::SOURCE_ONLINE)
            ->where('payment_status', Sale::PAYMENT_PAID)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->get()
            ->filter(fn (Sale $sale) => $this->commerceOrders->isCommerceOrder($sale)
                && data_get($sale->meta, 'commerce.fulfillment_method') === 'delivery')
            ->values();
        $waitingRate = $orders->filter(fn (Sale $sale) => empty(data_get($sale->meta, 'commerce.shipping.selected_rate')))->values();
        $readyQueue = $orders->filter(fn (Sale $sale) => !empty(data_get($sale->meta, 'commerce.shipping.selected_rate'))
            && in_array($this->commerceOrders->shippingStatus($sale), [
                CommerceOrderLifecycleService::SHIPPING_PENDING,
                CommerceOrderLifecycleService::SHIPPING_READY,
            ], true))->values();
        $shippedQueue = $orders->filter(fn (Sale $sale) => $this->commerceOrders->shippingStatus($sale) === CommerceOrderLifecycleService::SHIPPING_SHIPPED)->values();
        $attentionQueue = $orders->filter(fn (Sale $sale) => empty(data_get($sale->meta, 'commerce.shipping.selected_rate'))
            || data_get($sale->meta, 'commerce.shipping.status') === CommerceOrderLifecycleService::SHIPPING_PENDING)->values();
        $filteredOrders = match ($filter) {
            'waiting_rate' => $waitingRate,
            'ready_to_ship' => $readyQueue,
            'shipped' => $shippedQueue,
            'attention' => $attentionQueue,
            default => $orders,
        };
        $shippedToday = $orders->filter(function (Sale $sale): bool {
            if ($this->commerceOrders->shippingStatus($sale) !== CommerceOrderLifecycleService::SHIPPING_SHIPPED) {
                return false;
            }

            $shippedAt = data_get($sale->meta, 'commerce.shipping.shipped_at');

            return is_string($shippedAt) && str_starts_with($shippedAt, now()->toDateString());
        })->count();

        return view('shipping::index', [
            'activeShippingProvider' => $activeProvider,
            'activeShippingProviderLabel' => $shippingProviders->activeProviderLabel(),
            'activeShippingProviderConfigured' => $driver ? $driver->isConfigured() : false,
            'quoteResult' => $request->session()->get('shipping.quote_result'),
            'quoteInput' => $request->session()->get('shipping.quote_input', []),
            'orders' => $filteredOrders,
            'currentFilter' => $filter,
            'readyQueue' => $readyQueue,
            'shippingMetrics' => [
                'delivery_orders' => $orders->count(),
                'waiting_rate' => $waitingRate->count(),
                'ready_ship' => $readyQueue->count(),
                'shipped' => $shippedQueue->count(),
                'attention' => $attentionQueue->count(),
                'shipped_today' => $shippedToday,
            ],
            'attentionReasons' => [
                'missing_rate' => $waitingRate->count(),
                'pending_status' => $orders->filter(fn (Sale $sale) => data_get($sale->meta, 'commerce.shipping.status') === CommerceOrderLifecycleService::SHIPPING_PENDING)->count(),
            ],
        ]);
    }

    public function quote(ShippingQuoteRequest $request, ShippingProviderManager $shippingProviders): RedirectResponse
    {
        try {
            $result = $shippingProviders->quoteRates($request->validated());
        } catch (\RuntimeException $exception) {
            return redirect()
                ->route('shipping.index')
                ->withInput()
                ->withErrors(['provider' => $exception->getMessage()]);
        }

        return redirect()
            ->route('shipping.index')
            ->with('shipping.quote_result', $result)
            ->with('shipping.quote_input', $request->validated());
    }

    public function bulkRate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sale_ids' => ['required', 'array', 'min:1'],
            'sale_ids.*' => ['integer'],
            'courier_name' => ['required', 'string', 'max:100'],
            'service_name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'etd' => ['nullable', 'string', 'max:50'],
        ], [
            'sale_ids.required' => 'Pilih minimal satu order delivery untuk menerapkan rate.',
            'sale_ids.min' => 'Pilih minimal satu order delivery untuk menerapkan rate.',
        ]);

        $sales = Sale::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->where('source', Sale::SOURCE_ONLINE)
            ->whereIn('id', $data['sale_ids'])
            ->get()
            ->filter(fn (Sale $sale) => $this->commerceOrders->isCommerceOrder($sale)
                && data_get($sale->meta, 'commerce.fulfillment_method') === 'delivery')
            ->values();

        if ($sales->isEmpty()) {
            return redirect()
                ->route('shipping.index')
                ->withErrors(['sale_ids' => 'Order terpilih tidak valid untuk workflow shipping.']);
        }

        $baseRate = [
            'courier_name' => $data['courier_name'],
            'service_name' => $data['service_name'],
            'price' => (float) $data['price'],
            'etd' => $data['etd'] ?: null,
            'selected_at' => now()->toIso8601String(),
            'raw' => [],
        ];

        foreach ($sales as $sale) {
            $sale = $this->commerceOrders->markShippingRateSelected($sale, [
                ...$baseRate,
                'provider' => data_get($sale->meta, 'commerce.delivery.quote_provider', 'manual'),
            ]);
            $this->commercePricing->applyShippingCharge($sale, (array) data_get($sale->meta, 'commerce.shipping.selected_rate', []));
        }

        return redirect()
            ->route('shipping.index', ['filter' => 'waiting_rate'])
            ->with('status', 'Rate pengiriman berhasil diterapkan ke order terpilih.');
    }

    public function selectRate(Request $request, Sale $sale): RedirectResponse
    {
        abort_unless($this->commerceOrders->isCommerceOrder($sale), 404);

        $data = $request->validate([
            'courier_name' => ['required', 'string', 'max:100'],
            'service_name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'etd' => ['nullable', 'string', 'max:50'],
        ]);

        $meta = is_array($sale->meta) ? $sale->meta : [];
        $rate = [
            'provider' => data_get($meta, 'commerce.delivery.quote_provider', 'manual'),
            'courier_name' => $data['courier_name'],
            'service_name' => $data['service_name'],
            'price' => (float) $data['price'],
            'etd' => $data['etd'] ?: null,
            'selected_at' => now()->toIso8601String(),
            'raw' => [],
        ];

        $sale = $this->commerceOrders->markShippingRateSelected($sale, $rate);
        $sale = $this->commercePricing->applyShippingCharge($sale, (array) data_get($sale->meta, 'commerce.shipping.selected_rate', []));

        return redirect()->route('shipping.index')->with('status', 'Rate pengiriman berhasil disimpan.');
    }

    public function ship(Request $request, Sale $sale): RedirectResponse
    {
        abort_unless($this->commerceOrders->isCommerceOrder($sale), 404);

        $data = $request->validate([
            'tracking_number' => ['required', 'string', 'max:120'],
            'courier_name' => ['nullable', 'string', 'max:100'],
            'service_name' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->commerceOrders->markShipped($sale, $data);

        return redirect()->route('shipping.index')->with('status', 'Order berhasil ditandai sebagai dikirim.');
    }
}
