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
        $campaigns = EmailCampaign::query()->with('recipients')->latest()->get();

        return view('emailmarketing::index', compact('campaigns'));
    }

    public function create(): View
    {
        return view('emailmarketing::create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
        ]);

        $campaign = EmailCampaign::create($data + [
            'status' => 'draft',
            'body_html' => '<div style="padding:20px;font-family:Arial,sans-serif;"><h2>Halo {{name}}</h2><p>Tulis konten email Anda di sini. Untuk link tracking gunakan token: {{track_click}}</p></div>',
        ]);

        return redirect()->route('email-marketing.show', $campaign)->with('status', 'Campaign email dibuat.');
    }

    public function show(EmailCampaign $campaign): View
    {
        $campaign->load(['recipients' => fn ($query) => $query->orderBy('recipient_name')]);

        $metrics = $campaign->metrics();

        return view('emailmarketing::show', compact('campaign', 'metrics'));
    }

    public function update(Request $request, EmailCampaign $campaign): RedirectResponse
    {
        if ($campaign->status === 'running') {
            return back()->with('status', 'Campaign sudah berjalan dan tidak bisa diedit.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body_html' => ['required', 'string'],
        ]);

        $campaign->update($data);

        return back()->with('status', 'Draft campaign diperbarui.');
    }

    public function launch(EmailCampaign $campaign): RedirectResponse
    {
        if ($campaign->status === 'running') {
            return back()->with('status', 'Campaign sudah berjalan.');
        }

        $contacts = Contact::query()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        if ($contacts->isEmpty()) {
            return back()->with('status', 'Tidak ada kontak aktif dengan email.');
        }

        $now = Carbon::now();

        foreach ($contacts as $contact) {
            $isBounce = str_contains($contact->email, 'bounce');

            $campaign->recipients()->create([
                'contact_id' => $contact->id,
                'recipient_name' => $contact->name,
                'recipient_email' => $contact->email,
                'tracking_token' => Str::uuid()->toString(),
                'delivery_status' => $isBounce ? 'bounced' : 'delivered',
                'delivered_at' => $isBounce ? null : $now,
                'bounced_at' => $isBounce ? $now : null,
            ]);
        }

        $campaign->update([
            'status' => 'running',
            'started_at' => $now,
        ]);

        return back()->with('status', 'Campaign dijalankan untuk ' . $contacts->count() . ' kontak.');
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
