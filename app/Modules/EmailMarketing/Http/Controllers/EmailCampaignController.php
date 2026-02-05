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
    public function index(): View
    {
        $campaigns = EmailCampaign::query()
            ->withCount('recipients')
            ->latest()
            ->get();

        return view('emailmarketing::index', compact('campaigns'));
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

    public function show(EmailCampaign $campaign): View
    {
        $campaign->load(['recipients' => fn ($query) => $query->orderBy('recipient_name')]);
        $contacts = Contact::query()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('emailmarketing::show', [
            'campaign' => $campaign,
            'contacts' => $contacts,
        ]);
    }

    public function update(Request $request, EmailCampaign $campaign): RedirectResponse
    {
        $action = $request->input('action', 'save');

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body_html' => ['required', 'string'],
            'contact_ids' => ['array'],
            'contact_ids.*' => ['integer', 'exists:contacts,id'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
        ]);

        $contactIds = collect($data['contact_ids'] ?? []);

        $campaign->update([
            'name' => $data['subject'], // gabungkan name & subject
            'subject' => $data['subject'],
            'body_html' => $data['body_html'],
        ]);

        if ($action === 'send') {
            if ($contactIds->isEmpty()) {
                return back()->with('status', 'Pilih minimal satu penerima dari Contacts.');
            }
            $this->syncRecipients($campaign, $contactIds, sendNow: true);

            $campaign->update([
                'status' => 'running',
                'started_at' => Carbon::now(),
                'scheduled_at' => null,
            ]);

            // TODO: dispatch queue job per recipient
            return back()->with('status', 'Email dikirim sekarang ke ' . $contactIds->count() . ' kontak.');
        }

        if ($action === 'schedule') {
            if ($contactIds->isEmpty()) {
                return back()->with('status', 'Pilih minimal satu penerima dari Contacts.');
            }
            $scheduledAt = Carbon::parse($data['scheduled_at']);
            $this->syncRecipients($campaign, $contactIds, sendNow: false, markPending: true);

            $campaign->update([
                'status' => 'scheduled',
                'started_at' => null,
                'scheduled_at' => $scheduledAt,
            ]);

            return back()->with('status', 'Campaign dijadwalkan pada ' . $scheduledAt->format('d M Y H:i'));
        }

        // default save draft
        $campaign->update([
            'status' => 'draft',
            'scheduled_at' => null,
            'started_at' => null,
        ]);

        return back()->with('status', 'Draft disimpan.');
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
