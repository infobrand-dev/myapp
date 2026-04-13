<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AccountingJournal;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountingJournalController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->only(['entry_type', 'date_from', 'date_to']);

        $journals = AccountingJournal::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->with('lines')
            ->when(!empty($filters['entry_type']), fn ($query) => $query->where('entry_type', $filters['entry_type']))
            ->when(!empty($filters['date_from']), fn ($query) => $query->whereDate('entry_date', '>=', $filters['date_from']))
            ->when(!empty($filters['date_to']), fn ($query) => $query->whereDate('entry_date', '<=', $filters['date_to']))
            ->when(BranchContext::currentId(), fn ($query) => $query->where(function ($inner) {
                $inner->whereNull('branch_id')->orWhere('branch_id', BranchContext::currentId());
            }))
            ->latest('entry_date')
            ->paginate(20)
            ->withQueryString();

        return view('finance::governance.journals', [
            'journals' => $journals,
            'filters' => $filters,
        ]);
    }
}
