<?php

namespace App\Modules\WhatsAppApi\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\WhatsAppApi\Jobs\ProcessWABlastCampaign;
use App\Modules\WhatsAppApi\Models\WABlastCampaign;
use App\Modules\WhatsAppApi\Models\WABlastRecipient;
use App\Modules\WhatsAppApi\Models\WATemplate;
use App\Modules\WhatsAppApi\Models\WhatsAppInstance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BlastCampaignController extends Controller
{
    public function index(): View
    {
        $campaigns = WABlastCampaign::with(['instance:id,name', 'template:id,name,language'])
            ->orderByDesc('id')
            ->paginate(20);

        return view('whatsappapi::blast.index', compact('campaigns'));
    }

    public function create(): View
    {
        $instances = WhatsAppInstance::query()
            ->where('provider', 'cloud')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'cloud_business_account_id', 'status']);

        $templates = WATemplate::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'language', 'namespace', 'body']);

        return view('whatsappapi::blast.form', [
            'campaign' => new WABlastCampaign(),
            'instances' => $instances,
            'templates' => $templates,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'instance_id' => ['required', 'exists:whatsapp_instances,id'],
            'template_id' => ['required', 'exists:wa_templates,id'],
            'recipients_text' => ['required', 'string'],
            'scheduled_at' => ['nullable', 'date'],
            'delay_ms' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'action' => ['nullable', 'in:draft,send_now,schedule'],
        ]);

        $instance = WhatsAppInstance::query()->findOrFail((int) $data['instance_id']);
        $template = WATemplate::query()->findOrFail((int) $data['template_id']);

        if (strtolower((string) $instance->provider) !== 'cloud') {
            throw ValidationException::withMessages([
                'instance_id' => 'Blast hanya didukung untuk instance Cloud API.',
            ]);
        }

        if ((string) $template->status !== 'active') {
            throw ValidationException::withMessages([
                'template_id' => 'Template harus berstatus active.',
            ]);
        }

        if ($template->namespace && $instance->cloud_business_account_id && $template->namespace !== $instance->cloud_business_account_id) {
            throw ValidationException::withMessages([
                'template_id' => 'Template bukan milik WABA instance terpilih.',
            ]);
        }

        [$rows, $invalidRows] = $this->parseRecipients((string) $data['recipients_text']);
        if (empty($rows)) {
            throw ValidationException::withMessages([
                'recipients_text' => 'Tidak ada recipient valid. Format minimal: nomor,nama,var1,var2',
            ]);
        }

        $action = (string) ($data['action'] ?? 'draft');
        $scheduledAt = !empty($data['scheduled_at']) ? Carbon::parse($data['scheduled_at']) : null;

        $status = 'draft';
        if ($action === 'send_now') {
            $status = 'running';
        } elseif ($action === 'schedule' && $scheduledAt) {
            $status = 'scheduled';
        }

        $campaign = null;
        DB::transaction(function () use ($data, $rows, $request, $status, $scheduledAt, &$campaign) {
            $campaign = WABlastCampaign::create([
                'name' => $data['name'],
                'instance_id' => (int) $data['instance_id'],
                'template_id' => (int) $data['template_id'],
                'created_by' => $request->user()?->id,
                'status' => $status,
                'total_count' => count($rows),
                'settings' => [
                    'delay_ms' => (int) ($data['delay_ms'] ?? 300),
                ],
                'scheduled_at' => $scheduledAt,
            ]);

            $items = [];
            $now = now();
            foreach ($rows as $row) {
                $items[] = [
                    'campaign_id' => $campaign->id,
                    'phone_number' => $row['phone_number'],
                    'contact_name' => $row['contact_name'],
                    'variables' => $row['variables'],
                    'status' => 'pending',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            WABlastRecipient::insert($items);
        });

        if (($action === 'send_now') || ($action === 'schedule' && $scheduledAt && $scheduledAt->lessThanOrEqualTo(now()))) {
            ProcessWABlastCampaign::dispatch($campaign->id);
        }

        $msg = "Campaign dibuat dengan {$campaign->total_count} recipient.";
        if (!empty($invalidRows)) {
            $msg .= ' ' . count($invalidRows) . ' baris dilewati karena format tidak valid.';
        }

        return redirect()->route('whatsapp-api.blast-campaigns.index')->with('status', $msg);
    }

    public function launch(WABlastCampaign $blastCampaign): RedirectResponse
    {
        if ($blastCampaign->status === 'running') {
            return back()->with('status', 'Campaign sedang berjalan.');
        }

        $blastCampaign->update([
            'status' => 'running',
            'scheduled_at' => null,
            'finished_at' => null,
            'last_error' => null,
        ]);

        ProcessWABlastCampaign::dispatch($blastCampaign->id);

        return back()->with('status', 'Campaign dijalankan.');
    }

    public function retryFailed(WABlastCampaign $blastCampaign): RedirectResponse
    {
        $updated = WABlastRecipient::query()
            ->where('campaign_id', $blastCampaign->id)
            ->where('status', 'failed')
            ->update([
                'status' => 'pending',
                'error_message' => null,
                'queued_at' => null,
                'sent_at' => null,
                'updated_at' => now(),
            ]);

        if ($updated < 1) {
            return back()->with('status', 'Tidak ada recipient failed untuk diretry.');
        }

        $blastCampaign->update([
            'status' => 'running',
            'finished_at' => null,
            'last_error' => null,
        ]);
        ProcessWABlastCampaign::dispatch($blastCampaign->id);

        return back()->with('status', "Retry failed dijalankan untuk {$updated} recipient.");
    }

    public function destroy(WABlastCampaign $blastCampaign): RedirectResponse
    {
        if ($blastCampaign->status === 'running') {
            return back()->with('status', 'Campaign yang sedang running tidak bisa dihapus.');
        }

        $blastCampaign->delete();

        return back()->with('status', 'Campaign dihapus.');
    }

    private function parseRecipients(string $input): array
    {
        $rows = [];
        $invalid = [];
        $seen = [];

        $lines = preg_split('/\r\n|\r|\n/', trim($input));
        foreach ($lines as $lineNo => $line) {
            $raw = trim($line);
            if ($raw === '') {
                continue;
            }

            $delimiter = str_contains($raw, '|') ? '|' : (str_contains($raw, ';') ? ';' : ',');
            $parts = array_map('trim', explode($delimiter, $raw));

            $phone = $this->normalizePhone($parts[0] ?? '');
            if ($phone === null) {
                $invalid[] = ['line' => $lineNo + 1, 'reason' => 'Nomor tidak valid'];
                continue;
            }

            if (isset($seen[$phone])) {
                continue;
            }
            $seen[$phone] = true;

            $name = $parts[1] ?? null;
            $variables = [];
            foreach (array_slice($parts, 2) as $idx => $val) {
                $varKey = (string) ($idx + 1);
                $variables[$varKey] = $val;
            }

            $rows[] = [
                'phone_number' => $phone,
                'contact_name' => $name !== '' ? $name : null,
                'variables' => !empty($variables) ? $variables : null,
            ];
        }

        return [$rows, $invalid];
    }

    private function normalizePhone(string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', $value);
        if (!$digits) {
            return null;
        }

        if (strlen($digits) < 7 || strlen($digits) > 15) {
            return null;
        }

        return $digits;
    }
}
