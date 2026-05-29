<?php

namespace App\Modules\Tripay\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tripay\Models\TripayTransaction;
use App\Modules\Tripay\Services\TripayService;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TripayTransactionController extends Controller
{
    public function __construct(
        private readonly TripayService $service,
    ) {
    }

    public function index(Request $request): View
    {
        $filters = [
            'status' => $request->input('status'),
            'search' => $request->input('search'),
        ];

        $query = TripayTransaction::query()
            ->where('tenant_id', TenantContext::currentId())
            ->orderByDesc('created_at');

        if ($filters['status']) {
            $query->where('status', $filters['status']);
        }

        if ($filters['search']) {
            $term = '%' . $filters['search'] . '%';
            $query->where(function ($sub) use ($term): void {
                $sub->where('merchant_reference', 'like', $term)
                    ->orWhere('tripay_reference', 'like', $term)
                    ->orWhere('customer_name', 'like', $term)
                    ->orWhere('customer_email', 'like', $term);
            });
        }

        $transactions = $query->paginate(20)->withQueryString();
        $summary = [
            'total' => TripayTransaction::query()->where('tenant_id', TenantContext::currentId())->count(),
            'settled' => TripayTransaction::query()->where('tenant_id', TenantContext::currentId())->where('status', 'PAID')->count(),
            'pending' => TripayTransaction::query()->where('tenant_id', TenantContext::currentId())->where('status', 'UNPAID')->count(),
            'failed' => TripayTransaction::query()->where('tenant_id', TenantContext::currentId())->whereIn('status', ['EXPIRED', 'FAILED'])->count(),
        ];

        return view('tripay::transactions.index', [
            'transactions' => $transactions,
            'filters' => $filters,
            'summary' => $summary,
            'isConfigured' => $this->service->isConfigured(),
        ]);
    }

    public function show(TripayTransaction $transaction): View
    {
        abort_unless((int) $transaction->tenant_id === TenantContext::currentId(), 404);

        $transaction->load('payment');

        return view('tripay::transactions.show', compact('transaction'));
    }
}
