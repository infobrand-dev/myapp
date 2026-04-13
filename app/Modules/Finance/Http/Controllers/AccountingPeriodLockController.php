<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AccountingPeriodLock;
use App\Modules\Finance\Http\Requests\StoreAccountingPeriodLockRequest;
use App\Support\AccountingPeriodLockService;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AccountingPeriodLockController extends Controller
{
    public function index(): View
    {
        return view('finance::governance.period-locks', [
            'locks' => AccountingPeriodLock::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->with(['creator', 'releaser'])
                ->latest('locked_from')
                ->paginate(20),
            'branches' => \App\Models\Branch::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->active()
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(StoreAccountingPeriodLockRequest $request, AccountingPeriodLockService $service): RedirectResponse
    {
        $service->create($request->validated(), $request->user());

        return back()->with('status', 'Period lock dibuat.');
    }

    public function destroy(AccountingPeriodLock $accountingPeriodLock, AccountingPeriodLockService $service): RedirectResponse
    {
        $service->release($accountingPeriodLock, request()->user());

        return back()->with('status', 'Period lock dilepas.');
    }
}
