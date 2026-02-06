<?php

namespace App\Modules\EmailMarketing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Contacts\Models\Contact;
use App\Modules\EmailMarketing\Models\EmailCampaign;
use App\Modules\EmailMarketing\Models\EmailCampaignRecipient;
use App\Modules\EmailMarketing\Models\EmailAttachment;
use App\Modules\EmailMarketing\Jobs\SendCampaignEmailRecipient;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class EmailCampaignController extends Controller
{
    protected static function defaultTemplate(): string
    {
        $content = <<<INNER
<tr>
  <td style="padding:24px 24px 8px 24px;text-align:center;">
    <img src="https://placehold.co/96x96?text=Logo" alt="Logo" width="96" height="96" style="border-radius:50%;display:block;Margin:0 auto 12px;">
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
    <div>&copy; 2026 MyApp. Semua hak dilindungi.</div>
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
        $campaigns = EmailCampaign::query()
            ->withCount('recipients')
            ->latest()
            ->get()
            ->map(function ($campaign) use ($request) {
                [$filters, $contacts] = $this->filteredContacts($request, $campaign->filter_json ?? []);
                $campaign->planned_count = $contacts->count();
                return $campaign;
            });

        return view('emailmarketing::index', compact('campaigns'));
    }

    public function matches(Request $request, EmailCampaign $campaign)
    {
        [, $contacts] = $this->filteredContacts($request, $request->input('filters', []));
        return response()->json(['count' => $contacts->count()]);
    }

    public function matchesNew(Request $request)
    {
        [, $contacts] = $this->filteredContacts($request, $request->input('filters', []));
        return response()->json(['count' => $contacts->count()]);
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

        [$filters, $contacts] = $this->filteredContacts(request(), []);

        return view('emailmarketing::show', [
            'campaign' => $campaign,
            'contacts' => $contacts,
            'filters' => $filters,
            'matchCount' => $contacts->count(),
            'isNew' => true,
        ]);
    }

    public function show(Request $request, EmailCampaign $campaign): View
    {
        $campaign->load([
            'recipients' => fn ($query) => $query->orderBy('recipient_name'),
            'attachments',
        ]);

        // gunakan filters dari query > old input > tersimpan di campaign
        $requestFilters = $request->input(
            'filters',
            $request->old('filters', $campaign->filter_json ?? [])
        );
        [$filters, $contacts] = $this->filteredContacts($request, $requestFilters);

        $showReport = in_array($campaign->status, ['running', 'done']);

        return view('emailmarketing::show', [
            'campaign' => $campaign,
            'contacts' => $contacts,
            'filters'  => $filters,
            'matchCount' => $contacts->count(),
            'showReport' => $showReport,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $action = $request->input('action', 'save');

        $subjectRule = $action === 'save'
            ? ['nullable', 'string', 'max:255']
            : ['required', 'string', 'max:255'];

        $data = $request->validate([
            'subject' => $subjectRule,
            'body_html' => ['required', 'string'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'filters' => ['array'],
            'attachments.*' => ['file', 'max:5120', 'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg'],
            'dynamic_attachment_html' => ['nullable', 'string'],
        ]);

        [$filtersNormalized, $filteredContacts] = $this->filteredContacts($request, $data['filters'] ?? []);
        $contactIds = $filteredContacts->pluck('id');

        $subjectValue = $data['subject'] ?: 'Draft';

        $campaign = EmailCampaign::create([
            'name' => $subjectValue,
            'subject' => $subjectValue,
            'status' => 'draft',
            'body_html' => $data['body_html'],
            'filter_json' => $filtersNormalized,
        ]);

        $this->syncAttachments($campaign, $request);

        if ($action === 'send') {
            if ($contactIds->isEmpty()) {
                return back()->withInput()->with('status', 'Pilih minimal satu penerima dari Contacts.');
            }
            $recipients = $this->syncRecipients($campaign, $contactIds, sendNow: false, markPending: true);
            foreach ($recipients as $recipient) {
                dispatch(new SendCampaignEmailRecipient($recipient));
            }
            $campaign->update([
                'status' => 'running',
                'started_at' => Carbon::now(),
                'scheduled_at' => null,
            ]);
            return redirect()->route('email-marketing.index')->with('status', 'Email dikirim sekarang ke ' . $contactIds->count() . ' kontak (queued).');
        }

        if ($action === 'schedule') {
            if ($contactIds->isEmpty()) {
                return back()->withInput()->with('status', 'Pilih minimal satu penerima dari Contacts.');
            }
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
            return redirect()->route('email-marketing.index')->with('status', 'Campaign dijadwalkan pada ' . $scheduledAt->format('d M Y H:i') . ' (queued).');
        }

        // save draft
        return redirect()->route('email-marketing.index')->with('status', 'Draft disimpan.');
    }

    public function update(Request $request, EmailCampaign $campaign): RedirectResponse
    {
        $action = $request->input('action', 'save');

        $subjectRule = $action === 'save'
            ? ['nullable', 'string', 'max:255']
            : ['required', 'string', 'max:255'];

        $data = $request->validate([
            'subject' => $subjectRule,
            'body_html' => ['required', 'string'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'filters' => ['array'],
            'attachments.*' => ['file', 'max:5120', 'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg'],
            'remove_attachments' => ['array'],
            'remove_attachments.*' => ['integer'],
            'dynamic_attachment_html' => ['nullable', 'string'],
        ]);

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

        if ($action === 'send') {
            if ($contactIds->isEmpty()) {
                return back()->withInput()->with('status', 'Pilih minimal satu penerima dari Contacts.');
            }
            $recipients = $this->syncRecipients($campaign, $contactIds, sendNow: false, markPending: true);
            foreach ($recipients as $recipient) {
                dispatch(new SendCampaignEmailRecipient($recipient));
            }

            $campaign->update([
                'status' => 'running',
                'started_at' => Carbon::now(),
                'scheduled_at' => null,
            ]);

            return redirect()->route('email-marketing.index')->with('status', 'Email dikirim sekarang ke ' . $contactIds->count() . ' kontak (queued).');
        }

        if ($action === 'schedule') {
            if ($contactIds->isEmpty()) {
                return back()->withInput()->with('status', 'Pilih minimal satu penerima dari Contacts.');
            }
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

            return redirect()->route('email-marketing.index')->with('status', 'Campaign dijadwalkan pada ' . $scheduledAt->format('d M Y H:i') . ' (queued).');
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
        $contacts = Contact::whereIn('id', $contactIds)->get(['id', 'name', 'email']);

        // reset recipients
        $campaign->recipients()->delete();

        $items = [];
        foreach ($contacts as $contact) {
            $status = $markPending ? 'pending' : 'outgoing';

            $items[] = $campaign->recipients()->create([
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
        if (is_string($filtersInput)) {
            $filtersInput = json_decode($filtersInput, true) ?? [];
        }

        $filters = collect($filtersInput)
            ->filter(fn ($row) => !empty($row['value']))
            ->values();

        $query = Contact::query()
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
                        default: // contains
                            $q->where($column, 'like', '%' . $val . '%');
                            break;
                    }
                });
            });
        }

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
                    'type' => 'static',
                    'filename' => $file->getClientOriginalName(),
                    'path' => $path,
                    'mime' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                    'created_by' => $request->user()?->id,
                ]);
            }
        }

        // dynamic template
        if ($request->filled('dynamic_attachment_html')) {
            $campaign->attachments()
                ->updateOrCreate(
                    ['type' => 'dynamic'],
                    [
                        'filename' => 'dynamic.pdf',
                        'template_html' => $request->input('dynamic_attachment_html'),
                        'mime' => 'application/pdf',
                        'created_by' => $request->user()?->id,
                    ]
                );
        }
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
        $recipient = EmailCampaignRecipient::query()->where('tracking_token', $token)->firstOrFail();
        if (!$recipient->unsubscribed_at) {
            $recipient->update(['unsubscribed_at' => Carbon::now()]);
        }
        return response()->view('emailmarketing::unsubscribe', ['recipient' => $recipient]);
    }

    public function trackOpen(string $token)
    {
        $recipient = EmailCampaignRecipient::query()->where('tracking_token', $token)->firstOrFail();

        if (!$recipient->opened_at) {
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
        $recipient = EmailCampaignRecipient::query()->where('tracking_token', $token)->firstOrFail();

        if (!$recipient->clicked_at) {
            $recipient->update(['clicked_at' => Carbon::now()]);
        }

        $target = request()->query('u', 'https://example.com');
        return redirect()->away($target);
    }
}
