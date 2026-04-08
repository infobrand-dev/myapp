<?php

namespace App\Modules\EmailInbox\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\EmailInbox\Http\Requests\SendMailboxMessageRequest;
use App\Modules\EmailInbox\Jobs\SendMailboxOutboundMessage;
use App\Modules\EmailInbox\Models\EmailAccount;
use App\Modules\EmailInbox\Models\EmailMessage;
use App\Support\BooleanQuery;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MailboxController extends Controller
{
    public function index(): View
    {
        $accounts = EmailAccount::query()
            ->where('tenant_id', TenantContext::currentId())
            ->withCount([
                'messages as unread_count' => fn ($query) => BooleanQuery::apply($query, 'is_read', false)->where('direction', 'inbound'),
            ])
            ->orderBy('name')
            ->get();

        $recentMessages = EmailMessage::query()
            ->where('tenant_id', TenantContext::currentId())
            ->with('account')
            ->latest('received_at')
            ->latest('id')
            ->limit(15)
            ->get();

        return view('emailinbox::index', compact('accounts', 'recentMessages'));
    }

    public function show(Request $request, EmailAccount $account): View
    {
        $folderId = $request->integer('folder_id') ?: null;

        $folders = $account->folders()
            ->orderByRaw("case when type = 'inbox' then 0 else 1 end")
            ->orderBy('name')
            ->get();

        $messages = $account->messages()
            ->with('folder')
            ->when($folderId, fn ($query) => $query->where('folder_id', $folderId))
            ->latest('received_at')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $syncRuns = $account->syncRuns()
            ->latest('id')
            ->limit(10)
            ->get();

        return view('emailinbox::show', compact('account', 'folders', 'messages', 'syncRuns', 'folderId'));
    }

    public function message(EmailAccount $account, EmailMessage $message): View
    {
        abort_unless((int) $message->account_id === (int) $account->id, 404);

        if (!$message->is_read) {
            $message->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        return view('emailinbox::message', compact('account', 'message'));
    }

    public function compose(EmailAccount $account): View
    {
        return view('emailinbox::compose', compact('account'));
    }

    public function send(SendMailboxMessageRequest $request, EmailAccount $account): RedirectResponse
    {
        $message = EmailMessage::query()->create([
            'tenant_id' => TenantContext::currentId(),
            'account_id' => $account->id,
            'direction' => 'outbound',
            'status' => 'queued',
            'subject' => $request->string('subject')->toString(),
            'from_name' => $account->outbound_from_name ?: $account->name,
            'from_email' => $account->email_address,
            'to_json' => $this->parseAddresses($request->string('to')->toString()),
            'cc_json' => $this->parseAddresses((string) $request->input('cc')),
            'bcc_json' => $this->parseAddresses((string) $request->input('bcc')),
            'body_html' => $request->input('body_html'),
            'body_text' => $request->input('body_text'),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        SendMailboxOutboundMessage::dispatch($message->id);

        return redirect()->route('email-inbox.show', $account)
            ->with('status', 'Email keluar masuk antrean pengiriman.');
    }

    private function parseAddresses(?string $line): array
    {
        $parts = collect(explode(',', (string) $line))
            ->map(fn ($item) => trim($item))
            ->filter();

        return $parts->map(fn ($email) => ['email' => $email, 'name' => null])->values()->all();
    }
}
