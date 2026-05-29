<?php

namespace App\Modules\Xendit\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Xendit\Models\XenditTransaction;
use App\Modules\Xendit\Services\XenditService;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class XenditTransactionController extends Controller
{
    public function __construct(
        private readonly XenditService $service,
    ) {
    }

    public function index(Request $request): View
    {
        $filters = [
            'status' => $request->input('status'),
            'search' => $request->input('search'),
        ];

        $query = XenditTransaction::query()
            ->where('tenant_id', TenantContext::currentId())
            ->orderByDesc('created_at');

        if ($filters['status']) {
            $query->where('status', $filters['status']);
        }

        if ($filters['search']) {
            $term = '%' . $filters['search'] . '%';
            $query->where(function ($sub) use ($term): void {
                $sub->where('external_reference', 'like', $term)
                    ->orWhere('invoice_id', 'like', $term)
                    ->orWhere('customer_name', 'like', $term)
                    ->orWhere('customer_email', 'like', $term);
            });
        }

        $transactions = $query->paginate(20)->withQueryString();
        $summary = [
            'total' => XenditTransaction::query()->where('tenant_id', TenantContext::currentId())->count(),
            'settled' => XenditTransaction::query()->where('tenant_id', TenantContext::currentId())->whereIn('status', ['PAID', 'SETTLED'])->count(),
            'pending' => XenditTransaction::query()->where('tenant_id', TenantContext::currentId())->where('status', 'PENDING')->count(),
            'failed' => XenditTransaction::query()->where('tenant_id', TenantContext::currentId())->whereIn('status', ['EXPIRED', 'FAILED'])->count(),
        ];

        return view('xendit::transactions.index', [
            'transactions' => $transactions,
            'filters' => $filters,
            'summary' => $summary,
            'isConfigured' => $this->service->isConfigured(),
        ]);
    }

    public function show(XenditTransaction $transaction): View
    {
        abort_unless((int) $transaction->tenant_id === TenantContext::currentId(), 404);

        $transaction->load('payment');

        return view('xendit::transactions.show', compact('transaction'));
    }
}
