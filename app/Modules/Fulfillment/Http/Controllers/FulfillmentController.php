<?php

namespace App\Modules\Fulfillment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sales\Models\Sale;
use App\Support\BranchContext;
use App\Support\Commerce\CommerceOrderLifecycleService;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FulfillmentController extends Controller
{
    public function __construct(
        private readonly CommerceOrderLifecycleService $commerceOrders,
    ) {
    }

    public function __invoke(): View
    {
        $orders = Sale::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->where('source', Sale::SOURCE_ONLINE)
            ->where('payment_status', Sale::PAYMENT_PAID)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->get()
            ->filter(function (Sale $sale): bool {
                if (!$this->commerceOrders->isCommerceOrder($sale)) {
                    return false;
                }

                if (data_get($sale->meta, 'commerce.fulfillment_method') === 'delivery'
                    && $this->commerceOrders->shippingStatus($sale) === CommerceOrderLifecycleService::SHIPPING_SHIPPED) {
                    return false;
                }

                return true;
            })
            ->values();
        $filter = request()->query('filter', 'active');
        $packingQueue = $orders->filter(fn (Sale $sale) => $this->commerceOrders->fulfillmentStatus($sale) === CommerceOrderLifecycleService::FULFILLMENT_PACKING)->values();
        $readyQueue = $orders->filter(fn (Sale $sale) => $this->commerceOrders->status($sale) === CommerceOrderLifecycleService::STATUS_READY_FOR_FULFILLMENT)->values();
        $pickupQueue = $orders->filter(fn (Sale $sale) => data_get($sale->meta, 'commerce.fulfillment_method') === 'pickup')->values();
        $deliveryQueue = $orders->filter(fn (Sale $sale) => data_get($sale->meta, 'commerce.fulfillment_method') === 'delivery')->values();
        $filteredOrders = match ($filter) {
            'packing' => $packingQueue,
            'ready' => $readyQueue,
            'pickup' => $pickupQueue,
            'delivery' => $deliveryQueue,
            default => $orders,
        };

        return view('fulfillment::index', [
            'orders' => $filteredOrders,
            'currentFilter' => $filter,
            'packingQueue' => $packingQueue,
            'readyQueue' => $readyQueue,
            'pickupQueue' => $pickupQueue,
            'deliveryQueue' => $deliveryQueue,
            'fulfillmentMetrics' => [
                'orders' => $orders->count(),
                'packing' => $packingQueue->count(),
                'ready' => $readyQueue->count(),
                'pickup' => $pickupQueue->count(),
                'delivery' => $deliveryQueue->count(),
            ],
        ]);
    }

    public function markPacking(Request $request, Sale $sale): RedirectResponse
    {
        abort_unless($this->commerceOrders->isCommerceOrder($sale), 404);

        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->commerceOrders->markPacking($sale, $data['note'] ?? null);

        return redirect()->route('fulfillment.index')->with('status', 'Order masuk tahap packing.');
    }

    public function bulkUpdate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sale_ids' => ['required', 'array', 'min:1'],
            'sale_ids.*' => ['integer'],
            'action' => ['required', 'in:packing,ready'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'sale_ids.required' => 'Pilih minimal satu order untuk diproses.',
            'sale_ids.min' => 'Pilih minimal satu order untuk diproses.',
        ]);

        $sales = Sale::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->where('source', Sale::SOURCE_ONLINE)
            ->where('payment_status', Sale::PAYMENT_PAID)
            ->whereIn('id', $data['sale_ids'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->get()
            ->filter(function (Sale $sale): bool {
                if (!$this->commerceOrders->isCommerceOrder($sale)) {
                    return false;
                }

                if (data_get($sale->meta, 'commerce.fulfillment_method') === 'delivery'
                    && $this->commerceOrders->shippingStatus($sale) === CommerceOrderLifecycleService::SHIPPING_SHIPPED) {
                    return false;
                }

                return true;
            })
            ->values();

        if ($sales->isEmpty()) {
            return redirect()
                ->route('fulfillment.index')
                ->withErrors(['sale_ids' => 'Order terpilih tidak valid untuk workflow fulfillment.']);
        }

        foreach ($sales as $sale) {
            if ($data['action'] === 'packing') {
                $this->commerceOrders->markPacking($sale, $data['note'] ?? null);
                continue;
            }

            $this->commerceOrders->markReadyForFulfillment($sale, $data['note'] ?? null);
        }

        return redirect()
            ->route('fulfillment.index')
            ->with('status', $data['action'] === 'packing'
                ? 'Order terpilih berhasil dipindahkan ke tahap packing.'
                : 'Order terpilih berhasil ditandai siap untuk handoff.');
    }

    public function markReady(Request $request, Sale $sale): RedirectResponse
    {
        abort_unless($this->commerceOrders->isCommerceOrder($sale), 404);

        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->commerceOrders->markReadyForFulfillment($sale, $data['note'] ?? null);

        return redirect()->route('fulfillment.index')->with('status', 'Order siap diproses lebih lanjut.');
    }
}
