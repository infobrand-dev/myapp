<?php

namespace App\Modules\WhatsAppApi\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Contacts\Support\ContactPhoneNormalizer;
use App\Modules\WhatsAppApi\Jobs\ProcessWABlastCampaign;
use App\Modules\WhatsAppApi\Models\WABlastCampaign;
use App\Modules\WhatsAppApi\Models\WABlastRecipient;
use App\Modules\WhatsAppApi\Models\WAContactPhoneStatus;
use App\Modules\WhatsAppApi\Models\WATemplate;
use App\Modules\WhatsAppApi\Models\WhatsAppInstance;
use App\Modules\WhatsAppApi\Support\TemplateVariableResolver;
use App\Support\PlanLimit;
use App\Support\TenantContext;
use App\Support\TenantPlanManager;
use App\Modules\WhatsAppApi\Http\Requests\StoreBlastCampaignRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BlastCampaignController extends Controller
{

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));

        $campaigns = WABlastCampaign::with(['instance:id,name', 'template:id,name,language'])
            ->where('tenant_id', $this->tenantId())
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->whereFullText(['name'], $search))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('whatsappapi::blast.index', compact('campaigns'));
    }

    public function create(): View
    {
        $instances = WhatsAppInstance::query()
            ->where('tenant_id', $this->tenantId())
            ->where('provider', 'cloud')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'cloud_business_account_id', 'status']);

        $templates = WATemplate::query()
            ->where('tenant_id', $this->tenantId())
            ->where('status', 'approved')
            ->orderBy('name')
            ->get(['id', 'name', 'language', 'namespace', 'body', 'components', 'variable_mappings']);
        [$filters] = $this->normalizedFilters([]);

        return view('whatsappapi::blast.form', [
            'campaign' => new WABlastCampaign(),
            'instances' => $instances,
            'templates' => $templates,
            'filters' => $filters,
            'matchCount' => $this->filteredContactsCount($filters),
            'contactsEnabled' => $this->isContactsModuleReady(),
            'contactFieldOptions' => TemplateVariableResolver::contactFieldOptions(),
        ]);
    }

    public function matches(Request $request)
    {
        [$filters] = $this->normalizedFilters($request->input('filters', []));

        return response()->json(['count' => $this->filteredContactsCount($filters)]);
    }

    public function store(StoreBlastCampaignRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if (!WATemplate::query()->where('tenant_id', $this->tenantId())->find((int) $data['template_id'])) {
            throw ValidationException::withMessages([
                'template_id' => 'Template tidak tersedia untuk tenant aktif.',
            ]);
        }
        $normalizedFilters = [];
        $rows = [];
        $invalidRows = [];

        $instance = WhatsAppInstance::query()
            ->where('tenant_id', $this->tenantId())
            ->findOrFail((int) $data['instance_id']);
        $template = WATemplate::query()
            ->where('tenant_id', $this->tenantId())
            ->findOrFail((int) $data['template_id']);

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
            [, $normalizedFilters] = $this->normalizedFilters($data['filters'] ?? []);
        } else {
            [$rows, $invalidRows] = $this->resolveRecipientRows($request, $data, $template);
            if (empty($rows)) {
                throw ValidationException::withMessages([
                    'recipients_text' => 'Tidak ada recipient valid dari sumber yang dipilih.',
                ]);
            }
        }

        $recipientCount = ($data['recipient_source'] ?? null) === 'contacts'
            ? $this->filteredContactsCount($normalizedFilters)
            : count($rows);

        if ($recipientCount > 0) {
            app(TenantPlanManager::class)->ensureWithinLimit(PlanLimit::WA_BLAST_RECIPIENTS_MONTHLY, $recipientCount);
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
        DB::transaction(function () use ($data, $rows, $request, $status, $scheduledAt, $normalizedFilters, $template, &$campaign) {
            $campaign = WABlastCampaign::create([
                'tenant_id' => $this->tenantId(),
                'name' => $data['name'],
                'instance_id' => (int) $data['instance_id'],
                'template_id' => (int) $data['template_id'],
                'created_by' => $request->user()?->id,
                'status' => $status,
                'total_count' => 0,
                'settings' => [
                    'delay_ms' => (int) ($data['delay_ms'] ?? 300),
                    'recipient_source' => (string) ($data['recipient_source'] ?? 'manual'),
                    'filters' => $normalizedFilters,
                ],
                'scheduled_at' => $scheduledAt,
            ]);

            $totalCount = 0;

            if (($data['recipient_source'] ?? null) === 'contacts') {
                $totalCount = $this->insertContactRecipients($campaign, $template, $normalizedFilters);

                if ($totalCount < 1) {
                    throw ValidationException::withMessages([
                        'filters' => 'Tidak ada contact yang match dengan rule filter.',
                    ]);
                }
            } else {
                $totalCount = $this->insertBlastRecipients($campaign, $rows);
            }

            $campaign->update([
                'total_count' => $totalCount,
            ]);
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
            ->where('tenant_id', $this->tenantId())
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

            $phone = ContactPhoneNormalizer::normalize($parts[0] ?? '');
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

    private function resolveRecipientRows(Request $request, array $data, WATemplate $template): array
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

            [$rows, $invalid] = $this->parseRecipients((string) file_get_contents($file->getRealPath()));
            return [$this->applyTemplateVariablesToRows($rows, $template), $invalid];
        }

        $raw = trim((string) ($data['recipients_text'] ?? ''));
        if ($raw === '') {
            throw ValidationException::withMessages([
                'recipients_text' => 'Input recipients manual wajib diisi.',
            ]);
        }

        [$rows, $invalid] = $this->parseRecipients($raw);
        return [$this->applyTemplateVariablesToRows($rows, $template), $invalid];
    }

    private function contactToRecipientRow($contact, WATemplate $template): ?array
    {
        $phone = $contact->whatsappPhoneNumber();
        if ($phone === null) {
            return null;
        }

        return [
            'phone_number' => $phone,
            'contact_name' => trim((string) $contact->name) !== '' ? (string) $contact->name : null,
            'variables' => TemplateVariableResolver::resolve(
                $template,
                TemplateVariableResolver::contextFromArray([
                    'name' => $contact->name,
                    'mobile' => $contact->mobile,
                    'phone' => $contact->phone,
                    'email' => $contact->email,
                    'company_name' => $contact->company_name,
                    'job_title' => $contact->job_title,
                    'website' => $contact->website,
                    'industry' => $contact->industry,
                    'city' => $contact->city,
                    'state' => $contact->state,
                    'country' => $contact->country,
                    'phone_number' => $phone,
                ]),
                TemplateVariableResolver::contextFromSender(auth()->user())
            ),
        ];
    }

    private function applyTemplateVariablesToRows(array $rows, WATemplate $template): array
    {
        return array_map(function (array $row) use ($template): array {
            $row['variables'] = TemplateVariableResolver::resolve(
                $template,
                TemplateVariableResolver::contextFromArray([
                    'name' => $row['contact_name'] ?? '',
                    'mobile' => $row['phone_number'] ?? '',
                    'phone' => $row['phone_number'] ?? '',
                    'phone_number' => $row['phone_number'] ?? '',
                ]),
                TemplateVariableResolver::contextFromSender(auth()->user()),
                (array) ($row['variables'] ?? [])
            );

            return $row;
        }, $rows);
    }

    private function filteredContacts(Request $request, $filtersInput = []): array
    {
        if (!$this->isContactsModuleReady()) {
            return [[], collect()];
        }

        [$filters, $query] = $this->filteredContactsQuery($filtersInput);

        $contacts = $query
            ->orderBy('contacts.name')
            ->get([
                'contacts.id',
                'contacts.name',
                'contacts.mobile',
                'contacts.phone',
                'contacts.email',
                'contacts.job_title',
                'contacts.website',
                'contacts.industry',
                'contacts.city',
                'contacts.state',
                'contacts.country',
                'company.name as company_name',
            ]);

        return [$filters->toArray(), $contacts];
    }

    private function filteredContactsCount($filtersInput = []): int
    {
        if (!$this->isContactsModuleReady()) {
            return 0;
        }

        [, $query] = $this->filteredContactsQuery($filtersInput);

        return (clone $query)
            ->distinct()
            ->count(DB::raw($this->recipientPhoneExpression()));
    }

    private function filteredContactsQuery($filtersInput = []): array
    {
        [$filters] = $this->normalizedFilters($filtersInput);

        $query = Contact::query()
            ->where('contacts.tenant_id', $this->tenantId())
            ->leftJoin('contacts as company', 'company.id', '=', 'contacts.company_id')
            ->where('contacts.is_active', true)
            ->whereRaw($this->recipientPhoneExpression() . ' IS NOT NULL');

        $this->applyBlockedPhoneScope($query);

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

        return [$filters, $query];
    }

    private function normalizedFilters($filtersInput = []): array
    {
        if (is_string($filtersInput)) {
            $filtersInput = json_decode($filtersInput, true) ?? [];
        }

        $filters = collect($filtersInput)
            ->filter(fn ($row) => !empty($row['value']))
            ->values();

        return [$filters, $filters->toArray()];
    }

    private function isContactsModuleReady(): bool
    {
        return class_exists(Contact::class);
    }

    private function applyBlockedPhoneScope($query): void
    {
        $query->whereNotExists(function ($subQuery) {
            $subQuery->select(DB::raw(1))
                ->from('wa_contact_phone_statuses as blocked_phones')
                ->where('blocked_phones.tenant_id', $this->tenantId())
                ->where('blocked_phones.status', 'blocked')
                ->whereRaw('blocked_phones.phone_number = ' . $this->recipientPhoneExpression());
        });
    }

    private function insertBlastRecipients(WABlastCampaign $campaign, array $rows): int
    {
        if (empty($rows)) {
            return 0;
        }

        $now = now();
        $items = [];

        foreach ($rows as $row) {
            $items[] = [
                'tenant_id' => $this->tenantId(),
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

        return count($items);
    }

    private function insertContactRecipients(WABlastCampaign $campaign, WATemplate $template, array $filters): int
    {
        [, $query] = $this->filteredContactsQuery($filters);

        $totalCount = 0;
        $seenPhones = [];
        $buffer = [];
        $bufferSize = 500;

        (clone $query)
            ->orderBy('contacts.id')
            ->select([
                'contacts.id',
                'contacts.name',
                'contacts.mobile',
                'contacts.phone',
                'contacts.email',
                'contacts.job_title',
                'contacts.website',
                'contacts.industry',
                'contacts.city',
                'contacts.state',
                'contacts.country',
                'company.name as company_name',
            ])
            ->chunkById(500, function (Collection $contacts) use ($campaign, $template, &$totalCount, &$seenPhones, &$buffer, $bufferSize) {
                foreach ($contacts as $contact) {
                    $row = $this->contactToRecipientRow($contact, $template);
                    if (!$row || isset($seenPhones[$row['phone_number']])) {
                        continue;
                    }

                    $seenPhones[$row['phone_number']] = true;
                    $buffer[] = [
                        'tenant_id' => $this->tenantId(),
                        'campaign_id' => $campaign->id,
                        'phone_number' => $row['phone_number'],
                        'contact_name' => $row['contact_name'],
                        'variables' => $row['variables'],
                        'status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $totalCount++;

                    if (count($buffer) >= $bufferSize) {
                        WABlastRecipient::insert($buffer);
                        $buffer = [];
                    }
                }
            }, 'contacts.id');

        if (!empty($buffer)) {
            WABlastRecipient::insert($buffer);
        }

        return $totalCount;
    }

    private function recipientPhoneExpression(): string
    {
        return "COALESCE(NULLIF(contacts.mobile, ''), NULLIF(contacts.phone, ''))";
    }

    private function tenantId(): int
    {
        return TenantContext::currentId();
    }

}
