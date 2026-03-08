<?php

namespace App\Modules\WhatsAppApi\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Contacts\Models\Contact;
use App\Modules\WhatsAppApi\Jobs\ProcessWABlastCampaign;
use App\Modules\WhatsAppApi\Models\WABlastCampaign;
use App\Modules\WhatsAppApi\Models\WABlastRecipient;
use App\Modules\WhatsAppApi\Models\WATemplate;
use App\Modules\WhatsAppApi\Models\WhatsAppInstance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
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
            ->where('status', 'approved')
            ->orderBy('name')
            ->get(['id', 'name', 'language', 'namespace', 'body']);
        [$filters, $contacts] = $this->filteredContacts(request(), []);

        return view('whatsappapi::blast.form', [
            'campaign' => new WABlastCampaign(),
            'instances' => $instances,
            'templates' => $templates,
            'filters' => $filters,
            'matchCount' => $contacts->count(),
            'contactsEnabled' => $this->isContactsModuleReady(),
        ]);
    }

    public function matches(Request $request)
    {
        [, $contacts] = $this->filteredContacts($request, $request->input('filters', []));

        return response()->json(['count' => $contacts->count()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'instance_id' => ['required', 'exists:whatsapp_instances,id'],
            'template_id' => ['required', 'exists:wa_templates,id'],
            'recipient_source' => ['required', 'in:manual,csv,contacts'],
            'recipients_text' => ['nullable', 'string'],
            'recipients_file' => ['nullable', 'file', 'max:5120', 'mimes:csv,txt'],
            'filters' => ['nullable', 'array'],
            'scheduled_at' => ['nullable', 'date'],
            'delay_ms' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'action' => ['nullable', 'in:draft,send_now,schedule'],
        ]);
        $normalizedFilters = [];

        $instance = WhatsAppInstance::query()->findOrFail((int) $data['instance_id']);
        $template = WATemplate::query()->findOrFail((int) $data['template_id']);

        if (strtolower((string) $instance->provider) !== 'cloud') {
            throw ValidationException::withMessages([
                'instance_id' => 'Blast hanya didukung untuk instance Cloud API.',
            ]);
        }

        if ((string) $template->status !== 'approved') {
            throw ValidationException::withMessages([
                'template_id' => 'Template harus berstatus approved.',
            ]);
        }

        if ($template->namespace && $instance->cloud_business_account_id && $template->namespace !== $instance->cloud_business_account_id) {
            throw ValidationException::withMessages([
                'template_id' => 'Template bukan milik WABA instance terpilih.',
            ]);
        }

        if (($data['recipient_source'] ?? null) === 'contacts') {
            [$normalizedFilters] = $this->filteredContacts($request, $data['filters'] ?? []);
        }

        [$rows, $invalidRows] = $this->resolveRecipientRows($request, $data);
        if (empty($rows)) {
            throw ValidationException::withMessages([
                'recipients_text' => 'Tidak ada recipient valid dari sumber yang dipilih.',
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
                    'recipient_source' => (string) ($data['recipient_source'] ?? 'manual'),
                    'filters' => $normalizedFilters,
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

    private function resolveRecipientRows(Request $request, array $data): array
    {
        $source = (string) ($data['recipient_source'] ?? 'manual');

        if ($source === 'csv') {
            /** @var UploadedFile|null $file */
            $file = $request->file('recipients_file');
            if (!$file) {
                throw ValidationException::withMessages([
                    'recipients_file' => 'Upload file CSV/TXT terlebih dahulu.',
                ]);
            }

            return $this->parseRecipients((string) file_get_contents($file->getRealPath()));
        }

        if ($source === 'contacts') {
            [$filters, $contacts] = $this->filteredContacts($request, $data['filters'] ?? []);

            if ($contacts->isEmpty()) {
                throw ValidationException::withMessages([
                    'filters' => 'Tidak ada contact yang match dengan rule filter.',
                ]);
            }

            $data['filters'] = $filters;

            return [$this->contactsToRecipientRows($contacts), []];
        }

        $raw = trim((string) ($data['recipients_text'] ?? ''));
        if ($raw === '') {
            throw ValidationException::withMessages([
                'recipients_text' => 'Input recipients manual wajib diisi.',
            ]);
        }

        return $this->parseRecipients($raw);
    }

    private function contactsToRecipientRows($contacts): array
    {
        $rows = [];
        $seen = [];

        foreach ($contacts as $contact) {
            $phone = $this->normalizePhone((string) ($contact->mobile ?: $contact->phone));
            if ($phone === null || isset($seen[$phone])) {
                continue;
            }

            $seen[$phone] = true;
            $rows[] = [
                'phone_number' => $phone,
                'contact_name' => trim((string) $contact->name) !== '' ? (string) $contact->name : null,
                'variables' => null,
            ];
        }

        return $rows;
    }

    private function filteredContacts(Request $request, $filtersInput = []): array
    {
        if (!$this->isContactsModuleReady()) {
            return [[], collect()];
        }

        if (is_string($filtersInput)) {
            $filtersInput = json_decode($filtersInput, true) ?? [];
        }

        $filters = collect($filtersInput)
            ->filter(fn ($row) => !empty($row['value']))
            ->values();

        $query = Contact::query()
            ->leftJoin('contacts as company', 'company.id', '=', 'contacts.company_id')
            ->where('contacts.is_active', true)
            ->where(function ($query) {
                $query->whereNotNull('contacts.mobile')->where('contacts.mobile', '!=', '')
                    ->orWhereNotNull('contacts.phone')->where('contacts.phone', '!=', '');
            });

        if ($filters->isNotEmpty()) {
            $filters->each(function ($row) use ($query) {
                $field = $row['field'] ?? 'name';
                $op = $row['operator'] ?? 'contains';
                $value = $row['value'] ?? '';

                $query->where(function ($q) use ($field, $op, $value) {
                    $column = match ($field) {
                        'company' => 'company.name',
                        'phone' => 'contacts.phone',
                        'mobile' => 'contacts.mobile',
                        default => 'contacts.name',
                    };

                    switch ($op) {
                        case 'not_contains':
                            $q->where($column, 'not like', '%' . $value . '%');
                            break;
                        case 'equals':
                            $q->where($column, '=', $value);
                            break;
                        case 'starts_with':
                            $q->where($column, 'like', $value . '%');
                            break;
                        default:
                            $q->where($column, 'like', '%' . $value . '%');
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
                'contacts.mobile',
                'contacts.phone',
                'company.name as company_name',
            ]);

        return [$filters->toArray(), $contacts];
    }

    private function isContactsModuleReady(): bool
    {
        return class_exists(Contact::class);
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
