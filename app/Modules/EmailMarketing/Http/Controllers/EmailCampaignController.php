<?php

namespace App\Modules\EmailMarketing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Contacts\Models\Contact;
use App\Modules\EmailMarketing\Models\EmailCampaign;
use App\Modules\EmailMarketing\Models\EmailCampaignRecipient;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class EmailCampaignController extends Controller
{
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

    /**
     * Buat draft kosong dan langsung arahkan ke halaman edit.
     */
    public function create(): RedirectResponse
    {
        $campaign = EmailCampaign::create([
            'name' => 'New Campaign',
            'subject' => 'New Campaign',
            'status' => 'draft',
            'body_html' => '<div style="padding:20px;font-family:Arial,sans-serif;"><h2>Halo {{name}}</h2><p>Tulis konten email Anda di sini. Untuk link tracking gunakan token: {{track_click}}</p></div>',
        ]);

        return redirect()->route('email-marketing.show', $campaign);
    }

    public function show(Request $request, EmailCampaign $campaign): View
    {
        $campaign->load(['recipients' => fn ($query) => $query->orderBy('recipient_name')]);

        // gunakan filters dari query > old input > tersimpan di campaign
        $requestFilters = $request->input(
            'filters',
            $request->old('filters', $campaign->filter_json ?? [])
        );
        [$filters, $contacts] = $this->filteredContacts($request, $requestFilters);

        return view('emailmarketing::show', [
            'campaign' => $campaign,
            'contacts' => $contacts,
            'filters'  => $filters,
            'matchCount' => $contacts->count(),
        ]);
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

        if ($action === 'send') {
            if ($contactIds->isEmpty()) {
                return back()->withInput()->with('status', 'Pilih minimal satu penerima dari Contacts.');
            }
            $this->syncRecipients($campaign, $contactIds, sendNow: true);

            $campaign->update([
                'status' => 'running',
                'started_at' => Carbon::now(),
                'scheduled_at' => null,
            ]);

            // TODO: dispatch queue job per recipient
            return back()->withInput($request->all())->with('status', 'Email dikirim sekarang ke ' . $contactIds->count() . ' kontak.');
        }

        if ($action === 'schedule') {
            if ($contactIds->isEmpty()) {
                return back()->withInput()->with('status', 'Pilih minimal satu penerima dari Contacts.');
            }
            $scheduledAt = Carbon::parse($data['scheduled_at']);
            $this->syncRecipients($campaign, $contactIds, sendNow: false, markPending: true);

            $campaign->update([
                'status' => 'running',
                'started_at' => $scheduledAt,
                'scheduled_at' => $scheduledAt,
            ]);

            return back()->withInput($request->all())->with('status', 'Campaign dijadwalkan pada ' . $scheduledAt->format('d M Y H:i'));
        }

        // default save draft
        $campaign->update([
            'status' => 'draft',
            'scheduled_at' => null,
            'started_at' => null,
        ]);

        return redirect()->route('email-marketing.index')->with('status', 'Draft disimpan.');
    }

    protected function syncRecipients(EmailCampaign $campaign, $contactIds, bool $sendNow = false, bool $markPending = false): void
    {
        $contacts = Contact::whereIn('id', $contactIds)->get(['id', 'name', 'email']);

        // reset recipients
        $campaign->recipients()->delete();

        $now = Carbon::now();
        foreach ($contacts as $contact) {
            $status = 'pending';
            $deliveredAt = null;
            if ($sendNow) {
                $status = 'delivered';
                $deliveredAt = $now;
            }

            $campaign->recipients()->create([
                'contact_id' => $contact->id,
                'recipient_name' => $contact->name,
                'recipient_email' => $contact->email,
                'tracking_token' => Str::uuid()->toString(),
                'delivery_status' => $markPending ? 'pending' : $status,
                'delivered_at' => $deliveredAt,
            ]);
        }
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

    public function markReply(EmailCampaignRecipient $recipient): RedirectResponse
    {
        $recipient->update([
            'replied_at' => $recipient->replied_at ?: Carbon::now(),
        ]);

        return back()->with('status', 'Status replied diperbarui.');
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

        return redirect()->away('https://example.com');
    }
}
