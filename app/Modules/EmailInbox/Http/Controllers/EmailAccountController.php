<?php

namespace App\Modules\EmailInbox\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\EmailInbox\Http\Requests\StoreEmailAccountRequest;
use App\Modules\EmailInbox\Http\Requests\UpdateEmailAccountRequest;
use App\Modules\EmailInbox\Jobs\FetchMailboxMessages;
use App\Modules\EmailInbox\Models\EmailAccount;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\PlanLimit;
use App\Support\TenantContext;
use App\Support\TenantPlanManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EmailAccountController extends Controller
{
    public function index(): View
    {
        $accounts = EmailAccount::query()
            ->where('tenant_id', TenantContext::currentId())
            ->orderBy('name')
            ->paginate(20);

        return view('emailinbox::accounts.index', compact('accounts'));
    }

    public function create(): View
    {
        return view('emailinbox::accounts.form', [
            'account' => new EmailAccount([
                'direction_mode' => 'inbound_outbound',
                'inbound_protocol' => 'imap',
                'inbound_port' => 993,
                'inbound_encryption' => 'ssl',
                'outbound_port' => 587,
                'outbound_encryption' => 'tls',
                'inbound_validate_cert' => true,
                'sync_enabled' => true,
            ]),
        ]);
    }

    public function store(StoreEmailAccountRequest $request): RedirectResponse
    {
        app(TenantPlanManager::class)->ensureWithinLimit(PlanLimit::EMAIL_INBOX_ACCOUNTS);

        $data = $request->validated();
        $data['tenant_id'] = TenantContext::currentId();
        $data['company_id'] = CompanyContext::currentId();
        $data['branch_id'] = BranchContext::currentId();
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();
        $data['inbound_validate_cert'] = (bool) ($data['inbound_validate_cert'] ?? false);
        $data['sync_enabled'] = (bool) ($data['sync_enabled'] ?? false);

        EmailAccount::query()->create($data);

        return redirect()->route('email-inbox.accounts.index')
            ->with('status', 'Mailbox account berhasil dibuat.');
    }

    public function edit(EmailAccount $account): View
    {
        return view('emailinbox::accounts.form', compact('account'));
    }

    public function update(UpdateEmailAccountRequest $request, EmailAccount $account): RedirectResponse
    {
        $data = $request->validated();
        $data['updated_by'] = auth()->id();
        $data['inbound_validate_cert'] = (bool) ($data['inbound_validate_cert'] ?? false);
        $data['sync_enabled'] = (bool) ($data['sync_enabled'] ?? false);

        if (empty($data['inbound_password'])) {
            unset($data['inbound_password']);
        }

        if (empty($data['outbound_password'])) {
            unset($data['outbound_password']);
        }

        $account->update($data);

        return redirect()->route('email-inbox.accounts.index')
            ->with('status', 'Mailbox account berhasil diperbarui.');
    }

    public function sync(EmailAccount $account): RedirectResponse
    {
        FetchMailboxMessages::dispatch($account->id);

        return redirect()->route('email-inbox.show', $account)
            ->with('status', 'Sinkronisasi inbox dijadwalkan.');
    }
}
