<?php

namespace App\Http\Controllers;

use App\Mail\PlatformInvoiceIssuedMail;
use App\Mail\PlatformPaymentReceivedMail;
use App\Models\AiCreditTransaction;
use App\Models\AiUsageLog;
use App\Models\PlatformInvoice;
use App\Models\PlatformPlanOrder;
use App\Models\PlatformPayment;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantByoAiRequest;
use App\Models\TenantSubscription;
use App\Services\GoliveAuditService;
use App\Services\AiCreditPricingService;
use App\Services\AiUsageService;
use App\Services\PlatformAffiliateService;
use App\Services\PlatformManualPaymentService;
use App\Services\PlatformMidtransBillingService;
use App\Services\TenantOnboardingSalesService;
use App\Support\ByoAiAddon;
use App\Support\PlanFeature;
use App\Support\PlanLimit;
use App\Support\TenantPlanManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class PlatformOwnerController extends Controller
{
    private const LIMIT_LABELS = [
        PlanLimit::COMPANIES => 'Companies',
        PlanLimit::BRANCHES => 'Branches',
        PlanLimit::USERS => 'Users',
        PlanLimit::TOTAL_STORAGE_BYTES => 'Total Storage (bytes)',
        PlanLimit::PRODUCTS => 'Products',
        PlanLimit::CONTACTS => 'Contacts',
        PlanLimit::WHATSAPP_INSTANCES => 'WhatsApp Instances',
        PlanLimit::SOCIAL_ACCOUNTS => 'Social Accounts',
        PlanLimit::LIVE_CHAT_WIDGETS => 'Live Chat Widgets',
        PlanLimit::CHATBOT_ACCOUNTS => 'Chatbot Accounts',
        PlanLimit::EMAIL_INBOX_ACCOUNTS => 'Email Inbox Accounts',
        PlanLimit::EMAIL_CAMPAIGNS => 'Email Campaigns',
        PlanLimit::WA_BLAST_RECIPIENTS_MONTHLY => 'WA Blast Recipients / Month',
        PlanLimit::EMAIL_RECIPIENTS_MONTHLY => 'Email Recipients / Month',
        PlanLimit::AI_CREDITS_MONTHLY => 'AI Credits / Month',
        PlanLimit::CHATBOT_KNOWLEDGE_DOCUMENTS => 'Chatbot Knowledge Documents',
        PlanLimit::BYO_CHATBOT_ACCOUNTS => 'BYO Chatbot Accounts',
        PlanLimit::BYO_AI_REQUESTS_MONTHLY => 'BYO AI Requests / Month',
        PlanLimit::BYO_AI_TOKENS_MONTHLY => 'BYO AI Tokens / Month',
        PlanLimit::AUTOMATION_WORKFLOWS => 'Automation Workflows',
        PlanLimit::AUTOMATION_EXECUTIONS_MONTHLY => 'Automation Executions / Month',
    ];

    public function dashboard(TenantPlanManager $planManager, AiUsageService $aiUsage, AiCreditPricingService $aiPricing): View
    {
        $tenants = Tenant::query()
            ->with(['activeSubscription.plan:id,name,code'])
            ->withCount(['users', 'companies', 'branches'])
            ->orderByDesc('created_at')
            ->get();

        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfWeek = $now->copy()->startOfWeek();

        $stats = [
            'total_tenants' => $tenants->count(),
            'active_tenants' => $tenants->where('is_active', true)->count(),
            'new_this_month' => $tenants->filter(fn (Tenant $tenant) => $tenant->created_at && $tenant->created_at->gte($startOfMonth))->count(),
            'new_this_week' => $tenants->filter(fn (Tenant $tenant) => $tenant->created_at && $tenant->created_at->gte($startOfWeek))->count(),
            'total_users' => (int) $tenants->sum('users_count'),
            'total_companies' => (int) $tenants->sum('companies_count'),
            'total_branches' => (int) $tenants->sum('branches_count'),
            'paid_orders' => $this->orderTableReady() ? PlatformPlanOrder::query()->where('status', 'paid')->count() : 0,
            'paid_revenue' => $this->orderTableReady() ? (float) PlatformPlanOrder::query()->where('status', 'paid')->sum('amount') : 0,
            'ai_credits_this_month' => $this->aiUsageTableReady()
                ? (int) AiUsageLog::query()->whereBetween('used_at', [$startOfMonth, $now])->sum('credits_used')
                : 0,
        ];

        $acquisitionSeries = collect(range(5, 0))
            ->map(function (int $weeksAgo) use ($tenants, $startOfWeek) {
                $periodStart = $startOfWeek->copy()->subWeeks($weeksAgo);
                $periodEnd = $periodStart->copy()->endOfWeek();

                return [
                    'label' => $periodStart->format('d M'),
                    'count' => $tenants->filter(fn (Tenant $tenant) => $tenant->created_at && $tenant->created_at->betweenIncluded($periodStart, $periodEnd))->count(),
                ];
            })
            ->push([
                'label' => $startOfWeek->format('d M'),
                'count' => $tenants->filter(fn (Tenant $tenant) => $tenant->created_at && $tenant->created_at->betweenIncluded($startOfWeek, $now))->count(),
            ]);

        $planDistribution = $tenants
            ->groupBy(fn (Tenant $tenant) => optional(optional($tenant->activeSubscription)->plan)->display_name ?? optional(optional($tenant->activeSubscription)->plan)->name ?? 'No active plan')
            ->map(fn ($group, $label) => ['label' => $label, 'count' => $group->count()])
            ->sortByDesc('count')
            ->values();

        $tenantsAtRisk = $tenants
            ->map(function (Tenant $tenant) use ($planManager) {
                $usages = $this->limitUsageRows($planManager, $tenant->id);
                $risk = $this->summarizeLimitRisk($usages);

                return [
                    'tenant' => $tenant,
                    'risk' => $risk,
                ];
            })
            ->filter(fn (array $row) => $row['risk']['status'] !== 'ok' || !$row['tenant']->is_active)
            ->sortByDesc(fn (array $row) => $row['risk']['score'])
            ->take(8)
            ->values();

        $tenantAiLeaderboard = $this->aiUsageTableReady()
            ? AiUsageLog::query()
                ->selectRaw('tenant_id, SUM(credits_used) as credits_used')
                ->whereBetween('used_at', [$startOfMonth, $now])
                ->groupBy('tenant_id')
                ->orderByDesc('credits_used')
                ->limit(8)
                ->get()
                ->map(function ($row) use ($tenants, $aiUsage) {
                    $tenant = $tenants->firstWhere('id', (int) $row->tenant_id);
                    if (!$tenant) {
                        return null;
                    }

                    $summary = $aiUsage->summary($tenant->id);

                    return [
                        'tenant' => $tenant,
                        'used' => (int) $row->credits_used,
                        'limit' => $summary['available'],
                        'remaining' => $summary['remaining'],
                    ];
                })
                ->filter()
                ->values()
            : collect();

        return view('platform.dashboard', [
            'stats' => $stats,
            'acquisitionSeries' => $acquisitionSeries,
            'planDistribution' => $planDistribution,
            'recentTenants' => $tenants->take(8),
            'tenantsAtRisk' => $tenantsAtRisk,
            'tenantAiLeaderboard' => $tenantAiLeaderboard,
            'aiUsageReady' => $this->aiUsageTableReady(),
            'aiPricing' => $aiPricing->snapshot(),
        ]);
    }

    public function tenants(Request $request, TenantPlanManager $planManager): View
    {
        $tenants = Tenant::query()
            ->with(['activeSubscription.plan:id,name,code'])
            ->withCount(['users', 'companies', 'branches'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Tenant $tenant) use ($planManager) {
                $usageRows = $this->limitUsageRows($planManager, $tenant->id);
                $tenant->setAttribute('plan_risk', $this->summarizeLimitRisk($usageRows));
                $tenant->setAttribute('usage_rows', $usageRows);

                return $tenant;
            });

        $riskFilter = trim((string) $request->query('risk', ''));
        if ($riskFilter !== '') {
            $tenants = $tenants->filter(function (Tenant $tenant) use ($riskFilter) {
                $risk = (array) ($tenant->getAttribute('plan_risk') ?? []);

                return match ($riskFilter) {
                    'near_limit' => ($risk['status'] ?? null) === 'near_limit',
                    'over_limit' => in_array(($risk['status'] ?? null), ['at_limit', 'over_limit'], true),
                    'heavy_ai' => (bool) ($risk['heavy_ai'] ?? false),
                    'heavy_contacts' => (bool) ($risk['heavy_contacts'] ?? false),
                    default => true,
                };
            })->values();
        }

        return view('platform.tenants.index', [
            'tenants' => $tenants,
            'riskFilter' => $riskFilter,
        ]);
    }

    public function tenant(Tenant $tenant, TenantPlanManager $planManager, AiUsageService $aiUsage, AiCreditPricingService $aiPricing): View
    {
        $relations = [
            'activeSubscription.plan',
            'subscriptions.plan',
            'users' => fn ($query) => $query->latest()->limit(10),
        ];

        if ($this->orderTableReady()) {
            $relations[] = 'planOrders.plan';
        }

        if ($this->invoiceTableReady()) {
            $relations[] = 'platformInvoices.plan';
            $relations[] = 'platformInvoices.payments';
        }

        if ($this->aiCreditTransactionsTableReady()) {
            $relations[] = 'aiCreditTransactions.creator';
        }

        if (class_exists(TenantByoAiRequest::class) && Schema::hasTable('tenant_byo_ai_requests')) {
            $relations[] = 'byoAiRequests.requester';
            $relations[] = 'byoAiRequests.reviewer';
        }

        $tenant->load($relations);

        $plans = SubscriptionPlan::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('platform.tenants.show', [
            'tenant' => $tenant,
            'plans' => $plans,
            'ordersReady' => $this->orderTableReady(),
            'invoicesReady' => $this->invoiceTableReady(),
            'paymentsReady' => $this->paymentTableReady(),
            'usageRows' => $this->limitUsageRows($planManager, $tenant->id),
            'aiSummary' => $aiUsage->summary($tenant->id) + [
                'ready' => $this->aiUsageTableReady(),
                'transactions_ready' => $this->aiCreditTransactionsTableReady(),
            ],
            'aiPricing' => $aiPricing->snapshot(),
            'byoAiSummary' => [
                'enabled' => $planManager->hasFeature(PlanFeature::CHATBOT_BYO_AI, $tenant->id),
                'requests_ready' => Schema::hasTable('tenant_byo_ai_requests'),
                'latest_request' => Schema::hasTable('tenant_byo_ai_requests') ? $tenant->byoAiRequests->sortByDesc('id')->first() : null,
                'providers' => ByoAiAddon::providers(),
                'usage_states' => [
                    'accounts' => $planManager->usageState(PlanLimit::BYO_CHATBOT_ACCOUNTS, $tenant->id),
                    'requests' => $planManager->usageState(PlanLimit::BYO_AI_REQUESTS_MONTHLY, $tenant->id),
                    'tokens' => $planManager->usageState(PlanLimit::BYO_AI_TOKENS_MONTHLY, $tenant->id),
                ],
            ],
        ]);
    }

    public function updateAiCreditPricing(Request $request, AiCreditPricingService $aiPricing): RedirectResponse
    {
        if (!$aiPricing->ready()) {
            return back()->with('error', 'Table AI credit pricing settings belum tersedia. Jalankan migration terlebih dahulu.');
        }

        $data = $request->validate([
            'currency' => ['required', 'string', 'size:3', 'in:IDR'],
            'unit_tokens' => ['required', 'integer', 'min:1'],
            'price_per_credit' => ['required', 'integer', 'min:1'],
            'pack_options' => ['required', 'string', 'max:255'],
        ]);

        $aiPricing->upsert([
            'currency' => $data['currency'],
            'unit_tokens' => (int) $data['unit_tokens'],
            'price_per_credit' => (int) $data['price_per_credit'],
            'pack_options' => $data['pack_options'],
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return back()->with('status', 'Pricing AI Credits berhasil diperbarui.');
    }

    public function topUpAiCredits(Request $request, Tenant $tenant): RedirectResponse
    {
        if (!$this->aiCreditTransactionsTableReady()) {
            return back()->with('error', 'Table AI credit transactions belum tersedia. Jalankan migration terlebih dahulu.');
        }

        $data = $request->validate([
            'credits' => ['required', 'integer', 'min:1'],
            'kind' => ['nullable', 'string', 'max:50'],
            'source' => ['nullable', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'expires_at' => ['nullable', 'date'],
        ]);

        AiCreditTransaction::query()->create([
            'tenant_id' => $tenant->id,
            'kind' => $data['kind'] ?: 'top_up',
            'credits' => (int) $data['credits'],
            'source' => $data['source'] ?: 'platform_owner',
            'reference' => $data['reference'] ?: ('AI-TOPUP-' . now()->format('YmdHis')),
            'notes' => $data['notes'] ?: null,
            'expires_at' => $this->nullableCarbon($data['expires_at'] ?? null),
            'created_by' => auth()->id(),
            'meta' => [
                'assigned_from' => 'platform_owner',
            ],
        ]);

        return back()->with('status', 'AI Credits top up berhasil ditambahkan.');
    }

    public function orders(): View
    {
        $orders = $this->orderTableReady()
            ? PlatformPlanOrder::query()
                ->with(['tenant:id,name,slug', 'plan:id,name,code', 'subscription:id,status', 'invoices:id,platform_plan_order_id,invoice_number,status'])
                ->orderByDesc('created_at')
                ->limit(100)
                ->get()
            : collect();

        return view('platform.orders.index', [
            'orders' => $orders,
            'ordersReady' => $this->orderTableReady(),
            'invoicesReady' => $this->invoiceTableReady(),
        ]);
    }

    public function invoice(PlatformInvoice $invoice, PlatformMidtransBillingService $midtrans, PlatformManualPaymentService $manualPayment): View
    {
        $invoice->load(['tenant', 'plan', 'order', 'payments', 'items']);

        return view('platform.invoices.show', [
            'invoice' => $invoice,
            'publicInvoiceUrl' => $this->publicInvoiceUrl($invoice),
            'publicCheckoutUrl' => $this->publicCheckoutUrl($invoice),
            'midtransReady' => $midtrans->isConfigured(),
            'manualPaymentReady' => $manualPayment->isConfigured(),
            'manualPaymentQuote' => $manualPayment->quoteForInvoice($invoice),
        ]);
    }

    public function publicInvoice(PlatformInvoice $invoice, PlatformMidtransBillingService $midtrans, PlatformManualPaymentService $manualPayment): View
    {
        $invoice->load(['tenant', 'plan', 'order', 'payments', 'items']);

        return view('platform.invoices.public', [
            'invoice' => $invoice,
            'publicCheckoutUrl' => $this->publicCheckoutUrl($invoice),
            'midtransReady' => $midtrans->isConfigured(),
            'manualPaymentReady' => $manualPayment->isConfigured(),
            'manualPaymentQuote' => $manualPayment->quoteForInvoice($invoice),
        ]);
    }

    public function plans(): View
    {
        $plans = SubscriptionPlan::query()
            ->withCount('subscriptions')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('platform.plans.index', [
            'plans' => $plans,
            'featureLabels' => $this->featureLabels(),
            'limitLabels' => self::LIMIT_LABELS,
        ]);
    }

    public function golive(GoliveAuditService $audit): View
    {
        $result = $audit->run();

        return view('platform.golive', [
            'checks' => $result['checks'],
            'stats' => $result['stats'],
            'ready' => $result['ready'],
            'manualChecks' => $result['manual_checks'],
        ]);
    }

    public function editPlan(SubscriptionPlan $plan): View
    {
        return view('platform.plans.edit', [
            'plan' => $plan,
            'featureLabels' => $this->featureLabels(),
            'limitLabels' => self::LIMIT_LABELS,
            'productLineOptions' => $this->productLineOptions(),
            'planPresets' => $this->planPresetTemplates(),
        ]);
    }

    public function updateTenantStatus(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
            'suspend_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $meta = $tenant->meta ?? [];
        $meta['suspend_reason'] = $data['suspend_reason'] ?: null;

        $tenant->forceFill([
            'is_active' => (bool) $data['is_active'],
            'meta' => $meta,
        ])->save();

        return back()->with('status', 'Status tenant berhasil diperbarui.');
    }

    public function updateTenantNotes(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            'platform_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $meta = $tenant->meta ?? [];
        $meta['platform_notes'] = $data['platform_notes'] ?: null;

        $tenant->forceFill([
            'meta' => $meta,
        ])->save();

        return back()->with('status', 'Catatan tenant berhasil diperbarui.');
    }

    public function updatePlan(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'product_line' => ['nullable', 'string', 'max:50'],
            'billing_interval' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'is_public' => ['nullable', 'boolean'],
            'features' => ['nullable', 'array'],
            'limits' => ['nullable', 'array'],
        ]);

        $features = [];
        foreach (array_keys($this->featureLabels()) as $featureKey) {
            $features[$featureKey] = !empty(($data['features'] ?? [])[$featureKey]);
        }

        $limits = [];
        foreach (array_keys(self::LIMIT_LABELS) as $limitKey) {
            $raw = $data['limits'][$limitKey] ?? null;
            $limits[$limitKey] = ($raw === null || $raw === '') ? null : (int) $raw;
        }

        $meta = (array) ($plan->meta ?? []);
        $meta['product_line'] = $data['product_line'] ?: null;

        $plan->forceFill([
            'name' => $data['name'],
            'billing_interval' => $data['billing_interval'] ?: null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => (bool) ($data['is_active'] ?? false),
            'is_public' => (bool) ($data['is_public'] ?? false),
            'features' => $features,
            'limits' => $limits,
            'meta' => $meta,
        ])->save();

        return redirect()
            ->route('platform.plans.index')
            ->with('status', 'Plan berhasil diperbarui.');
    }

    public function assignPlan(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            'subscription_plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
            'status' => ['required', 'string', 'in:active,trialing,past_due,cancelled,expired'],
            'billing_provider' => ['nullable', 'string', 'max:100'],
            'billing_reference' => ['nullable', 'string', 'max:100'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'trial_ends_at' => ['nullable', 'date'],
            'auto_renews' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($tenant, $data): void {
            $activeSubscription = TenantSubscription::query()
                ->where('tenant_id', $tenant->id)
                ->where('status', 'active')
                ->latest('id')
                ->first();
            $byoOverrides = $this->preserveByoOverrides($activeSubscription);

            TenantSubscription::query()
                ->where('tenant_id', $tenant->id)
                ->where('status', 'active')
                ->update([
                    'status' => 'expired',
                    'ends_at' => now(),
                    'updated_at' => now(),
                ]);

            TenantSubscription::create([
                'tenant_id' => $tenant->id,
                'subscription_plan_id' => (int) $data['subscription_plan_id'],
                'status' => $data['status'],
                'billing_provider' => $data['billing_provider'] ?: 'manual',
                'billing_reference' => $data['billing_reference'] ?: ('manual-' . $tenant->id . '-' . now()->timestamp),
                'starts_at' => $this->nullableCarbon($data['starts_at'] ?? null) ?? now(),
                'ends_at' => $this->nullableCarbon($data['ends_at'] ?? null),
                'trial_ends_at' => $this->nullableCarbon($data['trial_ends_at'] ?? null),
                'auto_renews' => (bool) ($data['auto_renews'] ?? false),
                'feature_overrides' => $byoOverrides['feature_overrides'],
                'limit_overrides' => $byoOverrides['limit_overrides'],
                'meta' => [
                    'assigned_from' => 'platform_owner',
                    'assigned_by_user_id' => auth()->id(),
                ],
            ]);
        });

        return back()->with('status', 'Plan tenant berhasil diperbarui.');
    }

    public function updateByoAiAddon(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'allowed_providers' => ['nullable', 'array'],
            'allowed_providers.*' => ['string', 'in:openai,anthropic,groq'],
            'max_byo_chatbot_accounts' => ['nullable', 'integer', 'min:0'],
            'max_byo_ai_requests_monthly' => ['nullable', 'integer', 'min:0'],
            'max_byo_ai_tokens_monthly' => ['nullable', 'integer', 'min:0'],
            'review_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $subscription = TenantSubscription::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->latest('id')
            ->first();

        if (!$subscription) {
            return back()->with('error', 'Tenant belum memiliki subscription aktif untuk diberi add-on BYO AI.');
        }

        $featureOverrides = is_array($subscription->feature_overrides) ? $subscription->feature_overrides : [];
        $limitOverrides = is_array($subscription->limit_overrides) ? $subscription->limit_overrides : [];
        $featureOverrides[PlanFeature::CHATBOT_BYO_AI] = (bool) $data['enabled'];
        $limitOverrides[PlanLimit::BYO_CHATBOT_ACCOUNTS] = $this->nullableLimit($data['max_byo_chatbot_accounts'] ?? null);
        $limitOverrides[PlanLimit::BYO_AI_REQUESTS_MONTHLY] = $this->nullableLimit($data['max_byo_ai_requests_monthly'] ?? null);
        $limitOverrides[PlanLimit::BYO_AI_TOKENS_MONTHLY] = $this->nullableLimit($data['max_byo_ai_tokens_monthly'] ?? null);

        $meta = is_array($subscription->meta) ? $subscription->meta : [];
        $meta['byo_ai'] = [
            'allowed_providers' => array_values(array_unique(array_filter((array) ($data['allowed_providers'] ?? [])))),
            'review_notes' => $data['review_notes'] ?: null,
            'updated_by_user_id' => auth()->id(),
            'updated_at' => now()->toIso8601String(),
        ];

        $subscription->forceFill([
            'feature_overrides' => $featureOverrides,
            'limit_overrides' => $limitOverrides,
            'meta' => $meta,
        ])->save();

        return back()->with('status', 'Add-on BYO AI tenant berhasil diperbarui.');
    }

    public function reviewByoAiRequest(Request $request, Tenant $tenant, TenantByoAiRequest $requestModel): RedirectResponse
    {
        abort_unless($requestModel->tenant_id === $tenant->id, 404);

        $data = $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', ByoAiAddon::requestStatuses())],
            'review_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $requestModel->forceFill([
            'status' => $data['status'],
            'review_notes' => $data['review_notes'] ?: null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ])->save();

        $subscription = TenantSubscription::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->latest('id')
            ->first();

        if ($subscription && in_array($data['status'], [ByoAiAddon::REQUEST_STATUS_APPROVED, ByoAiAddon::REQUEST_STATUS_REJECTED, ByoAiAddon::REQUEST_STATUS_NOT_ELIGIBLE], true)) {
            $this->syncByoAddonFromRequestReview($subscription, $requestModel, $data['status'], $data['review_notes'] ?? null);
        }

        return back()->with('status', 'Status permintaan BYO AI berhasil diperbarui.');
    }

    public function createOrder(Request $request, Tenant $tenant): RedirectResponse
    {
        if (!$this->orderTableReady()) {
            return back()->with('error', 'Table billing order belum tersedia. Jalankan migration terlebih dahulu.');
        }

        $data = $request->validate([
            'subscription_plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'billing_period' => ['nullable', 'string', 'max:50'],
            'buyer_email' => ['nullable', 'email', 'max:255'],
            'payment_channel' => ['nullable', 'string', 'max:50'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $selectedPlan = SubscriptionPlan::query()->findOrFail((int) $data['subscription_plan_id']);
        $sellablePlan = app(TenantOnboardingSalesService::class)->resolvePlanForNewSale($selectedPlan);

        PlatformPlanOrder::create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $sellablePlan->id,
            'order_number' => $this->nextOrderNumber(),
            'status' => 'pending',
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?: 'IDR',
            'billing_period' => $data['billing_period'] ?: 'monthly',
            'buyer_email' => $data['buyer_email'] ?: optional($tenant->users()->orderBy('id')->first())->email,
            'payment_channel' => $data['payment_channel'] ?: 'manual',
            'starts_at' => $this->nullableCarbon($data['starts_at'] ?? null),
            'ends_at' => $this->nullableCarbon($data['ends_at'] ?? null),
            'meta' => [
                'created_from' => 'platform_owner',
                'created_by_user_id' => auth()->id(),
                'requested_plan_id' => $selectedPlan->id,
                'requested_plan_code' => $selectedPlan->code,
                'resolved_plan_id' => $sellablePlan->id,
                'resolved_plan_code' => $sellablePlan->code,
            ],
        ]);

        $message = 'Order plan berhasil dibuat.';
        if ($sellablePlan->id !== $selectedPlan->id) {
            $message .= " Plan legacy {$selectedPlan->code} dialihkan ke {$sellablePlan->code} untuk sales baru.";
        }

        return back()->with('status', $message);
    }

    public function markOrderPaid(PlatformPlanOrder $order, TenantOnboardingSalesService $onboardingSales, PlatformAffiliateService $affiliates): RedirectResponse
    {
        if (!$this->orderTableReady()) {
            return back()->with('error', 'Table billing order belum tersedia. Jalankan migration terlebih dahulu.');
        }

        if ($order->status === 'paid') {
            return back()->with('info', 'Order ini sudah berstatus paid.');
        }

        $welcomePayload = null;
        $paymentMailQueue = [];

        DB::transaction(function () use ($order, $onboardingSales, &$welcomePayload, &$paymentMailQueue): void {
            TenantSubscription::query()
                ->where('tenant_id', $order->tenant_id)
                ->where('status', 'active')
                ->update([
                    'status' => 'expired',
                    'ends_at' => now(),
                    'updated_at' => now(),
                ]);

            $subscription = TenantSubscription::create([
                'tenant_id' => $order->tenant_id,
                'subscription_plan_id' => $order->subscription_plan_id,
                'status' => 'active',
                'billing_provider' => $order->payment_channel ?: 'manual',
                'billing_reference' => $order->order_number,
                'starts_at' => $order->starts_at ?: now(),
                'ends_at' => $order->ends_at,
                'auto_renews' => false,
                'meta' => [
                    'source_order_id' => $order->id,
                    'assigned_from' => 'platform_owner_order',
                    'assigned_by_user_id' => auth()->id(),
                ],
            ]);

            $order->forceFill([
                'status' => 'paid',
                'paid_at' => now(),
                'tenant_subscription_id' => $subscription->id,
            ])->save();

            foreach ($order->invoices()->get() as $invoice) {
                $invoice->forceFill([
                    'status' => 'paid',
                    'paid_at' => $order->paid_at ?: now(),
                ])->save();

                $payment = PlatformPayment::query()->firstOrCreate(
                    [
                        'platform_invoice_id' => $invoice->id,
                        'reference' => $order->order_number,
                    ],
                    [
                        'tenant_id' => $invoice->tenant_id,
                        'amount' => $invoice->amount,
                        'currency' => $invoice->currency,
                        'status' => 'paid',
                        'payment_channel' => $order->payment_channel ?: 'manual',
                        'paid_at' => $order->paid_at ?: now(),
                        'meta' => [
                            'recorded_from' => 'mark_order_paid',
                            'recorded_by_user_id' => auth()->id(),
                        ],
                    ]
                );

                $paymentMailQueue[] = [
                    'invoice' => $invoice->fresh(['tenant', 'plan', 'order']),
                    'payment' => $payment,
                ];
            }

            $welcomePayload = $onboardingSales->completePaidOnboarding(
                $order->fresh(['tenant']),
                $order->paid_at ?: now()
            );
        });

        foreach ($paymentMailQueue as $mailPayload) {
            $this->sendPlatformPaymentReceivedMail($mailPayload['invoice'], $mailPayload['payment']);
        }

        $affiliates->finalizeSale($order->fresh(['affiliateReferral.affiliate', 'tenant', 'plan']));

        if ($welcomePayload) {
            $onboardingSales->queueWelcomeMail($welcomePayload);
        }

        return back()->with('status', 'Order ditandai paid, invoice disinkronkan, dan subscription tenant sudah diaktifkan.');
    }

    public function createInvoice(PlatformPlanOrder $order): RedirectResponse
    {
        if (!$this->invoiceTableReady()) {
            return back()->with('error', 'Table platform invoice belum tersedia. Jalankan migration terlebih dahulu.');
        }

        if ($order->invoices()->exists()) {
            return back()->with('info', 'Order ini sudah memiliki invoice.');
        }

        $invoice = PlatformInvoice::create([
            'tenant_id' => $order->tenant_id,
            'platform_plan_order_id' => $order->id,
            'subscription_plan_id' => $order->subscription_plan_id,
            'invoice_number' => $this->nextInvoiceNumber(),
            'status' => 'issued',
            'amount' => $order->amount,
            'currency' => $order->currency,
            'issued_at' => now(),
            'due_at' => ($order->starts_at ?: now())->copy()->addDays(7),
            'meta' => [
                'source_order_id' => $order->id,
                'created_by_user_id' => auth()->id(),
            ],
        ]);

        $invoice->items()->create([
            'item_type' => 'plan',
            'item_code' => optional($order->plan)->code,
            'name' => optional($order->plan)->display_name ?: optional($order->plan)->name ?: 'Subscription Plan',
            'description' => 'Tagihan langganan plan untuk workspace ' . optional($order->tenant)->name,
            'quantity' => 1,
            'unit_price' => $order->amount,
            'total_price' => $order->amount,
            'meta' => [
                'subscription_plan_id' => $order->subscription_plan_id,
                'source_order_id' => $order->id,
                'source' => 'platform_owner',
            ],
        ]);

        $invoice->syncAmountFromItems();

        $this->sendPlatformInvoiceIssuedMail($invoice);

        return back()->with('status', 'Invoice platform berhasil dibuat dari order.');
    }

    public function resendInvoice(PlatformInvoice $invoice): RedirectResponse
    {
        $this->sendPlatformInvoiceIssuedMail($invoice->fresh(['tenant', 'plan', 'order']));

        return back()->with('status', 'Email invoice berhasil dikirim ulang.');
    }

    public function recordPayment(Request $request, PlatformInvoice $invoice, TenantOnboardingSalesService $onboardingSales, PlatformAffiliateService $affiliates): RedirectResponse
    {
        if (!$this->paymentTableReady()) {
            return back()->with('error', 'Table platform payment belum tersedia. Jalankan migration terlebih dahulu.');
        }

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'payment_channel' => ['nullable', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:100'],
            'paid_at' => ['nullable', 'date'],
        ]);

        $welcomePayload = null;

        DB::transaction(function () use ($invoice, $data, $onboardingSales, &$welcomePayload): void {
            $payment = PlatformPayment::create([
                'tenant_id' => $invoice->tenant_id,
                'platform_invoice_id' => $invoice->id,
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?: $invoice->currency,
                'status' => 'paid',
                'payment_channel' => $data['payment_channel'] ?: 'manual',
                'reference' => $data['reference'] ?: $invoice->invoice_number,
                'paid_at' => $this->nullableCarbon($data['paid_at'] ?? null) ?: now(),
                'meta' => [
                    'recorded_by_user_id' => auth()->id(),
                ],
            ]);

            $invoice->forceFill([
                'status' => 'paid',
                'paid_at' => $this->nullableCarbon($data['paid_at'] ?? null) ?: now(),
            ])->save();

            $order = $invoice->order;
            if ($order && $order->status !== 'paid') {
                $subscription = $this->activateSubscriptionFromBilling(
                    $invoice->tenant_id,
                    $invoice->subscription_plan_id,
                    $data['payment_channel'] ?: 'manual',
                    $invoice->invoice_number,
                    $order->starts_at ?: now(),
                    $order->ends_at
                );

                $order->forceFill([
                    'status' => 'paid',
                    'paid_at' => $invoice->paid_at,
                    'tenant_subscription_id' => $subscription->id,
                ])->save();

                $welcomePayload = $onboardingSales->completePaidOnboarding(
                    $order->fresh(['tenant']),
                    $invoice->paid_at
                );
            }

            $this->sendPlatformPaymentReceivedMail($invoice->fresh(['tenant', 'plan']), $payment);
        });

        if ($welcomePayload) {
            $onboardingSales->queueWelcomeMail($welcomePayload);
        }

        if ($invoice->order) {
            $affiliates->finalizeSale($invoice->order->fresh(['affiliateReferral.affiliate', 'tenant', 'plan']), $invoice->paid_at);
        }

        return back()->with('status', 'Payment platform berhasil dicatat.');
    }

    public function cancelOrder(PlatformPlanOrder $order): RedirectResponse
    {
        if (!$this->orderTableReady()) {
            return back()->with('error', 'Table billing order belum tersedia. Jalankan migration terlebih dahulu.');
        }

        if ($order->status === 'paid') {
            return back()->with('error', 'Order yang sudah paid tidak bisa dibatalkan dari flow ini.');
        }

        $order->forceFill([
            'status' => 'cancelled',
        ])->save();

        return back()->with('status', 'Order berhasil dibatalkan.');
    }

    public function voidOrder(PlatformPlanOrder $order, PlatformAffiliateService $affiliates): RedirectResponse
    {
        if (!$this->orderTableReady() || !$this->invoiceTableReady() || !$this->paymentTableReady()) {
            return back()->with('error', 'Table billing platform belum lengkap. Jalankan migration terlebih dahulu.');
        }

        if ($order->status !== 'paid') {
            return back()->with('error', 'Hanya order yang sudah paid yang bisa di-void.');
        }

        DB::transaction(function () use ($order, $affiliates): void {
            $order->loadMissing(['invoices.payments', 'subscription', 'affiliateReferral']);

            $subscription = $order->subscription;
            if ($subscription && (int) $subscription->tenant_id === (int) $order->tenant_id) {
                $meta = (array) ($subscription->meta ?? []);
                if ((int) ($meta['source_order_id'] ?? 0) === (int) $order->id) {
                    $meta['voided_from_order_id'] = $order->id;
                    $meta['voided_by_user_id'] = auth()->id();
                    $meta['voided_at'] = now()->toIso8601String();

                    $subscription->forceFill([
                        'status' => 'cancelled',
                        'ends_at' => now(),
                        'auto_renews' => false,
                        'meta' => $meta,
                    ])->save();
                }
            }

            foreach ($order->invoices as $invoice) {
                $invoiceMeta = (array) ($invoice->meta ?? []);
                $invoiceMeta['voided_by_user_id'] = auth()->id();
                $invoiceMeta['voided_at'] = now()->toIso8601String();
                $invoiceMeta['voided_from_order_id'] = $order->id;

                $invoice->forceFill([
                    'status' => 'void',
                    'paid_at' => null,
                    'meta' => $invoiceMeta,
                ])->save();

                foreach ($invoice->payments as $payment) {
                    $paymentMeta = (array) ($payment->meta ?? []);
                    $paymentMeta['voided_by_user_id'] = auth()->id();
                    $paymentMeta['voided_at'] = now()->toIso8601String();
                    $paymentMeta['voided_from_order_id'] = $order->id;

                    $payment->forceFill([
                        'status' => 'void',
                        'paid_at' => null,
                        'meta' => $paymentMeta,
                    ])->save();
                }
            }

            $orderMeta = (array) ($order->meta ?? []);
            $orderMeta['voided_by_user_id'] = auth()->id();
            $orderMeta['voided_at'] = now()->toIso8601String();

            $order->forceFill([
                'status' => 'void',
                'paid_at' => null,
                'tenant_subscription_id' => null,
                'meta' => $orderMeta,
            ])->save();

            $affiliates->voidSale($order, 'platform_owner_void');
        });

        return back()->with('status', 'Order paid berhasil di-void dan tidak lagi dihitung sebagai omset.');
    }

    private function limitUsageRows(TenantPlanManager $planManager, int $tenantId): array
    {
        $rows = [];

        foreach (self::LIMIT_LABELS as $key => $label) {
            $state = $planManager->usageState($key, $tenantId);
            $rows[] = [
                'key' => $key,
                'label' => $label,
                'limit' => $state['limit'],
                'usage' => $state['usage'],
                'remaining' => $state['remaining'],
                'status' => $state['status'],
                'advice' => $planManager->limitActionAdvice($key, $state['status'], $tenantId),
            ];
        }

        return $rows;
    }

    private function featureLabels(): array
    {
        return [
            PlanFeature::MULTI_COMPANY => 'Multi company',
            PlanFeature::CONVERSATIONS => 'Conversations inbox',
            PlanFeature::CRM => 'CRM pipeline',
            PlanFeature::COMMERCE => 'Commerce suite',
            PlanFeature::PROJECT_MANAGEMENT => 'Project management',
            PlanFeature::LIVE_CHAT => 'Live chat widget',
            PlanFeature::SOCIAL_MEDIA => 'Social media conversations',
            PlanFeature::CHATBOT_AI => 'Chatbot AI',
            PlanFeature::CHATBOT_BYO_AI => 'Chatbot BYO AI add-on',
            PlanFeature::WHATSAPP_API => 'WhatsApp API',
            PlanFeature::WHATSAPP_WEB => 'WhatsApp Web',
            'multi_branch' => 'Multi branch',
            PlanFeature::EMAIL_MARKETING => 'Email marketing',
            PlanFeature::ADVANCED_REPORTS => 'Advanced reports',
            'inventory' => 'Inventory',
            'finance' => 'Finance',
            'pos' => 'Point of Sale',
        ];
    }

    private function productLineOptions(): array
    {
        return [
            'omnichannel' => 'Omnichannel',
            'crm' => 'CRM',
            'commerce' => 'Commerce',
            'project_management' => 'Project Management',
            'internal' => 'Internal',
        ];
    }

    private function planPresetTemplates(): array
    {
        return [
            'omnichannel_starter' => [
                'label' => 'Omnichannel Starter',
                'description' => 'Social inbox, live chat, dan CRM lite untuk tim kecil yang baru mulai omnichannel.',
                'product_line' => 'omnichannel',
                'features' => [
                    PlanFeature::MULTI_COMPANY => false,
                    PlanFeature::CONVERSATIONS => true,
                    PlanFeature::CRM => true,
                    PlanFeature::COMMERCE => false,
                    PlanFeature::PROJECT_MANAGEMENT => false,
                    PlanFeature::LIVE_CHAT => true,
                    PlanFeature::SOCIAL_MEDIA => true,
                    PlanFeature::CHATBOT_AI => false,
                    PlanFeature::CHATBOT_BYO_AI => false,
                    PlanFeature::WHATSAPP_API => false,
                    PlanFeature::WHATSAPP_WEB => false,
                    PlanFeature::EMAIL_MARKETING => false,
                    PlanFeature::ADVANCED_REPORTS => false,
                    'multi_branch' => false,
                    'inventory' => false,
                    'finance' => false,
                    'pos' => false,
                ],
                'limits' => [
                    PlanLimit::COMPANIES => 1,
                    PlanLimit::BRANCHES => 1,
                    PlanLimit::USERS => 5,
                    PlanLimit::TOTAL_STORAGE_BYTES => 1073741824,
                    PlanLimit::PRODUCTS => 100,
                    PlanLimit::CONTACTS => 2000,
                    PlanLimit::WHATSAPP_INSTANCES => 0,
                    PlanLimit::SOCIAL_ACCOUNTS => 2,
                    PlanLimit::LIVE_CHAT_WIDGETS => 1,
                    PlanLimit::CHATBOT_ACCOUNTS => 0,
                    PlanLimit::EMAIL_INBOX_ACCOUNTS => 0,
                    PlanLimit::EMAIL_CAMPAIGNS => 0,
                    PlanLimit::WA_BLAST_RECIPIENTS_MONTHLY => 0,
                    PlanLimit::EMAIL_RECIPIENTS_MONTHLY => 0,
                    PlanLimit::AI_CREDITS_MONTHLY => 0,
                    PlanLimit::CHATBOT_KNOWLEDGE_DOCUMENTS => 0,
                    PlanLimit::BYO_CHATBOT_ACCOUNTS => 0,
                    PlanLimit::BYO_AI_REQUESTS_MONTHLY => 0,
                    PlanLimit::BYO_AI_TOKENS_MONTHLY => 0,
                    PlanLimit::AUTOMATION_WORKFLOWS => 0,
                    PlanLimit::AUTOMATION_EXECUTIONS_MONTHLY => 0,
                ],
            ],
            'omnichannel_growth' => [
                'label' => 'Omnichannel Growth',
                'description' => 'Paket rekomendasi dengan AI, WhatsApp API, dan WhatsApp Web untuk tim yang mulai scale.',
                'product_line' => 'omnichannel',
                'features' => [
                    PlanFeature::MULTI_COMPANY => true,
                    PlanFeature::CONVERSATIONS => true,
                    PlanFeature::CRM => true,
                    PlanFeature::COMMERCE => false,
                    PlanFeature::PROJECT_MANAGEMENT => false,
                    PlanFeature::LIVE_CHAT => true,
                    PlanFeature::SOCIAL_MEDIA => true,
                    PlanFeature::CHATBOT_AI => true,
                    PlanFeature::CHATBOT_BYO_AI => false,
                    PlanFeature::WHATSAPP_API => true,
                    PlanFeature::WHATSAPP_WEB => true,
                    PlanFeature::EMAIL_MARKETING => false,
                    PlanFeature::ADVANCED_REPORTS => true,
                    'multi_branch' => true,
                    'inventory' => false,
                    'finance' => false,
                    'pos' => false,
                ],
                'limits' => [
                    PlanLimit::COMPANIES => 1,
                    PlanLimit::BRANCHES => 3,
                    PlanLimit::USERS => 15,
                    PlanLimit::TOTAL_STORAGE_BYTES => 5368709120,
                    PlanLimit::PRODUCTS => 1000,
                    PlanLimit::CONTACTS => 10000,
                    PlanLimit::WHATSAPP_INSTANCES => 1,
                    PlanLimit::SOCIAL_ACCOUNTS => 5,
                    PlanLimit::LIVE_CHAT_WIDGETS => 2,
                    PlanLimit::CHATBOT_ACCOUNTS => 2,
                    PlanLimit::EMAIL_INBOX_ACCOUNTS => 1,
                    PlanLimit::EMAIL_CAMPAIGNS => 0,
                    PlanLimit::WA_BLAST_RECIPIENTS_MONTHLY => 1500,
                    PlanLimit::EMAIL_RECIPIENTS_MONTHLY => 0,
                    PlanLimit::AI_CREDITS_MONTHLY => 500,
                    PlanLimit::CHATBOT_KNOWLEDGE_DOCUMENTS => 25,
                    PlanLimit::BYO_CHATBOT_ACCOUNTS => 0,
                    PlanLimit::BYO_AI_REQUESTS_MONTHLY => 0,
                    PlanLimit::BYO_AI_TOKENS_MONTHLY => 0,
                    PlanLimit::AUTOMATION_WORKFLOWS => 0,
                    PlanLimit::AUTOMATION_EXECUTIONS_MONTHLY => 0,
                ],
            ],
            'omnichannel_scale' => [
                'label' => 'Omnichannel Scale',
                'description' => 'Stack omnichannel lengkap untuk operasional multi-admin dengan channel dan kapasitas besar.',
                'product_line' => 'omnichannel',
                'features' => [
                    PlanFeature::MULTI_COMPANY => true,
                    PlanFeature::CONVERSATIONS => true,
                    PlanFeature::CRM => true,
                    PlanFeature::COMMERCE => false,
                    PlanFeature::PROJECT_MANAGEMENT => false,
                    PlanFeature::LIVE_CHAT => true,
                    PlanFeature::SOCIAL_MEDIA => true,
                    PlanFeature::CHATBOT_AI => true,
                    PlanFeature::CHATBOT_BYO_AI => false,
                    PlanFeature::WHATSAPP_API => true,
                    PlanFeature::WHATSAPP_WEB => true,
                    PlanFeature::EMAIL_MARKETING => false,
                    PlanFeature::ADVANCED_REPORTS => true,
                    'multi_branch' => true,
                    'inventory' => false,
                    'finance' => false,
                    'pos' => false,
                ],
                'limits' => [
                    PlanLimit::COMPANIES => 3,
                    PlanLimit::BRANCHES => 10,
                    PlanLimit::USERS => 50,
                    PlanLimit::TOTAL_STORAGE_BYTES => 21474836480,
                    PlanLimit::PRODUCTS => 5000,
                    PlanLimit::CONTACTS => 50000,
                    PlanLimit::WHATSAPP_INSTANCES => 5,
                    PlanLimit::SOCIAL_ACCOUNTS => 15,
                    PlanLimit::LIVE_CHAT_WIDGETS => 5,
                    PlanLimit::CHATBOT_ACCOUNTS => 10,
                    PlanLimit::EMAIL_INBOX_ACCOUNTS => 3,
                    PlanLimit::EMAIL_CAMPAIGNS => 0,
                    PlanLimit::WA_BLAST_RECIPIENTS_MONTHLY => 10000,
                    PlanLimit::EMAIL_RECIPIENTS_MONTHLY => 0,
                    PlanLimit::AI_CREDITS_MONTHLY => 2500,
                    PlanLimit::CHATBOT_KNOWLEDGE_DOCUMENTS => 200,
                    PlanLimit::BYO_CHATBOT_ACCOUNTS => 0,
                    PlanLimit::BYO_AI_REQUESTS_MONTHLY => 0,
                    PlanLimit::BYO_AI_TOKENS_MONTHLY => 0,
                    PlanLimit::AUTOMATION_WORKFLOWS => 0,
                    PlanLimit::AUTOMATION_EXECUTIONS_MONTHLY => 0,
                ],
            ],
            'crm' => [
                'label' => 'CRM',
                'description' => 'Pipeline lead dan kontak tanpa membuka modul transaksi atau omnichannel penuh.',
                'product_line' => 'crm',
                'features' => [
                    PlanFeature::MULTI_COMPANY => false,
                    PlanFeature::CONVERSATIONS => false,
                    PlanFeature::CRM => true,
                    PlanFeature::COMMERCE => false,
                    PlanFeature::PROJECT_MANAGEMENT => false,
                    PlanFeature::LIVE_CHAT => false,
                    PlanFeature::SOCIAL_MEDIA => false,
                    PlanFeature::CHATBOT_AI => false,
                    PlanFeature::WHATSAPP_API => false,
                    PlanFeature::WHATSAPP_WEB => false,
                    PlanFeature::EMAIL_MARKETING => false,
                    PlanFeature::ADVANCED_REPORTS => false,
                    'multi_branch' => false,
                    'inventory' => false,
                    'finance' => false,
                    'pos' => false,
                ],
                'limits' => [
                    PlanLimit::COMPANIES => 1,
                    PlanLimit::BRANCHES => 1,
                    PlanLimit::USERS => 5,
                    PlanLimit::TOTAL_STORAGE_BYTES => 1073741824,
                    PlanLimit::PRODUCTS => 0,
                    PlanLimit::CONTACTS => 5000,
                    PlanLimit::WHATSAPP_INSTANCES => 0,
                    PlanLimit::SOCIAL_ACCOUNTS => 0,
                    PlanLimit::LIVE_CHAT_WIDGETS => 0,
                    PlanLimit::CHATBOT_ACCOUNTS => 0,
                    PlanLimit::EMAIL_INBOX_ACCOUNTS => 0,
                    PlanLimit::EMAIL_CAMPAIGNS => 0,
                    PlanLimit::WA_BLAST_RECIPIENTS_MONTHLY => 0,
                    PlanLimit::EMAIL_RECIPIENTS_MONTHLY => 0,
                    PlanLimit::AI_CREDITS_MONTHLY => 0,
                    PlanLimit::CHATBOT_KNOWLEDGE_DOCUMENTS => 0,
                    PlanLimit::AUTOMATION_WORKFLOWS => 0,
                    PlanLimit::AUTOMATION_EXECUTIONS_MONTHLY => 0,
                ],
            ],
            'commerce' => [
                'label' => 'Commerce',
                'description' => 'Sales, payments, products, inventory, purchases, finance, discounts, dan POS.',
                'product_line' => 'commerce',
                'features' => [
                    PlanFeature::MULTI_COMPANY => true,
                    PlanFeature::CONVERSATIONS => false,
                    PlanFeature::CRM => false,
                    PlanFeature::COMMERCE => true,
                    PlanFeature::PROJECT_MANAGEMENT => false,
                    PlanFeature::LIVE_CHAT => false,
                    PlanFeature::SOCIAL_MEDIA => false,
                    PlanFeature::CHATBOT_AI => false,
                    PlanFeature::WHATSAPP_API => false,
                    PlanFeature::WHATSAPP_WEB => false,
                    PlanFeature::EMAIL_MARKETING => false,
                    PlanFeature::ADVANCED_REPORTS => true,
                    'multi_branch' => true,
                    'inventory' => true,
                    'finance' => true,
                    'pos' => true,
                ],
                'limits' => [
                    PlanLimit::COMPANIES => 1,
                    PlanLimit::BRANCHES => 3,
                    PlanLimit::USERS => 10,
                    PlanLimit::TOTAL_STORAGE_BYTES => 5368709120,
                    PlanLimit::PRODUCTS => 1000,
                    PlanLimit::CONTACTS => 3000,
                    PlanLimit::WHATSAPP_INSTANCES => 0,
                    PlanLimit::SOCIAL_ACCOUNTS => 0,
                    PlanLimit::LIVE_CHAT_WIDGETS => 0,
                    PlanLimit::CHATBOT_ACCOUNTS => 0,
                    PlanLimit::EMAIL_INBOX_ACCOUNTS => 0,
                    PlanLimit::EMAIL_CAMPAIGNS => 0,
                    PlanLimit::WA_BLAST_RECIPIENTS_MONTHLY => 0,
                    PlanLimit::EMAIL_RECIPIENTS_MONTHLY => 0,
                    PlanLimit::AI_CREDITS_MONTHLY => 0,
                    PlanLimit::CHATBOT_KNOWLEDGE_DOCUMENTS => 0,
                    PlanLimit::AUTOMATION_WORKFLOWS => 0,
                    PlanLimit::AUTOMATION_EXECUTIONS_MONTHLY => 0,
                ],
            ],
            'project_management' => [
                'label' => 'Project Management',
                'description' => 'Task, workflow, dan kolaborasi proyek tanpa membuka bundle commerce atau omnichannel.',
                'product_line' => 'project_management',
                'features' => [
                    PlanFeature::MULTI_COMPANY => true,
                    PlanFeature::CONVERSATIONS => false,
                    PlanFeature::CRM => false,
                    PlanFeature::COMMERCE => false,
                    PlanFeature::PROJECT_MANAGEMENT => true,
                    PlanFeature::LIVE_CHAT => false,
                    PlanFeature::SOCIAL_MEDIA => false,
                    PlanFeature::CHATBOT_AI => false,
                    PlanFeature::WHATSAPP_API => false,
                    PlanFeature::WHATSAPP_WEB => false,
                    PlanFeature::EMAIL_MARKETING => false,
                    PlanFeature::ADVANCED_REPORTS => false,
                    'multi_branch' => false,
                    'inventory' => false,
                    'finance' => false,
                    'pos' => false,
                ],
                'limits' => [
                    PlanLimit::COMPANIES => 1,
                    PlanLimit::BRANCHES => 3,
                    PlanLimit::USERS => 10,
                    PlanLimit::TOTAL_STORAGE_BYTES => 5368709120,
                    PlanLimit::PRODUCTS => 0,
                    PlanLimit::CONTACTS => 1000,
                    PlanLimit::WHATSAPP_INSTANCES => 0,
                    PlanLimit::SOCIAL_ACCOUNTS => 0,
                    PlanLimit::LIVE_CHAT_WIDGETS => 0,
                    PlanLimit::CHATBOT_ACCOUNTS => 0,
                    PlanLimit::EMAIL_INBOX_ACCOUNTS => 0,
                    PlanLimit::EMAIL_CAMPAIGNS => 0,
                    PlanLimit::WA_BLAST_RECIPIENTS_MONTHLY => 0,
                    PlanLimit::EMAIL_RECIPIENTS_MONTHLY => 0,
                    PlanLimit::AI_CREDITS_MONTHLY => 0,
                    PlanLimit::CHATBOT_KNOWLEDGE_DOCUMENTS => 0,
                    PlanLimit::AUTOMATION_WORKFLOWS => 25,
                    PlanLimit::AUTOMATION_EXECUTIONS_MONTHLY => 5000,
                ],
            ],
        ];
    }

    private function summarizeLimitRisk(array $usageRows): array
    {
        $priority = [
            'ok' => 0,
            'near_limit' => 1,
            'at_limit' => 2,
            'over_limit' => 3,
        ];

        $status = 'ok';
        $score = 0;

        foreach ($usageRows as $row) {
            $rowStatus = (string) ($row['status'] ?? 'ok');
            if (($priority[$rowStatus] ?? 0) > ($priority[$status] ?? 0)) {
                $status = $rowStatus;
            }

            $score += match ($rowStatus) {
                'over_limit' => 10,
                'at_limit' => 6,
                'near_limit' => 2,
                default => 0,
            };
        }

        $contacts = collect($usageRows)->firstWhere('key', PlanLimit::CONTACTS);
        $ai = collect($usageRows)->firstWhere('key', PlanLimit::AI_CREDITS_MONTHLY);

        return [
            'status' => $status,
            'score' => $score,
            'heavy_contacts' => in_array($contacts['status'] ?? 'ok', ['near_limit', 'at_limit', 'over_limit'], true),
            'heavy_ai' => in_array($ai['status'] ?? 'ok', ['near_limit', 'at_limit', 'over_limit'], true),
        ];
    }

    private function nullableCarbon(?string $value): ?Carbon
    {
        return $value ? Carbon::parse($value) : null;
    }

    private function nullableLimit(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return max(0, (int) $value);
    }

    private function syncByoAddonFromRequestReview(TenantSubscription $subscription, TenantByoAiRequest $requestModel, string $status, ?string $reviewNotes = null): void
    {
        $featureOverrides = is_array($subscription->feature_overrides) ? $subscription->feature_overrides : [];
        $limitOverrides = is_array($subscription->limit_overrides) ? $subscription->limit_overrides : [];
        $meta = is_array($subscription->meta) ? $subscription->meta : [];
        $currentAllowedProviders = array_values(array_unique(array_filter((array) data_get($meta, 'byo_ai.allowed_providers', []))));

        if ($status === ByoAiAddon::REQUEST_STATUS_APPROVED) {
            $featureOverrides[PlanFeature::CHATBOT_BYO_AI] = true;
            $defaults = $this->defaultByoAddonLimits($requestModel);

            foreach ($defaults as $key => $value) {
                if (!array_key_exists($key, $limitOverrides) || $limitOverrides[$key] === null || (int) $limitOverrides[$key] <= 0) {
                    $limitOverrides[$key] = $value;
                }
            }

            if ($currentAllowedProviders === []) {
                $preferredProvider = strtolower((string) ($requestModel->preferred_provider ?: 'openai'));
                $currentAllowedProviders = in_array($preferredProvider, ByoAiAddon::providers(), true)
                    ? [$preferredProvider]
                    : ['openai'];
            }
        }

        if (in_array($status, [ByoAiAddon::REQUEST_STATUS_REJECTED, ByoAiAddon::REQUEST_STATUS_NOT_ELIGIBLE], true)) {
            $featureOverrides[PlanFeature::CHATBOT_BYO_AI] = false;
        }

        $meta['byo_ai'] = array_merge((array) ($meta['byo_ai'] ?? []), [
            'allowed_providers' => $currentAllowedProviders,
            'review_notes' => $reviewNotes ?: ($requestModel->review_notes ?: null),
            'updated_by_user_id' => auth()->id(),
            'updated_at' => now()->toIso8601String(),
            'synced_from_request_id' => $requestModel->id,
            'synced_from_request_status' => $status,
        ]);

        $subscription->forceFill([
            'feature_overrides' => $featureOverrides,
            'limit_overrides' => $limitOverrides,
            'meta' => $meta,
        ])->save();
    }

    /**
     * @return array<string, int>
     */
    private function defaultByoAddonLimits(TenantByoAiRequest $requestModel): array
    {
        $requestedAccounts = max(1, min(10, (int) ($requestModel->chatbot_account_count ?: 1)));

        return [
            PlanLimit::BYO_CHATBOT_ACCOUNTS => $requestedAccounts,
            PlanLimit::BYO_AI_REQUESTS_MONTHLY => max(1000, $requestedAccounts * 1000),
            PlanLimit::BYO_AI_TOKENS_MONTHLY => max(500000, $requestedAccounts * 500000),
        ];
    }

    /**
     * @return array{feature_overrides: array<string, mixed>, limit_overrides: array<string, mixed>}
     */
    private function preserveByoOverrides(?TenantSubscription $subscription): array
    {
        if (!$subscription) {
            return [
                'feature_overrides' => [],
                'limit_overrides' => [],
            ];
        }

        return ByoAiAddon::extractOverrideSubset(
            is_array($subscription->feature_overrides) ? $subscription->feature_overrides : [],
            is_array($subscription->limit_overrides) ? $subscription->limit_overrides : [],
        );
    }

    private function nextOrderNumber(): string
    {
        return 'PLAN-' . now()->format('YmdHis') . '-' . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    private function nextInvoiceNumber(): string
    {
        return 'INV-PLATFORM-' . now()->format('YmdHis') . '-' . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    private function orderTableReady(): bool
    {
        return Schema::hasTable('platform_plan_orders');
    }

    private function invoiceTableReady(): bool
    {
        return Schema::hasTable('platform_invoices');
    }

    private function paymentTableReady(): bool
    {
        return Schema::hasTable('platform_payments');
    }

    private function aiUsageTableReady(): bool
    {
        return Schema::hasTable('ai_usage_logs');
    }

    private function aiCreditTransactionsTableReady(): bool
    {
        return Schema::hasTable('ai_credit_transactions');
    }

    private function sendPlatformInvoiceIssuedMail(PlatformInvoice $invoice): void
    {
        $recipient = $this->billingRecipient($invoice->tenant, optional($invoice->order)->buyer_email);
        if (!$recipient) {
            return;
        }

        try {
            Mail::to($recipient)->queue(
                new PlatformInvoiceIssuedMail(
                    $invoice->fresh(['tenant', 'plan']),
                    $this->publicInvoiceUrl($invoice)
                )
            );
        } catch (\Throwable $e) {
            logger()->error('Platform invoice email failed', [
                'invoice_id' => $invoice->id,
                'recipient' => $recipient,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendPlatformPaymentReceivedMail(PlatformInvoice $invoice, PlatformPayment $payment): void
    {
        $recipient = $this->billingRecipient($invoice->tenant, optional($invoice->order)->buyer_email);
        if (!$recipient) {
            return;
        }

        try {
            Mail::to($recipient)->queue(
                new PlatformPaymentReceivedMail(
                    $invoice,
                    $payment,
                    $this->publicInvoiceUrl($invoice)
                )
            );
        } catch (\Throwable $e) {
            logger()->error('Platform payment email failed', [
                'invoice_id' => $invoice->id,
                'payment_id' => $payment->id,
                'recipient' => $recipient,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function billingRecipient(Tenant $tenant, ?string $preferred = null): ?string
    {
        if ($preferred) {
            return $preferred;
        }

        return optional($tenant->users()->orderBy('id')->first())->email;
    }

    private function publicInvoiceUrl(PlatformInvoice $invoice): string
    {
        return URL::temporarySignedRoute(
            'platform.invoices.public',
            now()->addDays(30),
            ['invoice' => $invoice->id]
        );
    }

    private function publicCheckoutUrl(PlatformInvoice $invoice): string
    {
        return URL::temporarySignedRoute(
            'platform.invoices.public.midtrans.checkout',
            now()->addDays(30),
            ['invoice' => $invoice->id]
        );
    }

    private function activateSubscriptionFromBilling(int $tenantId, int $planId, string $billingProvider, string $billingReference, $startsAt, $endsAt): TenantSubscription
    {
        $activeSubscription = TenantSubscription::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->latest('id')
            ->first();
        $byoOverrides = $this->preserveByoOverrides($activeSubscription);

        TenantSubscription::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->update([
                'status' => 'expired',
                'ends_at' => now(),
                'updated_at' => now(),
            ]);

        return TenantSubscription::create([
            'tenant_id' => $tenantId,
            'subscription_plan_id' => $planId,
            'status' => 'active',
            'billing_provider' => $billingProvider,
            'billing_reference' => $billingReference,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'auto_renews' => false,
            'feature_overrides' => $byoOverrides['feature_overrides'],
            'limit_overrides' => $byoOverrides['limit_overrides'],
            'meta' => [
                'assigned_from' => 'platform_billing',
                'assigned_by_user_id' => auth()->id(),
            ],
        ]);
    }
}
