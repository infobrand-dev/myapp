<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AccountingPeriodClosing;
use App\Modules\Finance\Http\Requests\StoreAccountingPeriodClosingRequest;
use App\Modules\Finance\Services\AccountingPeriodClosingService;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AccountingPeriodClosingController extends Controller
{
    public function index(): View
    {
        return view('finance::governance.period-closings', [
            'closings' => AccountingPeriodClosing::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->with(['closingJournal', 'periodLock', 'closer'])
                ->latest('period_end')
                ->paginate(20),
            'branches' => \App\Models\Branch::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->active()
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(StoreAccountingPeriodClosingRequest $request, AccountingPeriodClosingService $service): RedirectResponse
    {
        $closing = $service->close($request->validated(), $request->user());

        return redirect()
            ->route('finance.period-closings.index')
            ->with('status', 'Period closing berhasil dibuat. Journal closing: ' . ($closing->closingJournal ? $closing->closingJournal->journal_number : '-'));
    }
}
