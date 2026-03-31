<?php

namespace App\Modules\EmailMarketing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Contacts\Models\Contact;
use App\Modules\EmailMarketing\Http\Requests\StoreEmailCampaignRequest;
use App\Modules\EmailMarketing\Http\Requests\UpdateEmailCampaignRequest;
use App\Modules\EmailMarketing\Models\EmailCampaign;
use App\Modules\EmailMarketing\Models\EmailCampaignRecipient;
use App\Modules\EmailMarketing\Models\EmailAttachment;
use App\Modules\EmailMarketing\Models\EmailAttachmentTemplate;
use App\Modules\EmailMarketing\Jobs\SendCampaignEmailRecipient;
use App\Support\PlanLimit;
use App\Support\TenantContext;
use App\Support\TenantPlanManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class EmailCampaignController extends Controller
{
    protected static function defaultTemplate(): string
    {
        $appName = config('app.name', 'Meetra');
        $appUrl = rtrim((string) config('app.url', ''), '/');
        $logoUrl = $appUrl !== '' ? $appUrl . '/brand/logo-default.png' : 'https://placehold.co/220x60?text=' . rawurlencode($appName);

        $content = <<<INNER
<tr>
  <td style="padding:24px 24px 8px 24px;text-align:center;">
    <img src="{$logoUrl}" alt="{$appName}" width="180" style="display:block;Margin:0 auto 12px;width:180px;height:auto;">
    <h1 style="Margin:0;font-size:20px;line-height:28px;color:#111827;">Halo {{name}}</h1>
    <p style="Margin:8px 0 0;font-size:14px;line-height:22px;color:#6b7280;">Berikut update terbaru untuk Anda.</p>
  </td>
</tr>
<tr>
  <td style="padding:8px 24px 16px 24px;">
    <div style="background:#f0f4ff;border-radius:10px;padding:14px 16px;">
      <h2 style="Margin:0 0 8px;font-size:16px;line-height:22px;color:#1f2937;">Judul Seksi</h2>
      <p style="Margin:0;font-size:14px;line-height:22px;color:#4b5563;">Tulis konten email Anda di sini. Sertakan {{track_click}} untuk tracking link jika diperlukan.</p>
    </div>
  </td>
</tr>
<tr>
  <td style="padding:0 24px 24px 24px;text-align:center;">
    <a href="#" style="display:inline-block;padding:12px 18px;background:#206bc4;color:#fff;border-radius:10px;text-decoration:none;font-size:14px;">Call To Action</a>
  </td>
</tr>
<tr>
  <td style="padding:0 24px 20px 24px;text-align:center;font-size:12px;line-height:18px;color:#9ca3af;">
    <div style="Margin-bottom:4px;">Jika tombol tidak berfungsi, salin link ini: {{track_click}}</div>
    <div>&copy; 2026 {$appName}. Semua hak dilindungi.</div>
  </td>
</tr>
INNER;

        return self::wrapTemplate($content);
    }

    /**
     * Normalize body html for editor preview: add wrapper + base style.
     */
    public static function editorHtml(?string $html): string
    {
        if ($html) {
            // inject into wrapper while preserving user content
            $html = self::wrapTemplate($html, true);
        } else {
            $html = self::defaultTemplate();
        }
        return $html;
    }

    protected static function wrapTemplate(string $inner, bool $treatAsHtmlBlock = false): string
    {
        $baseStyle = '<style data-email-base-style>
            body{margin:0;padding:0;background:#f2f4f7;font-family:Arial,Helvetica,sans-serif;}
            .email-wrapper-outer{background:#f2f4f7;padding:16px;}
            table.email-card{margin:0 auto;max-width:500px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 8px 24px rgba(0,0,0,0.06);}
            a{color:#206bc4;}
            h1,h2,h3,h4,h5,h6{font-family:Arial,Helvetica,sans-serif;}
        </style>';

        $contentRows = $treatAsHtmlBlock
            ? "<tr><td style=\"padding:0;\">{$inner}</td></tr>"
            : $inner;

        return <<<HTML
{$baseStyle}
<div class="email-wrapper-outer" style="Margin:0;background:#f2f4f7;padding:16px;font-family:Arial,sans-serif;">
  <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" class="email-card">
    {$contentRows}
  </table>
</div>
HTML;
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));

        $campaigns = EmailCampaign::query()
            ->where('tenant_id', $this->tenantId())
            ->withCount('recipients')
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->whereFullText(['name', 'subject'], $search)
                        ->orWhere('subject', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $this->attachPlannedCounts($campaigns);

        return view('emailmarketing::index', compact('campaigns'));
    }

    public function matches(Request $request, EmailCampaign $campaign)
    {
        [$filters] = $this->normalizedFilters($request->input('filters', []));
        return response()->json(['count' => $this->filteredContactsCount($filters)]);
    }

    public function matchesNew(Request $request)
    {
        [$filters] = $this->normalizedFilters($request->input('filters', []));
        return response()->json(['count' => $this->filteredContactsCount($filters)]);
    }

    /**
     * Buat draft kosong dan langsung arahkan ke halaman edit.
     */
    public function create(): View
    {
        $campaign = new EmailCampaign([
            'status' => 'draft',
            'body_html' => self::defaultTemplate(),
            'filter_json' => [],
        ]);

        [$filters] = $this->normalizedFilters([]);

        return view('emailmarketing::show', [
            'campaign' => $campaign,
            'filters' => $filters,
            'matchCount' => $this->filteredContactsCount($filters),
            'attachmentTemplates' => $this->attachmentTemplates(),
            'isNew' => true,
        ]);
    }

    public function show(Request $request, EmailCampaign $campaign): View
    {
        $campaign->load([
            'recipients' => fn ($query) => $query->orderBy('recipient_name'),
            'attachments',
            'dynamicTemplates',
        ]);

        // gunakan filters dari query > old input > tersimpan di campaign
        $requestFilters = $request->input(
            'filters',
            $request->old('filters', $campaign->filter_json ?? [])
        );
        [$filters] = $this->normalizedFilters($requestFilters);

        $showReport = in_array($campaign->status, ['running', 'done']);

        return view('emailmarketing::show', [
            'campaign' => $campaign,
            'filters'  => $filters,
            'matchCount' => $this->filteredContactsCount($filters),
            'attachmentTemplates' => $this->attachmentTemplates(),
            'showReport' => $showReport,
        ]);
    }

    public function store(StoreEmailCampaignRequest $request): RedirectResponse
    {
        app(TenantPlanManager::class)->ensureWithinLimit(PlanLimit::EMAIL_CAMPAIGNS);

        $action = $request->input('action', 'save');

        $data = $request->validated();

        [$filtersNormalized, $filteredContacts] = $this->filteredContacts($request, $data['filters'] ?? []);
        $contactIds = $filteredContacts->pluck('id');

        $subjectValue = $data['subject'] ?: 'Draft';

        $campaign = EmailCampaign::create([
            'tenant_id' => $this->tenantId(),
            'name' => $subjectValue,
            'subject' => $subjectValue,
            'status' => 'draft',
            'body_html' => $data['body_html'],
            'filter_json' => $filtersNormalized,
        ]);

        $this->syncAttachments($campaign, $request);
        $this->syncDynamicTemplates($campaign, $request);

        if ($action === 'send') {
            if ($contactIds->isEmpty()) {
                return back()->withInput()->with('status', 'Pilih minimal satu penerima.');
            }
            $this->ensureRecipientCapacity($contactIds->count());
            $recipients = $this->syncRecipients($campaign, $contactIds, sendNow: false, markPending: true);
            foreach ($recipients as $recipient) {
                dispatch(new SendCampaignEmailRecipient($recipient));
            }
            $campaign->update([
                'status' => 'running',
                'started_at' => Carbon::now(),
                'scheduled_at' => null,
            ]);
            return redirect()->route('email-marketing.index')->with('status', 'Email dikirim ke ' . $contactIds->count() . ' kontak.');
        }

        if ($action === 'schedule') {
            if ($contactIds->isEmpty()) {
                return back()->withInput()->with('status', 'Pilih minimal satu penerima.');
            }
            $this->ensureRecipientCapacity($contactIds->count());
            $scheduledAt = Carbon::parse($data['scheduled_at']);
            $recipients = $this->syncRecipients($campaign, $contactIds, sendNow: false, markPending: true);
            foreach ($recipients as $recipient) {
                dispatch((new SendCampaignEmailRecipient($recipient))->delay($scheduledAt));
            }
            $campaign->update([
                'status' => 'scheduled',
                'started_at' => null,
                'scheduled_at' => $scheduledAt,
            ]);
            return redirect()->route('email-marketing.index')->with('status', 'Campaign dijadwalkan: ' . $scheduledAt->format('d M Y H:i') . '.');
        }

        // save draft
        return redirect()->route('email-marketing.index')->with('status', 'Draft disimpan.');
    }

    public function update(UpdateEmailCampaignRequest $request, EmailCampaign $campaign): RedirectResponse
    {
        $action = $request->input('action', 'save');

        $data = $request->validated();

        // Build recipients from filters (or all active contacts if no filters)
        [$filtersNormalized, $filteredContacts] = $this->filteredContacts($request, $data['filters'] ?? []);
        $contactIds = $filteredContacts->pluck('id');

        // Jika subject kosong saat save draft, pertahankan subject lama (atau fallback sekali saja)
        $subjectValue = $data['subject'];
        if ($action === 'save' && (is_null($subjectValue) || $subjectValue === '')) {
            $subjectValue = $campaign->subject ?: 'Draft';
        }

        $campaign->update([
            'name' => $subjectValue, // gabungkan name & subject
            'subject' => $subjectValue,
            'body_html' => $data['body_html'],
            'filter_json' => $filtersNormalized,
        ]);

        $this->syncAttachments($campaign, $request);
        $this->syncDynamicTemplates($campaign, $request);

        if ($action === 'send') {
            if ($contactIds->isEmpty()) {
                return back()->withInput()->with('status', 'Pilih minimal satu penerima.');
            }
            $this->ensureRecipientCapacity($contactIds->count());
            $recipients = $this->syncRecipients($campaign, $contactIds, sendNow: false, markPending: true);
            foreach ($recipients as $recipient) {
                dispatch(new SendCampaignEmailRecipient($recipient));
            }

            $campaign->update([
                'status' => 'running',
                'started_at' => Carbon::now(),
                'scheduled_at' => null,
            ]);

            return redirect()->route('email-marketing.index')->with('status', 'Email dikirim ke ' . $contactIds->count() . ' kontak.');
        }

        if ($action === 'schedule') {
            if ($contactIds->isEmpty()) {
                return back()->withInput()->with('status', 'Pilih minimal satu penerima.');
            }
            $this->ensureRecipientCapacity($contactIds->count());
            $scheduledAt = Carbon::parse($data['scheduled_at']);
            $recipients = $this->syncRecipients($campaign, $contactIds, sendNow: false, markPending: true);
            foreach ($recipients as $recipient) {
                dispatch((new SendCampaignEmailRecipient($recipient))->delay($scheduledAt));
            }

            $campaign->update([
                'status' => 'scheduled',
                'started_at' => null,
                'scheduled_at' => $scheduledAt,
            ]);

            return redirect()->route('email-marketing.index')->with('status', 'Campaign dijadwalkan: ' . $scheduledAt->format('d M Y H:i') . '.');
        }

        // default save draft
        $campaign->update([
            'status' => 'draft',
            'scheduled_at' => null,
            'started_at' => null,
        ]);

        return redirect()->route('email-marketing.index')->with('status', 'Draft disimpan.');
    }

    protected function syncRecipients(EmailCampaign $campaign, $contactIds, bool $sendNow = false, bool $markPending = false)
    {
        $contacts = Contact::query()
            ->where('tenant_id', $this->tenantId())
            ->whereIn('id', $contactIds)
            ->get(['id', 'name', 'email']);

        // reset recipients
        $campaign->recipients()->delete();

        $items = [];
        foreach ($contacts as $contact) {
            $status = $markPending ? 'pending' : 'outgoing';

            $items[] = $campaign->recipients()->create([
                'tenant_id' => $this->tenantId(),
                'contact_id' => $contact->id,
                'recipient_name' => $contact->name,
                'recipient_email' => $contact->email,
                'tracking_token' => Str::uuid()->toString(),
                'delivery_status' => $status,
                'delivered_at' => null,
            ]);
        }

        return collect($items);
    }

    protected function filteredContacts(Request $request, $filtersInput = []): array
    {
        [$filters, $query] = $this->filteredContactsQuery($filtersInput);

        $contacts = $query
            ->orderBy('contacts.name')
            ->get([
                'contacts.id',
                'contacts.name',
                'contacts.email',
                'company.name as company_name',
            ]);

        return [$filters->toArray(), $contacts];
    }

    protected function filteredContactsCount($filtersInput = []): int
    {
        [, $query] = $this->filteredContactsQuery($filtersInput);

        return (clone $query)->count('contacts.id');
    }

    protected function filteredContactsQuery($filtersInput = []): array
    {
        [$filters] = $this->normalizedFilters($filtersInput);

        $query = Contact::query()
            ->where('contacts.tenant_id', $this->tenantId())
            ->leftJoin('contacts as company', 'company.id', '=', 'contacts.company_id')
            ->whereNotNull('contacts.email')
            ->where('contacts.email', '!=', '')
            ->where('contacts.is_active', true);

        if ($filters->isNotEmpty()) {
            $filters->each(function ($row) use ($query) {
                $field = $row['field'] ?? 'email';
                $op = $row['operator'] ?? 'contains';
                $value = $row['value'] ?? '';

                $query->where(function ($q) use ($field, $op, $value) {
                    $column = $field === 'company' ? 'company.name' : ($field === 'name' ? 'contacts.name' : 'contacts.email');
                    $val = $value;
                    switch ($op) {
                        case 'not_contains':
                            $q->where($column, 'not like', '%' . $val . '%');
                            break;
                        case 'equals':
                            $q->where($column, '=', $val);
                            break;
                        case 'starts_with':
                            $q->where($column, 'like', $val . '%');
                            break;
                        default:
                            $q->where($column, 'like', '%' . $val . '%');
                            break;
                    }
                });
            });
        }

        return [$filters, $query];
    }

    protected function normalizedFilters($filtersInput = []): array
    {
        if (is_string($filtersInput)) {
            $filtersInput = json_decode($filtersInput, true) ?? [];
        }

        $filters = collect($filtersInput)
            ->filter(fn ($row) => !empty($row['value']))
            ->values();

        return [$filters, $filters->toArray()];
    }

    protected function syncAttachments(EmailCampaign $campaign, Request $request): void
    {
        // remove
        $removeIds = collect($request->input('remove_attachments', []))->filter()->all();
        if ($removeIds) {
            $toDelete = $campaign->attachments()->whereIn('id', $removeIds)->get();
            foreach ($toDelete as $att) {
                if ($att->type === 'static' && $att->path) {
                    Storage::delete($att->path);
                }
                $att->delete();
            }
        }

        // static uploads
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('email_attachments/'.$campaign->id);
                $campaign->attachments()->create([
                    'tenant_id' => $this->tenantId(),
                    'type' => 'static',
                    'filename' => $file->getClientOriginalName(),
                    'path' => $path,
                    'mime' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                    'created_by' => $request->user()?->id,
                ]);
            }
        }

        // dynamic template dikelola di halaman lain (tidak di form ini)
    }

    protected function syncDynamicTemplates(EmailCampaign $campaign, Request $request): void
    {
        $ids = EmailAttachmentTemplate::query()
            ->where('tenant_id', $this->tenantId())
            ->whereIn('id', collect($request->input('dynamic_template_ids', []))->filter()->map(fn ($value) => (int) $value)->all())
            ->pluck('id')
            ->all();

        $campaign->dynamicTemplates()->syncWithPivotValues($ids, [
            'tenant_id' => $this->tenantId(),
        ]);
    }

    public function markReply(EmailCampaignRecipient $recipient): RedirectResponse
    {
        $recipient->update([
            'replied_at' => $recipient->replied_at ?: Carbon::now(),
        ]);

        return back()->with('status', 'Status replied diperbarui.');
    }

    public function unsubscribe(string $token)
    {
        $recipient = $this->recipientByTrackingToken($token);
        if ($recipient && !$recipient->unsubscribed_at) {
            $recipient->update(['unsubscribed_at' => Carbon::now()]);
        }

        if (!$recipient) {
            return response(
                '<html><body style="font-family:Arial,sans-serif;padding:24px;"><h2>Link unsubscribe tidak valid atau sudah tidak aktif.</h2></body></html>',
                Response::HTTP_OK,
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }

        return response()->view('emailmarketing::unsubscribe', ['recipient' => $recipient]);
    }

    public function trackOpen(string $token)
    {
        $recipient = $this->recipientByTrackingToken($token);

        if ($recipient && !$recipient->opened_at) {
            $recipient->update(['opened_at' => Carbon::now()]);
        }

        $pixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==');

        return response($pixel, 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    public function trackClick(string $token)
    {
        $recipient = $this->recipientByTrackingToken($token);

        if ($recipient && !$recipient->clicked_at) {
            $recipient->update(['clicked_at' => Carbon::now()]);
        }

        $fallback = rtrim((string) config('app.url', url('/')), '/');
        $target = $this->validatedTrackingTarget((string) request()->query('u', ''), $fallback);

        return redirect()->away($target);
    }

    private function tenantId(): int
    {
        return TenantContext::currentId();
    }

    private function ensureRecipientCapacity(int $recipientCount): void
    {
        if ($recipientCount < 1) {
            return;
        }

        app(TenantPlanManager::class)->ensureWithinLimit(PlanLimit::EMAIL_RECIPIENTS_MONTHLY, $recipientCount);
    }

    private function attachPlannedCounts(LengthAwarePaginator $campaigns): void
    {
        $items = $campaigns->getCollection();
        $countCache = [];

        $campaigns->setCollection($items->transform(function ($campaign) use (&$countCache) {
            if ($campaign->status === 'running') {
                return $campaign;
            }

            [$filters] = $this->normalizedFilters($campaign->filter_json ?? []);
            $key = json_encode($filters->toArray());

            if (!array_key_exists($key, $countCache)) {
                $countCache[$key] = $this->filteredContactsCount($filters->toArray());
            }

            $campaign->planned_count = $countCache[$key];

            return $campaign;
        }));
    }

    private function attachmentTemplates()
    {
        return EmailAttachmentTemplate::query()
            ->where('tenant_id', $this->tenantId())
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'filename',
                'description',
            ]);
    }

    private function recipientByTrackingToken(string $token): ?EmailCampaignRecipient
    {
        $trackingToken = trim($token);
        if ($trackingToken === '') {
            return null;
        }

        return EmailCampaignRecipient::query()
            ->where('tracking_token', $trackingToken)
            ->first();
    }

    private function validatedTrackingTarget(string $target, string $fallback): string
    {
        $target = trim($target);
        if ($target === '' || mb_strlen($target) > 2000) {
            return $fallback;
        }

        $scheme = strtolower((string) parse_url($target, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http', 'https'], true)) {
            return $fallback;
        }

        return $target;
    }
}
