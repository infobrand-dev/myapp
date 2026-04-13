<?php

namespace App\Http\Controllers;

use App\Mail\PlatformInvoiceIssuedMail;
use App\Mail\PlatformPaymentReceivedMail;
use App\Models\AiCreditTransaction;
use App\Models\AiUsageLog;
use App\Models\PlatformInvoice;
use App\Models\PlatformPlanOrder;
use App\Models\PlatformPayment;
use App\Models\PlatformPromoCode;
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
use App\Services\PlatformPromoCodeService;
use App\Services\TenantOnboardingSalesService;
use App\Support\ByoAiAddon;
use App\Support\PlanFeature;
use App\Support\PlanLimit;
use App\Support\PlanProductLineMap;
use App\Support\TenantPlanManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
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

        $promoCodes = $this->promoTableReady()
            ? PlatformPromoCode::query()
                ->orderByDesc('is_active')
                ->orderBy('expires_at')
                ->orderBy('code')
                ->limit(6)
                ->get()
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
            'promoCodes' => $promoCodes,
            'promoReady' => $this->promoTableReady(),
        ]);
    }

    public function promos(): View
    {
        $promoCodes = $this->promoTableReady()
            ? PlatformPromoCode::query()
                ->orderByDesc('is_active')
                ->orderBy('expires_at')
                ->orderBy('code')
                ->get()
            : collect();

        return view('platform.promos.index', [
            'promoCodes' => $promoCodes,
            'promoReady' => $this->promoTableReady(),
        ]);
    }

    public function storePromo(Request $request): RedirectResponse
    {
        if (!$this->promoTableReady()) {
            return back()->with('error', 'Table promo platform belum tersedia. Jalankan migration terlebih dahulu.');
        }

        $data = $request->validate([
            'code' => ['required', 'string', 'max:64', 'alpha_dash', Rule::unique('platform_promo_codes', 'code')],
            'label' => ['required', 'string', 'max:255'],
            'discount_percent' => ['required', 'integer', 'min:1', 'max:100'],
            'product_lines' => ['nullable', 'array'],
            'product_lines.*' => ['string', Rule::in(['accounting', 'omnichannel'])],
            'expires_at' => ['nullable', 'date'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        PlatformPromoCode::query()->create([
            'code' => strtoupper(trim((string) $data['code'])),
            'label' => $data['label'],
            'discount_percent' => (int) $data['discount_percent'],
            'applicable_product_lines' => empty($data['product_lines']) ? null : array_values($data['product_lines']),
            'is_active' => (bool) ($data['is_active'] ?? false),
            'expires_at' => $this->nullableCarbon($data['expires_at'] ?? null),
            'max_uses' => $this->nullableLimit($data['max_uses'] ?? null),
            'used_count' => 0,
        ]);

        return back()->with('status', 'Promo code platform berhasil dibuat.');
    }

    public function updatePromo(Request $request, PlatformPromoCode $promo): RedirectResponse
    {
        if (!$this->promoTableReady()) {
            return back()->with('error', 'Table promo platform belum tersedia. Jalankan migration terlebih dahulu.');
        }

        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'discount_percent' => ['required', 'integer', 'min:1', 'max:100'],
            'product_lines' => ['nullable', 'array'],
            'product_lines.*' => ['string', Rule::in(['accounting', 'omnichannel'])],
            'expires_at' => ['nullable', 'date'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $promo->forceFill([
            'label' => $data['label'],
            'discount_percent' => (int) $data['discount_percent'],
            'applicable_product_lines' => empty($data['product_lines']) ? null : array_values($data['product_lines']),
            'is_active' => (bool) ($data['is_active'] ?? false),
            'expires_at' => $this->nullableCarbon($data['expires_at'] ?? null),
            'max_uses' => $this->nullableLimit($data['max_uses'] ?? null),
        ])->save();

        return back()->with('status', "Promo code {$promo->code} berhasil diperbarui.");
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

        $activePlans = $tenant->subscriptions
            ->filter(fn (TenantSubscription $subscription) => $subscription->status === 'active')
            ->filter(function (TenantSubscription $subscription): bool {
                if ($subscription->starts_at && $subscription->starts_at->isFuture()) {
                    return false;
                }

                return !$subscription->ends_at || $subscription->ends_at->isFuture();
            })
            ->sortByDesc(fn (TenantSubscription $subscription) => optional($subscription->starts_at)->timestamp ?? 0)
            ->groupBy(fn (TenantSubscription $subscription) => $subscription->productLine())
            ->map(fn ($group) => $group->first())
            ->sortKeys();

        $plans = SubscriptionPlan::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $plansByProductLine = $plans
            ->groupBy(fn (SubscriptionPlan $plan) => $plan->productLineLabel() ?: 'Default')
            ->sortKeys();

        $orderPosAddonDefaults = $plans
            ->mapWithKeys(fn (SubscriptionPlan $plan) => [
                $plan->id => [
                    'product_line' => $plan->productLine(),
                    'price' => $this->defaultPointOfSaleAddonPriceForPlan($plan),
                ],
            ]);

        return view('platform.tenants.show', [
            'tenant' => $tenant,
            'plans' => $plans,
            'plansByProductLine' => $plansByProductLine,
            'activePlans' => $activePlans,
            'orderPosAddonDefaults' => $orderPosAddonDefaults,
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
            'point_of_sale_addon_price' => ['nullable', 'numeric', 'min:0'],
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

        if (($data['product_line'] ?: null) === 'accounting') {
            data_set(
                $meta,
                'addons.point_of_sale.price',
                round((float) ($data['point_of_sale_addon_price'] ?? 0), 2)
            );
            data_set($meta, 'addons.point_of_sale.currency', (string) ($meta['currency'] ?? 'IDR'));
        } else {
            data_forget($meta, 'addons.point_of_sale');
        }

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
            'point_of_sale_addon' => ['nullable', 'boolean'],
        ]);

        $selectedPlan = SubscriptionPlan::query()->findOrFail((int) $data['subscription_plan_id']);
        $productLine = $this->resolvedProductLine($selectedPlan);

        DB::transaction(function () use ($tenant, $data, $selectedPlan, $productLine): void {
            $activeSubscription = $this->activeSubscriptionForProductLine($tenant->id, $productLine);
            $addonOverrides = $this->preserveAddonOverrides($activeSubscription);

            if ($productLine === 'accounting') {
                $addonOverrides['feature_overrides'][PlanFeature::POINT_OF_SALE] = (bool) ($data['point_of_sale_addon'] ?? false);
            } else {
                unset($addonOverrides['feature_overrides'][PlanFeature::POINT_OF_SALE]);
            }

            $this->expireActiveSubscriptionsForProductLine($tenant->id, $productLine);

            TenantSubscription::create([
                'tenant_id' => $tenant->id,
                'subscription_plan_id' => $selectedPlan->id,
                'product_line' => $productLine,
                'status' => $data['status'],
                'billing_provider' => $data['billing_provider'] ?: 'manual',
                'billing_reference' => $data['billing_reference'] ?: ('manual-' . $tenant->id . '-' . now()->timestamp),
                'starts_at' => $this->nullableCarbon($data['starts_at'] ?? null) ?? now(),
                'ends_at' => $this->nullableCarbon($data['ends_at'] ?? null),
                'trial_ends_at' => $this->nullableCarbon($data['trial_ends_at'] ?? null),
                'auto_renews' => $this->databaseBoolean(!empty($data['auto_renews'])),
                'feature_overrides' => $addonOverrides['feature_overrides'],
                'limit_overrides' => $addonOverrides['limit_overrides'],
                'meta' => [
                    'assigned_from' => 'platform_owner',
                    'assigned_by_user_id' => auth()->id(),
                    'product_line_label' => $selectedPlan->productLineLabel(),
                    'point_of_sale_addon' => $productLine === 'accounting' ? (bool) ($data['point_of_sale_addon'] ?? false) : null,
                ],
            ]);
        });

        return back()->with('status', 'Plan tenant berhasil diperbarui.');
    }

    public function cancelActivePlan(Tenant $tenant, TenantSubscription $subscription): RedirectResponse
    {
        abort_unless($subscription->tenant_id === $tenant->id, 404);

        $isCurrentSubscription = in_array($subscription->status, ['active', 'trialing'], true)
            && (!$subscription->starts_at || $subscription->starts_at->lte(now()))
            && (!$subscription->ends_at || $subscription->ends_at->isFuture());

        if (!$isCurrentSubscription) {
            return back()->with('error', 'Subscription tersebut sudah tidak aktif, jadi tidak perlu dicancel lagi.');
        }

        $meta = is_array($subscription->meta) ? $subscription->meta : [];
        $meta['cancelled_from'] = 'platform_owner';
        $meta['cancelled_by_user_id'] = auth()->id();
        $meta['cancelled_at'] = now()->toIso8601String();

        $subscription->forceFill([
            'status' => 'cancelled',
            'ends_at' => now(),
            'auto_renews' => false,
            'meta' => $meta,
        ])->save();

        return back()->with('status', 'Plan aktif tenant berhasil dicancel.');
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
            'point_of_sale_addon' => ['nullable', 'boolean'],
            'point_of_sale_addon_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $selectedPlan = SubscriptionPlan::query()->findOrFail((int) $data['subscription_plan_id']);
        $sellablePlan = app(TenantOnboardingSalesService::class)->resolvePlanForNewSale($selectedPlan);
        $productLine = $this->resolvedProductLine($sellablePlan);
        $posAddon = $this->pointOfSaleAddonPayload($sellablePlan, $data);

        if ($posAddon['enabled'] && $posAddon['price'] > (float) $data['amount']) {
            return back()
                ->withInput()
                ->withErrors(['point_of_sale_addon_price' => 'Harga POS Add-on tidak boleh melebihi total order.']);
        }

        PlatformPlanOrder::create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $sellablePlan->id,
            'product_line' => $productLine,
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
                'product_line' => $productLine,
                'addons' => [
                    'point_of_sale' => $posAddon,
                ],
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
            $productLine = $order->product_line ?: $this->resolvedProductLine($order->plan);
            $activeSubscription = $this->activeSubscriptionForProductLine($order->tenant_id, $productLine);
            $addonOverrides = $this->addonOverridesForProductLine(
                $productLine,
                $activeSubscription,
                is_array($order->meta) ? $order->meta : []
            );

            $this->expireActiveSubscriptionsForProductLine($order->tenant_id, $productLine);

            $subscription = TenantSubscription::create([
                'tenant_id' => $order->tenant_id,
                'subscription_plan_id' => $order->subscription_plan_id,
                'product_line' => $productLine,
                'status' => 'active',
                'billing_provider' => $order->payment_channel ?: 'manual',
                'billing_reference' => $order->order_number,
                'starts_at' => $order->starts_at ?: now(),
                'ends_at' => $order->ends_at,
                'auto_renews' => $this->databaseBoolean(false),
                'feature_overrides' => $addonOverrides['feature_overrides'],
                'limit_overrides' => $addonOverrides['limit_overrides'],
                'meta' => [
                    'source_order_id' => $order->id,
                    'assigned_from' => 'platform_owner_order',
                    'assigned_by_user_id' => auth()->id(),
                    'product_line_label' => optional($order->plan)->productLineLabel(),
                    'point_of_sale_addon' => (bool) ($addonOverrides['feature_overrides'][PlanFeature::POINT_OF_SALE] ?? false),
                ],
            ]);

            $order->forceFill([
                'status' => 'paid',
                'paid_at' => now(),
                'tenant_subscription_id' => $subscription->id,
            ])->save();

            app(PlatformPromoCodeService::class)->markOrderPaid($order);

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
            'product_line' => $order->product_line ?: $this->resolvedProductLine($order->plan),
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

        $orderMeta = is_array($order->meta) ? $order->meta : [];
        $posAddon = $this->extractPointOfSaleAddonFromMeta($orderMeta);
        $addonPrice = $posAddon['enabled'] ? round((float) $posAddon['price'], 2) : 0.0;
        $planAmount = round(max((float) $order->amount - $addonPrice, 0), 2);

        $invoice->items()->create([
            'item_type' => 'plan',
            'item_code' => optional($order->plan)->code,
            'name' => optional($order->plan)->display_name ?: optional($order->plan)->name ?: 'Subscription Plan',
            'description' => 'Tagihan langganan plan untuk workspace ' . optional($order->tenant)->name,
            'quantity' => 1,
            'unit_price' => $planAmount,
            'total_price' => $planAmount,
            'meta' => [
                'subscription_plan_id' => $order->subscription_plan_id,
                'source_order_id' => $order->id,
                'source' => 'platform_owner',
            ],
        ]);

        if ($posAddon['enabled'] && $addonPrice > 0) {
            $invoice->items()->create([
                'item_type' => 'addon',
                'item_code' => PlanFeature::POINT_OF_SALE,
                'name' => 'POS Add-on',
                'description' => 'Aktivasi add-on Point of Sale untuk workspace ' . optional($order->tenant)->name,
                'quantity' => 1,
                'unit_price' => $addonPrice,
                'total_price' => $addonPrice,
                'meta' => [
                    'feature' => PlanFeature::POINT_OF_SALE,
                    'source_order_id' => $order->id,
                    'source' => 'platform_owner',
                ],
            ]);
        }

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
                    $order->ends_at,
                    is_array($order->meta) ? $order->meta : []
                );

                $order->forceFill([
                    'status' => 'paid',
                    'paid_at' => $invoice->paid_at,
                    'tenant_subscription_id' => $subscription->id,
                ])->save();

                app(PlatformPromoCodeService::class)->markOrderPaid($order);

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

        app(PlatformPromoCodeService::class)->releaseOrderUsage($order);

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
                        'auto_renews' => $this->databaseBoolean(false),
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

            app(PlatformPromoCodeService::class)->releaseOrderUsage($order);

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
            PlanFeature::ACCOUNTING => 'Accounting suite',
            PlanFeature::COMMERCE => 'Accounting suite (legacy)',
            PlanFeature::PROJECT_MANAGEMENT => 'Project management',
            PlanFeature::LIVE_CHAT => 'Live chat widget',
            PlanFeature::SOCIAL_MEDIA => 'Social media conversations',
            PlanFeature::CHATBOT_AI => 'Chatbot AI',
            PlanFeature::CHATBOT_BYO_AI => 'Chatbot BYO AI add-on',
            PlanFeature::WHATSAPP_API => 'WhatsApp API',
            PlanFeature::WHATSAPP_WEB => 'WhatsApp Web',
            PlanFeature::POINT_OF_SALE => 'Point of Sale add-on',
            'multi_branch' => 'Multi branch',
            PlanFeature::EMAIL_MARKETING => 'Email marketing',
            PlanFeature::PURCHASES => 'Purchases',
            PlanFeature::INVENTORY => 'Inventory',
            PlanFeature::ADVANCED_REPORTS => 'Full reports',
            'finance' => 'Finance',
        ];
    }

    private function productLineOptions(): array
    {
        return [
            'omnichannel' => 'Omnichannel',
            'crm' => 'CRM',
            'accounting' => 'Accounting',
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
                    PlanFeature::ACCOUNTING => false,
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
                    PlanFeature::ACCOUNTING => false,
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
                    PlanFeature::ACCOUNTING => false,
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
                    PlanFeature::ACCOUNTING => false,
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
            'accounting_starter' => [
                'label' => 'Accounting Starter',
                'description' => 'Paket simple untuk UMKM dengan sales, payments, finance ringan, products, contacts, dan dashboard report ringkas. POS disiapkan sebagai add-on.',
                'product_line' => 'accounting',
                'features' => [
                    PlanFeature::MULTI_COMPANY => false,
                    PlanFeature::CONVERSATIONS => false,
                    PlanFeature::CRM => false,
                    PlanFeature::ACCOUNTING => true,
                    PlanFeature::PROJECT_MANAGEMENT => false,
                    PlanFeature::LIVE_CHAT => false,
                    PlanFeature::SOCIAL_MEDIA => false,
                    PlanFeature::CHATBOT_AI => false,
                    PlanFeature::CHATBOT_BYO_AI => false,
                    PlanFeature::WHATSAPP_API => false,
                    PlanFeature::WHATSAPP_WEB => false,
                    PlanFeature::EMAIL_MARKETING => false,
                    PlanFeature::PURCHASES => false,
                    PlanFeature::INVENTORY => false,
                    PlanFeature::ADVANCED_REPORTS => false,
                    PlanFeature::POINT_OF_SALE => false,
                    'multi_branch' => false,
                    'finance' => true,
                ],
                'limits' => [
                    PlanLimit::COMPANIES => 1,
                    PlanLimit::BRANCHES => 1,
                    PlanLimit::USERS => 5,
                    PlanLimit::TOTAL_STORAGE_BYTES => 1073741824,
                    PlanLimit::PRODUCTS => 250,
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
                    PlanLimit::BYO_CHATBOT_ACCOUNTS => 0,
                    PlanLimit::BYO_AI_REQUESTS_MONTHLY => 0,
                    PlanLimit::BYO_AI_TOKENS_MONTHLY => 0,
                    PlanLimit::AUTOMATION_WORKFLOWS => 0,
                    PlanLimit::AUTOMATION_EXECUTIONS_MONTHLY => 0,
                ],
                'meta' => [
                    'addons' => [
                        'point_of_sale' => [
                            'price' => 99000,
                            'currency' => 'IDR',
                        ],
                    ],
                ],
            ],
            'accounting_growth' => [
                'label' => 'Accounting Growth',
                'description' => 'Paket lengkap untuk tim yang sudah aktif dengan purchases, inventory, full reports, dan kapasitas workspace yang lebih longgar.',
                'product_line' => 'accounting',
                'features' => [
                    PlanFeature::MULTI_COMPANY => true,
                    PlanFeature::CONVERSATIONS => false,
                    PlanFeature::CRM => false,
                    PlanFeature::ACCOUNTING => true,
                    PlanFeature::PROJECT_MANAGEMENT => false,
                    PlanFeature::LIVE_CHAT => false,
                    PlanFeature::SOCIAL_MEDIA => false,
                    PlanFeature::CHATBOT_AI => false,
                    PlanFeature::CHATBOT_BYO_AI => false,
                    PlanFeature::WHATSAPP_API => false,
                    PlanFeature::WHATSAPP_WEB => false,
                    PlanFeature::EMAIL_MARKETING => false,
                    PlanFeature::PURCHASES => true,
                    PlanFeature::INVENTORY => true,
                    PlanFeature::ADVANCED_REPORTS => true,
                    PlanFeature::POINT_OF_SALE => false,
                    'multi_branch' => true,
                    'finance' => true,
                ],
                'limits' => [
                    PlanLimit::COMPANIES => 1,
                    PlanLimit::BRANCHES => 3,
                    PlanLimit::USERS => 15,
                    PlanLimit::TOTAL_STORAGE_BYTES => 5368709120,
                    PlanLimit::PRODUCTS => 2000,
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
                    PlanLimit::BYO_CHATBOT_ACCOUNTS => 0,
                    PlanLimit::BYO_AI_REQUESTS_MONTHLY => 0,
                    PlanLimit::BYO_AI_TOKENS_MONTHLY => 0,
                    PlanLimit::AUTOMATION_WORKFLOWS => 0,
                    PlanLimit::AUTOMATION_EXECUTIONS_MONTHLY => 0,
                ],
                'meta' => [
                    'addons' => [
                        'point_of_sale' => [
                            'price' => 149000,
                            'currency' => 'IDR',
                        ],
                    ],
                ],
            ],
            'accounting_scale' => [
                'label' => 'Accounting Scale',
                'description' => 'Isi paket sama dengan Growth, dengan kapasitas besar untuk tim multi-user dan multi-branch yang lebih padat.',
                'product_line' => 'accounting',
                'features' => [
                    PlanFeature::MULTI_COMPANY => true,
                    PlanFeature::CONVERSATIONS => false,
                    PlanFeature::CRM => false,
                    PlanFeature::ACCOUNTING => true,
                    PlanFeature::PROJECT_MANAGEMENT => false,
                    PlanFeature::LIVE_CHAT => false,
                    PlanFeature::SOCIAL_MEDIA => false,
                    PlanFeature::CHATBOT_AI => false,
                    PlanFeature::CHATBOT_BYO_AI => false,
                    PlanFeature::WHATSAPP_API => false,
                    PlanFeature::WHATSAPP_WEB => false,
                    PlanFeature::EMAIL_MARKETING => false,
                    PlanFeature::PURCHASES => true,
                    PlanFeature::INVENTORY => true,
                    PlanFeature::ADVANCED_REPORTS => true,
                    PlanFeature::POINT_OF_SALE => false,
                    'multi_branch' => true,
                    'finance' => true,
                ],
                'limits' => [
                    PlanLimit::COMPANIES => 3,
                    PlanLimit::BRANCHES => 10,
                    PlanLimit::USERS => 50,
                    PlanLimit::TOTAL_STORAGE_BYTES => 21474836480,
                    PlanLimit::PRODUCTS => 10000,
                    PlanLimit::CONTACTS => 20000,
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
                    PlanLimit::BYO_CHATBOT_ACCOUNTS => 0,
                    PlanLimit::BYO_AI_REQUESTS_MONTHLY => 0,
                    PlanLimit::BYO_AI_TOKENS_MONTHLY => 0,
                    PlanLimit::AUTOMATION_WORKFLOWS => 0,
                    PlanLimit::AUTOMATION_EXECUTIONS_MONTHLY => 0,
                ],
                'meta' => [
                    'addons' => [
                        'point_of_sale' => [
                            'price' => 199000,
                            'currency' => 'IDR',
                        ],
                    ],
                ],
            ],
            'project_management' => [
                'label' => 'Project Management',
                'description' => 'Task, workflow, dan kolaborasi proyek tanpa membuka bundle accounting atau omnichannel.',
                'product_line' => 'project_management',
                'features' => [
                    PlanFeature::MULTI_COMPANY => true,
                    PlanFeature::CONVERSATIONS => false,
                    PlanFeature::CRM => false,
                    PlanFeature::ACCOUNTING => false,
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
    private function preserveAddonOverrides(?TenantSubscription $subscription): array
    {
        if (!$subscription) {
            return [
                'feature_overrides' => [],
                'limit_overrides' => [],
            ];
        }

        $featureOverrides = is_array($subscription->feature_overrides) ? $subscription->feature_overrides : [];
        $limitOverrides = is_array($subscription->limit_overrides) ? $subscription->limit_overrides : [];
        $subset = ByoAiAddon::extractOverrideSubset($featureOverrides, $limitOverrides);

        if (array_key_exists(PlanFeature::POINT_OF_SALE, $featureOverrides)) {
            $subset['feature_overrides'][PlanFeature::POINT_OF_SALE] = (bool) $featureOverrides[PlanFeature::POINT_OF_SALE];
        }

        return $subset;
    }

    private function addonOverridesForProductLine(string $productLine, ?TenantSubscription $subscription, array $sourceMeta = []): array
    {
        $overrides = $this->preserveAddonOverrides($subscription);

        if ($productLine === 'accounting') {
            $overrides['feature_overrides'][PlanFeature::POINT_OF_SALE] = $this->extractPointOfSaleAddonFromMeta($sourceMeta)['enabled'];
        } else {
            unset($overrides['feature_overrides'][PlanFeature::POINT_OF_SALE]);
        }

        return $overrides;
    }

    private function pointOfSaleAddonPayload(SubscriptionPlan $plan, array $data): array
    {
        $productLine = $this->resolvedProductLine($plan);

        if ($productLine !== 'accounting') {
            return [
                'enabled' => false,
                'price' => 0,
            ];
        }

        $enabled = (bool) ($data['point_of_sale_addon'] ?? false);
        $rawPrice = $data['point_of_sale_addon_price'] ?? null;
        $defaultPrice = $this->defaultPointOfSaleAddonPriceForPlan($plan);
        $price = $rawPrice === null || $rawPrice === ''
            ? $defaultPrice
            : round((float) $rawPrice, 2);

        return [
            'enabled' => $enabled,
            'price' => $enabled ? max($price, 0) : 0,
        ];
    }

    private function extractPointOfSaleAddonFromMeta(array $meta): array
    {
        return [
            'enabled' => (bool) data_get($meta, 'addons.point_of_sale.enabled', false),
            'price' => max((float) data_get($meta, 'addons.point_of_sale.price', 0), 0),
        ];
    }

    private function defaultPointOfSaleAddonPriceForPlan(SubscriptionPlan $plan): float
    {
        return round((float) data_get($plan->meta, 'addons.point_of_sale.price', 0), 2);
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

    private function promoTableReady(): bool
    {
        return Schema::hasTable('platform_promo_codes');
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

    private function activateSubscriptionFromBilling(int $tenantId, int $planId, string $billingProvider, string $billingReference, $startsAt, $endsAt, array $sourceMeta = []): TenantSubscription
    {
        $plan = SubscriptionPlan::query()->findOrFail($planId);
        $productLine = $this->resolvedProductLine($plan);
        $activeSubscription = $this->activeSubscriptionForProductLine($tenantId, $productLine);
        $sourceOrder = PlatformPlanOrder::query()
            ->where('order_number', $billingReference)
            ->first();
        $addonOverrides = $this->addonOverridesForProductLine(
            $productLine,
            $activeSubscription,
            !empty($sourceMeta) ? $sourceMeta : (is_array($sourceOrder?->meta) ? $sourceOrder->meta : [])
        );

        $this->expireActiveSubscriptionsForProductLine($tenantId, $productLine);

        return TenantSubscription::create([
            'tenant_id' => $tenantId,
            'subscription_plan_id' => $planId,
            'product_line' => $productLine,
            'status' => 'active',
            'billing_provider' => $billingProvider,
            'billing_reference' => $billingReference,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'auto_renews' => $this->databaseBoolean(false),
            'feature_overrides' => $addonOverrides['feature_overrides'],
            'limit_overrides' => $addonOverrides['limit_overrides'],
            'meta' => [
                'assigned_from' => 'platform_billing',
                'assigned_by_user_id' => auth()->id(),
                'product_line_label' => $plan->productLineLabel(),
                'point_of_sale_addon' => (bool) ($addonOverrides['feature_overrides'][PlanFeature::POINT_OF_SALE] ?? false),
            ],
        ]);
    }

    private function expireActiveSubscriptionsForProductLine(int $tenantId, string $productLine): void
    {
        $query = TenantSubscription::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active');

        if (Schema::hasColumn('tenant_subscriptions', 'product_line')) {
            $query->whereIn('product_line', PlanProductLineMap::productLineCandidates($productLine));
        }

        $query->update([
            'status' => 'expired',
            'ends_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function activeSubscriptionForProductLine(int $tenantId, string $productLine): ?TenantSubscription
    {
        $query = TenantSubscription::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active');

        if (Schema::hasColumn('tenant_subscriptions', 'product_line')) {
            $query->whereIn('product_line', PlanProductLineMap::productLineCandidates($productLine));
        }

        return $query
            ->latest('starts_at')
            ->latest('id')
            ->first();
    }

    private function resolvedProductLine(?SubscriptionPlan $plan): string
    {
        return $plan?->productLine() ?: 'default';
    }

    private function databaseBoolean(bool $value): bool|string
    {
        return DB::connection()->getDriverName() === 'pgsql'
            ? ($value ? 'true' : 'false')
            : $value;
    }
}
