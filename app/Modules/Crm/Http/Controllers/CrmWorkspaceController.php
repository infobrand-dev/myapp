<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PlatformWebhookReceipt;
use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Contacts\Support\ContactScope;
use App\Modules\Crm\Models\CrmFollowUpTask;
use App\Modules\Crm\Models\CrmLead;
use App\Modules\Crm\Models\CrmPipeline;
use App\Modules\Crm\Models\CrmPipelineStage;
use App\Modules\Crm\Support\CrmFollowUpTaskManager;
use App\Modules\Crm\Support\CrmIntegrationService;
use App\Modules\Crm\Support\CrmLeadScope;
use App\Modules\Crm\Support\CrmOnboardingService;
use App\Modules\Crm\Support\CrmPipelineProvisioner;
use App\Modules\Crm\Support\CrmSourceCatalog;
use App\Modules\Crm\Support\CrmStageCatalog;
use App\Modules\Crm\Support\Customer360TimelineBuilder;
use App\Support\BooleanQuery;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\PlanFeature;
use App\Support\PlanLimit;
use App\Support\TenantContext;
use App\Support\TenantPlanManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CrmWorkspaceController extends Controller
{
    public function __construct(
        private readonly CrmFollowUpTaskManager $followUpTasks,
        private readonly Customer360TimelineBuilder $timelineBuilder,
        private readonly CrmOnboardingService $onboardingService,
        private readonly CrmPipelineProvisioner $pipelineProvisioner,
        private readonly CrmIntegrationService $integrations,
        private readonly \App\Services\Webhooks\WebhookReceiptService $webhookReceipts,
        private readonly \App\Modules\Crm\Support\MetaLeadAdsPayloadMapper $metaLeadMapper,
        private readonly \App\Modules\Crm\Support\CrmLeadIngestionService $leadIngestion,
        private readonly TenantPlanManager $plans,
    ) {
    }

    public function dashboard(Request $request): View
    {
        $tenantId = TenantContext::currentId();
        $user = $request->user();
        $today = now()->startOfDay();

        $baseLeads = CrmLeadScope::applyVisibilityScope(CrmLead::query())
            ->with(['owner', 'stageModel']);
        BooleanQuery::apply($baseLeads, 'is_archived', false);

        $openStages = [CrmStageCatalog::WON, CrmStageCatalog::LOST];
        $won = (clone $baseLeads)->where('stage', CrmStageCatalog::WON)->count();
        $lost = (clone $baseLeads)->where('stage', CrmStageCatalog::LOST)->count();
        $activeDeals = (clone $baseLeads)->whereNotIn('stage', $openStages)->count();
        $pipelineValue = (float) ((clone $baseLeads)->whereNotIn('stage', $openStages)->sum('estimated_value') ?? 0);
        $staleLeads = (clone $baseLeads)
            ->whereNotIn('stage', $openStages)
            ->where(function ($query): void {
                $query->whereNull('last_contacted_at')
                    ->orWhere('last_contacted_at', '<', now()->subDays(7));
            })
            ->count();

        $followUpsBase = CrmFollowUpTask::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'pending');

        if ($user && !$user->hasAnyRole(['Super-admin', 'Admin']) && !$user->can('crm.view_all')) {
            $followUpsBase->where(function ($query) use ($user): void {
                $query->where('owner_user_id', $user->id)
                    ->orWhereNull('owner_user_id');
            });
        }

        $dueToday = (clone $followUpsBase)->whereBetween('due_at', [$today, now()->endOfDay()])->count();
        $overdue = (clone $followUpsBase)->whereNotNull('due_at')->where('due_at', '<', $today)->count();
        $upcoming = (clone $followUpsBase)->where('due_at', '>', now()->endOfDay())->count();

        $sourcePerformance = (clone $baseLeads)
            ->select(
                'lead_source',
                DB::raw('COUNT(*) as total_leads'),
                DB::raw("SUM(CASE WHEN stage = '" . CrmStageCatalog::WON . "' THEN 1 ELSE 0 END) as won_leads")
            )
            ->whereNotNull('lead_source')
            ->where('lead_source', '!=', '')
            ->groupBy('lead_source')
            ->orderByDesc('total_leads')
            ->limit(6)
            ->get();

        $stageBottlenecks = CrmPipelineStage::query()
            ->where('tenant_id', $tenantId)
            ->with('pipeline:id,name')
            ->withCount(['leads' => function ($query): void {
                BooleanQuery::apply($query, 'is_archived', false);
            }])
            ->orderByDesc('leads_count')
            ->orderBy('position')
            ->limit(6)
            ->get();

        $topSales = (clone $baseLeads)
            ->select('owner_user_id', DB::raw('COUNT(*) as total_leads'), DB::raw('SUM(COALESCE(estimated_value, 0)) as pipeline_value'))
            ->whereNotNull('owner_user_id')
            ->groupBy('owner_user_id')
            ->with('owner:id,name')
            ->orderByDesc('total_leads')
            ->orderByDesc('pipeline_value')
            ->limit(5)
            ->get();

        $recentFollowUps = (clone $followUpsBase)
            ->with(['lead:id,title', 'contact:id,name', 'owner:id,name'])
            ->orderByRaw("CASE WHEN due_at IS NULL THEN 1 ELSE 0 END")
            ->orderBy('due_at')
            ->limit(8)
            ->get();

        $conversionBase = $won + $lost;
        $wizard = $this->onboardingService->wizardState($tenantId);

        return view('crm::dashboard.index', [
            'summary' => [
                'leads' => (clone $baseLeads)->count(),
                'active_deals' => $activeDeals,
                'pipeline_value' => $pipelineValue,
                'conversion_rate' => $conversionBase > 0 ? round(($won / $conversionBase) * 100, 1) : 0.0,
                'due_today' => $dueToday,
                'overdue' => $overdue,
                'upcoming' => $upcoming,
                'stale_leads' => $staleLeads,
            ],
            'topSales' => $topSales,
            'sourcePerformance' => $sourcePerformance,
            'stageBottlenecks' => $stageBottlenecks,
            'recentFollowUps' => $recentFollowUps,
            'wizard' => $wizard,
        ]);
    }

    public function followUps(Request $request): View
    {
        $tenantId = TenantContext::currentId();
        $user = $request->user();
        $filter = $request->string('filter')->toString() ?: 'today';
        $today = now()->startOfDay();

        $query = CrmFollowUpTask::query()
            ->where('tenant_id', $tenantId)
            ->with(['lead', 'contact', 'owner']);

        if ($filter === 'mine') {
            $query->where('owner_user_id', $user?->id);
        } elseif ($filter === 'completed') {
            $query->where('status', 'completed');
        } elseif ($filter === 'overdue') {
            $query->where('status', 'pending')->whereNotNull('due_at')->where('due_at', '<', $today);
        } elseif ($filter === 'upcoming') {
            $query->where('status', 'pending')->where('due_at', '>', now()->endOfDay());
        } else {
            $query->where('status', 'pending')->whereBetween('due_at', [$today, now()->endOfDay()]);
            $filter = 'today';
        }

        if ($user && !$user->hasAnyRole(['Super-admin', 'Admin']) && !$user->can('crm.view_all')) {
            $query->where(function ($visibility) use ($user): void {
                $visibility->where('owner_user_id', $user->id)
                    ->orWhereNull('owner_user_id');
            });
        }

        $tasks = $query
            ->orderByRaw("CASE WHEN due_at IS NULL THEN 1 ELSE 0 END")
            ->orderBy('due_at')
            ->paginate(15)
            ->withQueryString();

        return view('crm::follow-ups.index', [
            'tasks' => $tasks,
            'filter' => $filter,
            'owners' => User::query()->where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']),
            'leads' => CrmLeadScope::applyVisibilityScope(CrmLead::query())
                ->orderByDesc('updated_at')
                ->limit(100)
                ->get(['id', 'title', 'contact_id']),
        ]);
    }

    public function storeFollowUp(Request $request): RedirectResponse
    {
        $tenantId = TenantContext::currentId();
        $data = $request->validate([
            'lead_id' => ['nullable', 'integer', Rule::exists('crm_leads', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId))],
            'contact_id' => ['nullable', 'integer', Rule::exists('contacts', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId))],
            'owner_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId))],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_at' => ['nullable', 'date'],
            'priority' => ['nullable', Rule::in(array_keys(CrmStageCatalog::priorities()))],
        ]);

        $lead = !empty($data['lead_id'])
            ? CrmLeadScope::applyVisibilityScope(CrmLead::query())->find($data['lead_id'])
            : null;
        $contact = !empty($data['contact_id'])
            ? ContactScope::applyVisibilityScope(Contact::query())->find($data['contact_id'])
            : null;

        CrmFollowUpTask::query()->create([
            'tenant_id' => $tenantId,
            'company_id' => $lead?->company_id ?? $contact?->company_id ?? CompanyContext::currentId(),
            'branch_id' => $lead?->branch_id ?? $contact?->branch_id ?? BranchContext::currentId(),
            'lead_id' => $lead?->id,
            'contact_id' => $contact?->id ?? $lead?->contact_id,
            'owner_user_id' => $data['owner_user_id'] ?? $lead?->owner_user_id,
            'subject' => $data['subject'],
            'description' => $data['description'] ?? null,
            'due_at' => $data['due_at'] ?? null,
            'status' => 'pending',
            'priority' => $data['priority'] ?? 'medium',
            'sequence_no' => 1,
            'meta' => ['kind' => 'manual_queue_task'],
        ]);

        return back()->with('status', 'Follow-up berhasil ditambahkan.');
    }

    public function completeFollowUp(int $task): RedirectResponse
    {
        $record = CrmFollowUpTask::query()
            ->where('tenant_id', TenantContext::currentId())
            ->findOrFail($task);

        $this->followUpTasks->complete($record);

        return back()->with('status', 'Follow-up ditandai selesai.');
    }

    public function customers(Request $request): View
    {
        $search = trim($request->string('q')->toString());

        $query = ContactScope::applyVisibilityScope(Contact::query())
            ->withCount([
                'crmLeads as crm_open_deals_count' => function ($builder): void {
                    $builder->whereNotIn('stage', [CrmStageCatalog::WON, CrmStageCatalog::LOST]);
                },
                'crmFollowUpTasks as crm_pending_follow_ups_count' => function ($builder): void {
                    $builder->where('status', 'pending');
                },
            ]);

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('mobile', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }

        $customers = $query->orderBy('name')->paginate(16)->withQueryString();

        return view('crm::customers.index', [
            'customers' => $customers,
            'filters' => ['q' => $search],
        ]);
    }

    public function customerShow(Contact $contact): View
    {
        $contact = ContactScope::applyVisibilityScope(Contact::query())->findOrFail($contact->id);
        $customer360 = $this->timelineBuilder->build($contact);

        return view('crm::customers.show', [
            'contact' => $contact,
            'customer360' => $customer360,
        ]);
    }

    public function pipelines(): View
    {
        $tenantId = TenantContext::currentId();
        $this->pipelineProvisioner->ensureDefaultPipeline($tenantId, CompanyContext::currentId(), BranchContext::currentId());

        return view('crm::pipelines.index', [
            'pipelines' => CrmPipeline::query()
                ->where('tenant_id', $tenantId)
                ->with(['stages', 'leads'])
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get(),
            'stageTypes' => [
                'open' => 'Open',
                'won' => 'Won',
                'lost' => 'Lost',
            ],
            'stageLimit' => $this->plans->limit(PlanLimit::CRM_CUSTOM_STAGES, $tenantId),
        ]);
    }

    public function storePipeline(Request $request): RedirectResponse
    {
        $tenantId = TenantContext::currentId();
        $this->plans->ensureWithinLimit(PlanLimit::CRM_PIPELINES, 1, null, $tenantId);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:100'],
        ]);

        CrmPipeline::query()->create([
            'tenant_id' => $tenantId,
            'company_id' => CompanyContext::currentId(),
            'branch_id' => BranchContext::currentId(),
            'name' => $data['name'],
            'code' => $data['code'] ?: str($data['name'])->slug('-'),
            'is_default' => false,
            'is_active' => true,
        ]);

        return back()->with('status', 'Pipeline berhasil dibuat.');
    }

    public function storePipelineStage(Request $request, CrmPipeline $pipeline): RedirectResponse
    {
        abort_unless((int) $pipeline->tenant_id === (int) TenantContext::currentId(), 404);
        $this->plans->ensureWithinLimit(PlanLimit::CRM_CUSTOM_STAGES, 1, null, TenantContext::currentId());

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:100'],
            'probability_default' => ['nullable', 'integer', 'min:0', 'max:100'],
            'stage_type' => ['required', Rule::in(['open', 'won', 'lost'])],
        ]);

        $position = ((int) $pipeline->stages()->max('position')) + 1;

        CrmPipelineStage::query()->create([
            'pipeline_id' => $pipeline->id,
            'tenant_id' => $pipeline->tenant_id,
            'name' => $data['name'],
            'code' => $data['code'] ?: str($data['name'])->slug('-'),
            'position' => $position,
            'probability_default' => $data['probability_default'] ?? 0,
            'stage_type' => $data['stage_type'],
            'color_token' => $data['stage_type'] === 'won' ? 'green' : ($data['stage_type'] === 'lost' ? 'red' : 'primary'),
        ]);

        return back()->with('status', 'Stage pipeline berhasil ditambahkan.');
    }

    public function reorderPipelineStages(Request $request, CrmPipeline $pipeline): RedirectResponse
    {
        abort_unless((int) $pipeline->tenant_id === (int) TenantContext::currentId(), 404);

        $positions = (array) $request->input('positions', []);
        $stageIds = $pipeline->stages()->pluck('id')->all();

        foreach ($positions as $stageId => $position) {
            if (!in_array((int) $stageId, $stageIds, true)) {
                continue;
            }

            CrmPipelineStage::query()
                ->where('pipeline_id', $pipeline->id)
                ->where('id', (int) $stageId)
                ->update(['position' => max((int) $position, 1)]);
        }

        return back()->with('status', 'Urutan stage berhasil diperbarui.');
    }

    public function settings(): View
    {
        $tenantId = TenantContext::currentId();
        $tenant = TenantContext::currentTenant();
        $integrationSettings = $tenant ? $this->integrations->settings($tenant) : [];
        $productOptions = class_exists(\App\Modules\Products\Models\Product::class)
            ? \App\Modules\Products\Models\Product::query()
                ->where('tenant_id', $tenantId)
                ->orderBy('name')
                ->limit(200)
                ->get(['id', 'name', 'sell_price'])
            : collect();
        $owners = User::query()->where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);

        return view('crm::settings.index', [
            'capabilities' => [
                'export' => $this->plans->hasFeature(PlanFeature::CRM_EXPORTS, $tenantId),
                'manager_visibility' => $this->plans->hasFeature(PlanFeature::CRM_MANAGER_VISIBILITY, $tenantId),
                'automation' => $this->plans->hasFeature(PlanFeature::CRM_AUTOMATION, $tenantId),
            ],
            'limits' => [
                'pipelines' => $this->plans->usageState(PlanLimit::CRM_PIPELINES, $tenantId),
                'custom_stages' => $this->plans->usageState(PlanLimit::CRM_CUSTOM_STAGES, $tenantId),
                'active_deals' => $this->plans->usageState(PlanLimit::CRM_ACTIVE_DEALS, $tenantId),
                'contacts' => $this->plans->usageState(PlanLimit::CONTACTS, $tenantId),
                'users' => $this->plans->usageState(PlanLimit::USERS, $tenantId),
            ],
            'sourceOptions' => CrmSourceCatalog::options(),
            'integrationSettings' => $integrationSettings,
            'productOptions' => $productOptions,
            'owners' => $owners,
            'leadCaptureWebhookUrl' => route('crm.webhooks.lead-capture'),
            'metaLeadWebhookUrl' => route('crm.webhooks.meta-leads'),
            'leadCaptureApiUrl' => url('/api/crm/leads'),
            'recentWebhookReceipts' => PlatformWebhookReceipt::query()
                ->where('tenant_id', $tenantId)
                ->where('provider', 'crm')
                ->whereIn('endpoint', ['crm.webhooks.lead-capture', 'crm.webhooks.meta-leads'])
                ->latest('id')
                ->limit(10)
                ->get(),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $tenant = TenantContext::currentTenant();
        abort_unless($tenant, 404);

        $data = $request->validate([
            'on_won_enabled' => ['nullable', 'boolean'],
            'create_sales_quotation' => ['nullable', 'boolean'],
            'create_draft_sale' => ['nullable', 'boolean'],
            'finalize_draft_sale' => ['nullable', 'boolean'],
            'default_product_id' => ['nullable', 'integer'],
            'rotate_lead_capture_token' => ['nullable', 'boolean'],
            'owner_routing_rules_text' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->integrations->update($tenant, $data);

        return back()->with('status', 'Pengaturan integrasi CRM berhasil diperbarui.');
    }

    public function replayWebhookReceipt(int $receipt): RedirectResponse
    {
        $record = PlatformWebhookReceipt::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('provider', 'crm')
            ->findOrFail($receipt);

        try {
            $payload = (array) ($record->payload ?? []);

            if ($record->endpoint === 'crm.webhooks.meta-leads') {
                $mapped = $this->metaLeadMapper->map(new Request($payload));
                $lead = $this->leadIngestion->ingest($mapped, 'external', 'meta_lead_ads_replay');
            } else {
                $lead = $this->leadIngestion->ingest($payload, 'external', 'lead_capture_replay');
            }

            $this->webhookReceipts->markReplayed($record);
            $this->webhookReceipts->markProcessed($record, [
                'replayed_lead_id' => $lead->id,
                'replayed_at' => now()->toIso8601String(),
            ]);

            return back()->with('status', 'Webhook receipt CRM berhasil di-replay.');
        } catch (\Throwable $e) {
            $this->webhookReceipts->markFailed($record, $e->getMessage(), ['replay_failed_at' => now()->toIso8601String()]);

            return back()->with('status', 'Replay webhook CRM gagal: ' . $e->getMessage());
        }
    }

    public function onboarding(): View
    {
        $state = $this->onboardingService->wizardState(TenantContext::currentId());

        if ($state['is_complete']) {
            $tenant = TenantContext::currentTenant();
            if ($tenant) {
                $this->onboardingService->markCompleted($tenant);
            }
        }

        return view('crm::onboarding.index', [
            'wizard' => $state,
        ]);
    }

    public function completeOnboarding(): RedirectResponse
    {
        $tenant = TenantContext::currentTenant();
        if ($tenant) {
            $this->onboardingService->markCompleted($tenant);
        }

        return redirect()->route('crm.dashboard')->with('status', 'Onboarding CRM diselesaikan.');
    }
}
