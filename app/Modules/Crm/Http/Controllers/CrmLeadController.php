<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Contacts\Support\ContactScope;
use App\Modules\Crm\Http\Requests\StoreCrmLeadRequest;
use App\Modules\Crm\Http\Requests\UpdateCrmLeadRequest;
use App\Modules\Crm\Models\CrmLead;
use App\Modules\Crm\Support\CrmLeadScope;
use App\Modules\Crm\Support\CrmStageCatalog;
use App\Support\BooleanQuery;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\CurrencySettingsResolver;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CrmLeadController extends Controller
{
    public function index(Request $request): View
    {
        $viewMode = $request->string('view')->toString() === 'kanban' ? 'kanban' : 'list';
        $filters = [
            'q' => trim($request->string('q')->toString()),
            'stage' => trim($request->string('stage')->toString()),
            'owner_user_id' => $request->integer('owner_user_id') ?: null,
            'show_archived' => $request->boolean('show_archived'),
        ];

        $baseQuery = CrmLeadScope::applyVisibilityScope(CrmLead::query())
            ->with(['contact', 'owner', 'company', 'branch']);

        if (!$filters['show_archived']) {
            BooleanQuery::apply($baseQuery, 'is_archived', false);
        }

        if ($filters['stage'] !== '') {
            $baseQuery->where('stage', $filters['stage']);
        }

        if ($filters['owner_user_id']) {
            $baseQuery->where('owner_user_id', $filters['owner_user_id']);
        }

        if ($filters['q'] !== '') {
            $search = $filters['q'];
            $baseQuery->where(function ($query) use ($search): void {
                $query->where('title', 'like', '%' . $search . '%')
                    ->orWhere('lead_source', 'like', '%' . $search . '%')
                    ->orWhere('notes', 'like', '%' . $search . '%')
                    ->orWhereHas('contact', function ($contactQuery) use ($search): void {
                        $contactQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%')
                            ->orWhere('phone', 'like', '%' . $search . '%')
                            ->orWhere('mobile', 'like', '%' . $search . '%');
                    });
            });
        }

        $summaryLeads = (clone $baseQuery)->get();

        $summary = [
            'total' => $summaryLeads->count(),
            'open' => $summaryLeads->whereNotIn('stage', [CrmStageCatalog::WON, CrmStageCatalog::LOST])->count(),
            'won' => $summaryLeads->where('stage', CrmStageCatalog::WON)->count(),
            'value' => (float) $summaryLeads->sum(fn (CrmLead $lead) => (float) ($lead->estimated_value ?? 0)),
        ];

        $owners = User::query()
            ->where('tenant_id', TenantContext::currentId())
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($viewMode === 'kanban') {
            $leadCollection = (clone $baseQuery)
                ->orderBy('position')
                ->orderByDesc('updated_at')
                ->get();

            $board = collect(CrmStageCatalog::options())->map(function ($label, $stage) use ($leadCollection) {
                return [
                    'key' => $stage,
                    'label' => $label,
                    'badge_class' => CrmStageCatalog::badgeClass($stage),
                    'items' => $leadCollection->where('stage', $stage)->values(),
                ];
            });

            return view('crm::index', compact('viewMode', 'summary', 'owners', 'filters', 'board') + [
                'leads' => null,
                'stageOptions' => CrmStageCatalog::options(),
            ]);
        }

        $leads = (clone $baseQuery)
            ->orderByRaw("CASE WHEN next_follow_up_at IS NULL THEN 1 ELSE 0 END")
            ->orderBy('next_follow_up_at')
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('crm::index', compact('viewMode', 'summary', 'owners', 'filters', 'leads') + [
            'board' => collect(),
            'stageOptions' => CrmStageCatalog::options(),
        ]);
    }

    public function create(Request $request): View
    {
        $lead = new CrmLead([
            'stage' => $request->string('stage')->toString() ?: CrmStageCatalog::NEW_LEAD,
            'priority' => 'medium',
            'currency' => app(CurrencySettingsResolver::class)->defaultCurrency(),
            'contact_id' => $request->integer('contact_id') ?: null,
            'title' => $request->string('title')->toString(),
        ]);

        return view('crm::form', [
            'lead' => $lead,
            'contacts' => $this->contactOptions(),
            'owners' => $this->ownerOptions(),
            'stageOptions' => CrmStageCatalog::options(),
            'priorityOptions' => CrmStageCatalog::priorities(),
            'formAction' => route('crm.store'),
            'method' => 'POST',
            'pageTitle' => 'Tambah Lead CRM',
        ]);
    }

    public function store(StoreCrmLeadRequest $request): RedirectResponse
    {
        $lead = CrmLead::query()->create($this->payloadFromRequest($request));

        return redirect()->route('crm.show', $lead)->with('status', 'Lead CRM berhasil dibuat.');
    }

    public function show(CrmLead $lead): View
    {
        $lead->load(['contact', 'owner', 'company', 'branch']);

        return view('crm::show', [
            'lead' => $lead,
            'stageOptions' => CrmStageCatalog::options(),
            'nextStage' => CrmStageCatalog::nextStage($lead->stage),
            'previousStage' => CrmStageCatalog::previousStage($lead->stage),
        ]);
    }

    public function edit(CrmLead $lead): View
    {
        $lead->load(['contact', 'owner']);

        return view('crm::form', [
            'lead' => $lead,
            'contacts' => $this->contactOptions(),
            'owners' => $this->ownerOptions(),
            'stageOptions' => CrmStageCatalog::options(),
            'priorityOptions' => CrmStageCatalog::priorities(),
            'formAction' => route('crm.update', $lead),
            'method' => 'PUT',
            'pageTitle' => 'Edit Lead CRM',
        ]);
    }

    public function update(UpdateCrmLeadRequest $request, CrmLead $lead): RedirectResponse
    {
        $lead->update($this->payloadFromRequest($request, $lead));

        return redirect()->route('crm.show', $lead)->with('status', 'Lead CRM berhasil diperbarui.');
    }

    public function updateStage(Request $request, CrmLead $lead): RedirectResponse|JsonResponse
    {
        $request->validate([
            'stage' => ['required', 'in:' . implode(',', array_keys(CrmStageCatalog::options()))],
        ]);

        $stage = $request->string('stage')->toString();

        $lead->forceFill([
            'stage' => $stage,
            'won_at' => $stage === CrmStageCatalog::WON ? now() : null,
            'lost_at' => $stage === CrmStageCatalog::LOST ? now() : null,
        ])->save();

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'stage' => $stage]);
        }

        return back()->with('status', 'Tahap CRM berhasil diperbarui.');
    }

    public function destroy(CrmLead $lead): RedirectResponse
    {
        $lead->delete();

        return redirect()->route('crm.index')->with('status', 'Lead CRM dihapus.');
    }

    private function payloadFromRequest(Request $request, ?CrmLead $lead = null): array
    {
        $data = $request->validated();

        $contact = !empty($data['contact_id'])
            ? ContactScope::applyVisibilityScope(Contact::query())->whereKey($data['contact_id'])->first()
            : null;

        $labels = collect(explode(',', (string) ($data['labels'] ?? '')))
            ->map(fn ($item) => trim($item))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $stage = $data['stage'];

        return [
            'tenant_id' => TenantContext::currentId(),
            'company_id' => $contact?->company_id ?? CompanyContext::currentId(),
            'branch_id' => $contact?->branch_id ?? BranchContext::currentId(),
            'contact_id' => $contact?->id,
            'owner_user_id' => $data['owner_user_id'] ?? null,
            'title' => $data['title'],
            'stage' => $stage,
            'priority' => $data['priority'] ?? 'medium',
            'lead_source' => $data['lead_source'] ?? null,
            'estimated_value' => $data['estimated_value'] ?? null,
            'currency' => $data['currency'] ?? app(CurrencySettingsResolver::class)->defaultCurrency(),
            'probability' => $data['probability'] ?? null,
            'next_follow_up_at' => $data['next_follow_up_at'] ?? null,
            'last_contacted_at' => $data['last_contacted_at'] ?? null,
            'won_at' => $stage === CrmStageCatalog::WON ? now() : null,
            'lost_at' => $stage === CrmStageCatalog::LOST ? now() : null,
            'notes' => $data['notes'] ?? null,
            'labels' => $labels,
            'position' => $lead?->position ?? $this->nextPositionForStage($stage),
            'is_archived' => (bool) ($data['is_archived'] ?? false),
            'meta' => [
                'contact_name_snapshot' => $contact?->name,
                'contact_phone_snapshot' => $contact?->mobile ?: $contact?->phone,
            ],
        ];
    }

    private function contactOptions()
    {
        return ContactScope::applyVisibilityScope(Contact::query())
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone', 'mobile']);
    }

    private function ownerOptions()
    {
        return User::query()
            ->where('tenant_id', TenantContext::currentId())
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    private function nextPositionForStage(string $stage): int
    {
        return (int) CrmLeadScope::applyVisibilityScope(CrmLead::query())
            ->where('stage', $stage)
            ->max('position') + 1;
    }
}
