<?php

namespace App\Modules\Midtrans\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Midtrans\Http\Requests\CreateSnapTokenRequest;
use App\Modules\Midtrans\Models\MidtransTransaction;
use App\Modules\Midtrans\Services\MidtransService;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MidtransTransactionController extends Controller
{
    public function __construct(private readonly MidtransService $midtrans) {}

    public function index(Request $request): View
    {
        $filters = [
            'status'       => $request->input('status'),
            'payment_type' => $request->input('payment_type'),
            'date_from'    => $request->input('date_from'),
            'date_to'      => $request->input('date_to'),
            'search'       => $request->input('search'),
        ];

        $query = MidtransTransaction::query()
            ->where('tenant_id', TenantContext::currentId())
            ->orderByDesc('created_at');

        if ($filters['status']) {
            $query->where('transaction_status', $filters['status']);
        }
        if ($filters['payment_type']) {
            $query->where('payment_type', $filters['payment_type']);
        }
        if ($filters['date_from']) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if ($filters['date_to']) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        if ($filters['search']) {
            $q = '%' . $filters['search'] . '%';
            $query->where(function ($sub) use ($q) {
                $sub->where('order_id', 'like', $q)
                    ->orWhere('transaction_id', 'like', $q)
                    ->orWhere('customer_name', 'like', $q)
                    ->orWhere('customer_email', 'like', $q);
            });
        }

        $transactions = $query->paginate(20)->withQueryString();

        $summary = [
            'total'      => MidtransTransaction::where('tenant_id', TenantContext::currentId())->count(),
            'settled'    => MidtransTransaction::where('tenant_id', TenantContext::currentId())->where('transaction_status', 'settlement')->count(),
            'pending'    => MidtransTransaction::where('tenant_id', TenantContext::currentId())->where('transaction_status', 'pending')->count(),
            'failed'     => MidtransTransaction::where('tenant_id', TenantContext::currentId())->whereIn('transaction_status', ['deny', 'cancel', 'expire'])->count(),
        ];

        $isConfigured = $this->midtrans->isConfigured();

        return view('midtrans::transactions.index', compact('transactions', 'filters', 'summary', 'isConfigured'));
    }

    public function show(MidtransTransaction $transaction): View
    {
        abort_unless((int) $transaction->tenant_id === TenantContext::currentId(), 404);

        $transaction->load('payment');

        return view('midtrans::transactions.show', compact('transaction'));
    }

    /**
     * Create a Snap token for a payable (e.g. Sale).
     * Returns JSON: { snap_token, redirect_url, order_id }
     */
    public function createSnapToken(CreateSnapTokenRequest $request): JsonResponse
    {
        if (!$this->midtrans->isConfigured()) {
            return response()->json(['error' => 'Midtrans belum dikonfigurasi.'], 422);
        }

        $data        = $request->validated();
        $tenantId    = TenantContext::currentId();
        $payableType = $data['payable_type'];
        $payableId   = (int) $data['payable_id'];

        // Resolve payable model
        $payable = null;
        if (class_exists($payableType)) {
            $payable = $payableType::query()
                ->where('tenant_id', $tenantId)
                ->findOrFail($payableId);
        }

        // Determine amount
        $amount = (float) ($data['amount']
            ?? $payable?->balance_due
            ?? $payable?->grand_total
            ?? 0);

        if ($amount <= 0) {
            return response()->json(['error' => 'Nominal pembayaran tidak valid atau sudah lunas.'], 422);
        }

        // Check if there's already a pending snap token for this payable
        $existing = MidtransTransaction::query()
            ->where('tenant_id', $tenantId)
            ->where('payable_type', $payableType)
            ->where('payable_id', $payableId)
            ->where('transaction_status', MidtransTransaction::STATUS_PENDING)
            ->whereNotNull('snap_token')
            ->latest()
            ->first();

        if ($existing) {
            return response()->json([
                'snap_token'   => $existing->snap_token,
                'redirect_url' => $existing->snap_redirect_url,
                'order_id'     => $existing->order_id,
            ]);
        }

        // Create new transaction record
        $orderId = MidtransTransaction::generateOrderId($tenantId);

        try {
            $result = $this->midtrans->createSnapToken([
                'order_id'           => $orderId,
                'gross_amount'       => $amount,
                'customer_name'      => $data['customer_name'] ?? $payable?->customer_name_snapshot,
                'customer_email'     => $data['customer_email'] ?? $payable?->customer_email_snapshot,
                'customer_phone'     => $data['customer_phone'] ?? $payable?->customer_phone_snapshot,
                'item_description'   => $data['description'] ?? ($payable ? class_basename($payableType) . ' #' . ($payable->sale_number ?? $payableId) : 'Pembayaran'),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        MidtransTransaction::create([
            'tenant_id'          => $tenantId,
            'order_id'           => $orderId,
            'snap_token'         => $result['token'],
            'snap_redirect_url'  => $result['redirect_url'],
            'gross_amount'       => $amount,
            'transaction_status' => MidtransTransaction::STATUS_PENDING,
            'payable_type'       => $payableType,
            'payable_id'         => $payableId,
            'customer_name'      => $data['customer_name'] ?? $payable?->customer_name_snapshot,
            'customer_email'     => $data['customer_email'] ?? $payable?->customer_email_snapshot,
            'customer_phone'     => $data['customer_phone'] ?? $payable?->customer_phone_snapshot,
            'item_description'   => $data['description'] ?? null,
            'created_by'         => $request->user()?->id,
        ]);

        return response()->json([
            'snap_token'   => $result['token'],
            'redirect_url' => $result['redirect_url'],
            'order_id'     => $orderId,
        ]);
    }
}
