<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AccountingJournal;
use App\Modules\Finance\Http\Requests\StoreManualAccountingJournalRequest;
use App\Modules\Finance\Http\Requests\UpdateManualAccountingJournalRequest;
use App\Modules\Finance\Models\ChartOfAccount;
use App\Support\AccountingJournalService;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AccountingJournalController extends Controller
{
    public function __construct(private readonly AccountingJournalService $journalService)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->only(['entry_type', 'status', 'date_from', 'date_to']);

        $journals = AccountingJournal::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->with('lines')
            ->when(!empty($filters['entry_type']), fn ($query) => $query->where('entry_type', $filters['entry_type']))
            ->when(!empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
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

    public function create(): View
    {
        return view('finance::governance.journal-form', [
            'journal' => new AccountingJournal([
                'entry_date' => now(),
                'status' => AccountingJournalService::STATUS_DRAFT,
                'entry_type' => 'manual',
            ]),
            'submitRoute' => route('finance.journals.store'),
            'method' => 'POST',
            'pageTitle' => 'Manual Journal',
            'chartOfAccounts' => $this->chartOfAccountOptions(),
        ]);
    }

    public function store(StoreManualAccountingJournalRequest $request): RedirectResponse
    {
        $journal = $this->journalService->createManual(
            $request->input('entry_date'),
            $request->input('lines', []),
            $request->input('status', AccountingJournalService::STATUS_DRAFT),
            $request->input('description'),
            $request->integer('branch_id') ?: null,
            [
                'manual' => true,
            ]
        );

        return redirect()
            ->route('finance.journals.index')
            ->with('status', 'Manual journal ' . $journal->journal_number . ' berhasil dibuat.');
    }

    public function edit(int $journal): View
    {
        $journal = $this->manualJournal($journal);
        abort_unless($journal->status !== AccountingJournalService::STATUS_POSTED, 403, 'Journal posted tidak boleh diedit.');

        return view('finance::governance.journal-form', [
            'journal' => $journal,
            'submitRoute' => route('finance.journals.update', $journal->id),
            'method' => 'PUT',
            'pageTitle' => 'Edit Manual Journal',
            'chartOfAccounts' => $this->chartOfAccountOptions(),
        ]);
    }

    public function update(UpdateManualAccountingJournalRequest $request, int $journal): RedirectResponse
    {
        $journal = $this->manualJournal($journal);
        abort_unless($journal->status !== AccountingJournalService::STATUS_POSTED, 403, 'Journal posted tidak boleh diedit.');

        $journal = $this->journalService->updateManual(
            $journal,
            $request->input('entry_date'),
            $request->input('lines', []),
            $request->input('status', AccountingJournalService::STATUS_DRAFT),
            $request->input('description'),
            $request->integer('branch_id') ?: null,
            array_merge($journal->meta ?? [], [
                'manual' => true,
            ])
        );

        return redirect()
            ->route('finance.journals.index')
            ->with('status', 'Manual journal ' . $journal->journal_number . ' berhasil diperbarui.');
    }

    public function post(int $journal): RedirectResponse
    {
        $journal = $this->manualJournal($journal);
        $this->journalService->postManual($journal);

        return redirect()
            ->route('finance.journals.index')
            ->with('status', 'Manual journal ' . $journal->journal_number . ' berhasil diposting.');
    }

    private function manualJournal(int $journalId): AccountingJournal
    {
        return AccountingJournal::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->where('entry_type', 'manual')
            ->with('lines')
            ->when(BranchContext::currentId(), fn ($query) => $query->where(function ($inner) {
                $inner->whereNull('branch_id')->orWhere('branch_id', BranchContext::currentId());
            }))
            ->findOrFail($journalId);
    }

    private function chartOfAccountOptions()
    {
        $companyId = CompanyContext::currentId();

        if (!$companyId) {
            return collect();
        }

        return ChartOfAccount::query()
            ->active()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', $companyId)
            ->where('is_postable', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get(['code', 'name']);
    }
}
