<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Contacts\Support\ContactScope;
use App\Modules\Crm\Http\Requests\StoreCrmLeadRequest;
use App\Modules\Crm\Http\Requests\UpdateCrmLeadRequest;
use App\Modules\Crm\Models\CrmActivity;
use App\Modules\Crm\Models\CrmLead;
use App\Modules\Crm\Support\CrmActivityLogger;
use App\Modules\Crm\Support\CrmFollowUpTaskManager;
use App\Modules\Crm\Support\CrmLeadScope;
use App\Modules\Crm\Support\CrmPipelineProvisioner;
use App\Modules\Crm\Support\CrmSourceCatalog;
use App\Modules\Crm\Support\CrmStageCatalog;
use App\Modules\Crm\Support\CrmWonAutomationService;
use App\Support\BooleanQuery;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\CurrencySettingsResolver;
use App\Support\PlanLimit;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class CrmLeadController extends Controller
{
    public function __construct(
        private readonly CrmActivityLogger $activityLogger,
        private readonly CrmPipelineProvisioner $pipelineProvisioner,
        private readonly CrmFollowUpTaskManager $followUpTasks,
        private readonly CrmWonAutomationService $wonAutomation,
    ) {
    }

    public function index(Request $request): View
    {
        $viewMode = $request->string('view')->toString() === 'kanban' ? 'kanban' : 'list';
        $today = now()->startOfDay();
        $tomorrow = (clone $today)->addDay();
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
                    ->orWhere('qualification_status', 'like', '%' . $search . '%')
                    ->orWhere('notes', 'like', '%' . $search . '%')
                    ->orWhereHas('contact', function ($contactQuery) use ($search): void {
                        $contactQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%')
                            ->orWhere('phone', 'like', '%' . $search . '%')
                            ->orWhere('mobile', 'like', '%' . $search . '%');
                    });
            });
        }

        $openStages = [CrmStageCatalog::WON, CrmStageCatalog::LOST];
        $total = (clone $baseQuery)->count();
        $won = (clone $baseQuery)->where('stage', CrmStageCatalog::WON)->count();
        $lost = (clone $baseQuery)->where('stage', CrmStageCatalog::LOST)->count();
        $openCount = (clone $baseQuery)->whereNotIn('stage', $openStages)->count();
        $pipelineValue = (float) ((clone $baseQuery)->whereNotIn('stage', $openStages)->sum('estimated_value') ?? 0);
        $crmContactCount = (clone $baseQuery)->whereNotNull('contact_id')->distinct('contact_id')->count('contact_id');
        $followUpDueToday = (clone $baseQuery)
            ->whereNotIn('stage', $openStages)
            ->whereBetween('next_follow_up_at', [$today, $tomorrow])
            ->count();
        $overdueFollowUp = (clone $baseQuery)
            ->whereNotIn('stage', $openStages)
            ->whereNotNull('next_follow_up_at')
            ->where('next_follow_up_at', '<', $today)
            ->count();
        $conversionBase = $won + $lost;
        $conversionRate = $conversionBase > 0 ? round(($won / $conversionBase) * 100, 1) : 0.0;

        $summary = [
            'total_leads' => $total,
            'total_contacts' => $crmContactCount,
            'active_deals' => $openCount,
            'pipeline_value' => $pipelineValue,
            'conversion_rate' => $conversionRate,
            'follow_up_due_today' => $followUpDueToday,
            'overdue_follow_up' => $overdueFollowUp,
            'won' => $won,
        ];

        $topSales = (clone $baseQuery)
            ->select('owner_user_id', DB::raw('COUNT(*) as total_leads'), DB::raw('SUM(COALESCE(estimated_value, 0)) as pipeline_value'))
            ->whereNotNull('owner_user_id')
            ->groupBy('owner_user_id')
            ->with('owner:id,name')
            ->orderByDesc('total_leads')
            ->orderByDesc('pipeline_value')
            ->limit(5)
            ->get();

        $sourcePerformance = (clone $baseQuery)
            ->select(
                'lead_source',
                DB::raw('COUNT(*) as total_leads'),
                DB::raw("SUM(CASE WHEN stage = '" . CrmStageCatalog::WON . "' THEN 1 ELSE 0 END) as won_leads"),
                DB::raw('SUM(COALESCE(estimated_value, 0)) as pipeline_value')
            )
            ->whereNotNull('lead_source')
            ->where('lead_source', '!=', '')
            ->groupBy('lead_source')
            ->orderByDesc('total_leads')
            ->limit(6)
            ->get()
            ->map(function ($row) {
                $totalLeads = (int) $row->total_leads;
                $wonLeads = (int) $row->won_leads;

                return [
                    'lead_source' => $row->lead_source,
                    'total_leads' => $totalLeads,
                    'won_leads' => $wonLeads,
                    'pipeline_value' => (float) $row->pipeline_value,
                    'conversion_rate' => $totalLeads > 0 ? round(($wonLeads / $totalLeads) * 100, 1) : 0.0,
                ];
            });

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
                'topSales' => $topSales,
                'sourcePerformance' => $sourcePerformance,
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
            'topSales' => $topSales,
            'sourcePerformance' => $sourcePerformance,
        ]);
    }

    public function create(Request $request): View
    {
        $this->pipelineProvisioner->ensureDefaultPipeline(TenantContext::currentId(), CompanyContext::currentId(), BranchContext::currentId());

        $lead = new CrmLead([
            'stage' => $request->string('stage')->toString() ?: CrmStageCatalog::NEW_LEAD,
            'priority' => 'medium',
            'currency' => app(CurrencySettingsResolver::class)->defaultCurrency(),
            'contact_id' => $request->integer('contact_id') ?: null,
            'title' => $request->string('title')->toString(),
        ]);

        return view('crm::form', [
            'lead' => $lead,
            'owners' => $this->ownerOptions(),
            'stageOptions' => CrmStageCatalog::options(),
            'priorityOptions' => CrmStageCatalog::priorities(),
            'sourceOptions' => CrmSourceCatalog::options(),
            'formAction' => route('crm.store'),
            'method' => 'POST',
            'pageTitle' => 'Tambah Lead CRM',
        ]);
    }

    public function store(StoreCrmLeadRequest $request): RedirectResponse
    {
        app(\App\Support\TenantPlanManager::class)->ensureWithinLimit(PlanLimit::CRM_ACTIVE_DEALS);

        $lead = CrmLead::query()->create($this->payloadFromRequest($request));
        $this->pipelineProvisioner->ensureLeadPlacement($lead);
        $this->followUpTasks->syncPrimaryFollowUp($lead->fresh());
        $this->activityLogger->logLeadCreated($lead);
        $this->wonAutomation->handle($lead->fresh());

        return redirect()->route('crm.show', $lead)->with('status', 'Lead CRM berhasil dibuat.');
    }

    public function show(CrmLead $lead): View
    {
        $lead->load(['contact', 'owner', 'company', 'branch']);
        $this->pipelineProvisioner->ensureLeadPlacement($lead);

        $timeline = CrmActivity::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where(function ($query) use ($lead): void {
                $query->where('lead_id', $lead->id);

                if ($lead->contact_id) {
                    $query->orWhere('contact_id', $lead->contact_id);
                }
            })
            ->with('owner:id,name')
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        return view('crm::show', [
            'lead' => $lead->fresh(['contact', 'owner', 'company', 'branch', 'pipeline', 'stageModel', 'followUpTasks.owner']),
            'timeline' => $timeline,
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
            'owners' => $this->ownerOptions(),
            'stageOptions' => CrmStageCatalog::options(),
            'priorityOptions' => CrmStageCatalog::priorities(),
            'sourceOptions' => CrmSourceCatalog::options(),
            'formAction' => route('crm.update', $lead),
            'method' => 'PUT',
            'pageTitle' => 'Edit Lead CRM',
        ]);
    }

    public function update(UpdateCrmLeadRequest $request, CrmLead $lead): RedirectResponse
    {
        $before = $lead->only([
            'title',
            'owner_user_id',
            'stage',
            'stage_id',
            'priority',
            'lead_source',
            'qualification_status',
            'lead_score',
            'estimated_value',
            'probability',
            'expected_close_date',
            'next_follow_up_at',
            'last_contacted_at',
            'is_archived',
            'visibility_scope',
            'lost_reason',
        ]);
        $lead->update($this->payloadFromRequest($request, $lead));
        $this->pipelineProvisioner->ensureLeadPlacement($lead);
        $this->followUpTasks->syncPrimaryFollowUp($lead->fresh());
        $this->activityLogger->logLeadUpdated($lead->fresh(), $before);
        $this->wonAutomation->handle($lead->fresh());

        return redirect()->route('crm.show', $lead)->with('status', 'Lead CRM berhasil diperbarui.');
    }

    public function updateStage(Request $request, CrmLead $lead): RedirectResponse|JsonResponse
    {
        $request->validate([
            'stage' => ['required', 'in:' . implode(',', array_keys(CrmStageCatalog::options()))],
        ]);

        $stage = $request->string('stage')->toString();
        $fromStage = $lead->stage;

        $lead->forceFill([
            'stage' => $stage,
            'won_at' => $stage === CrmStageCatalog::WON ? now() : null,
            'lost_at' => $stage === CrmStageCatalog::LOST ? now() : null,
            'lost_reason' => $stage === CrmStageCatalog::LOST ? ($lead->lost_reason ?: 'Moved to lost stage') : null,
        ])->save();
        $this->pipelineProvisioner->ensureLeadPlacement($lead);
        $this->activityLogger->logStageChanged($lead->fresh(), $fromStage, $stage);
        $this->wonAutomation->handle($lead->fresh());

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

    public function export(Request $request): StreamedResponse
    {
        $query = CrmLeadScope::applyVisibilityScope(CrmLead::query())
            ->with(['contact', 'owner', 'pipeline', 'stageModel']);
        BooleanQuery::apply($query, 'is_archived', false);

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['Title', 'Contact', 'Owner', 'Pipeline', 'Stage', 'Priority', 'Source', 'Estimated Value', 'Next Follow Up']);

            $query->orderByDesc('updated_at')->chunkById(200, function ($leads) use ($handle): void {
                foreach ($leads as $lead) {
                    fputcsv($handle, [
                        $lead->title,
                        $lead->contact?->name,
                        $lead->owner?->name,
                        $lead->pipeline?->name,
                        $lead->stageModel?->name ?? $lead->stage,
                        $lead->priority,
                        $lead->lead_source,
                        (float) ($lead->estimated_value ?? 0),
                        optional($lead->next_follow_up_at)->toDateTimeString(),
                    ]);
                }
            });

            fclose($handle);
        }, 'crm-leads-' . now()->format('Ymd-His') . '.csv');
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
        $pipeline = $this->pipelineProvisioner->ensureDefaultPipeline(
            TenantContext::currentId(),
            $contact?->company_id ?? CompanyContext::currentId(),
            $contact?->branch_id ?? BranchContext::currentId()
        );
        $stageModel = $pipeline->stages->firstWhere('code', $stage) ?: $pipeline->stages->sortBy('position')->first();

        return [
            'tenant_id' => TenantContext::currentId(),
            'company_id' => $contact?->company_id ?? CompanyContext::currentId(),
            'branch_id' => $contact?->branch_id ?? BranchContext::currentId(),
            'contact_id' => $contact?->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stageModel?->id,
            'owner_user_id' => $data['owner_user_id'] ?? null,
            'title' => $data['title'],
            'stage' => $stage,
            'priority' => $data['priority'] ?? 'medium',
            'lead_source' => $data['lead_source'] ?? null,
            'qualification_status' => $data['qualification_status'] ?? null,
            'lead_score' => $data['lead_score'] ?? null,
            'estimated_value' => $data['estimated_value'] ?? null,
            'currency' => $data['currency'] ?? app(CurrencySettingsResolver::class)->defaultCurrency(),
            'probability' => $data['probability'] ?? $stageModel?->probability_default,
            'expected_close_date' => $data['expected_close_date'] ?? null,
            'next_follow_up_at' => $data['next_follow_up_at'] ?? null,
            'last_contacted_at' => $data['last_contacted_at'] ?? null,
            'won_at' => $stage === CrmStageCatalog::WON ? now() : null,
            'lost_at' => $stage === CrmStageCatalog::LOST ? now() : null,
            'lost_reason' => $data['lost_reason'] ?? null,
            'notes' => $data['notes'] ?? null,
            'labels' => $labels,
            'position' => $lead?->position ?? $this->nextPositionForStage($stage),
            'visibility_scope' => $data['visibility_scope'] ?? 'team',
            'is_archived' => (bool) ($data['is_archived'] ?? false),
            'meta' => [
                'contact_name_snapshot' => $contact?->name,
                'contact_phone_snapshot' => $contact?->mobile ?: $contact?->phone,
            ],
        ];
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
