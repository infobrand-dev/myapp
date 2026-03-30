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
use App\Models\TenantSubscription;
use App\Services\GoliveAuditService;
use App\Services\AiUsageService;
use App\Services\PlatformMidtransBillingService;
use App\Services\TenantOnboardingSalesService;
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
        PlanLimit::USERS => 'Users',
        PlanLimit::PRODUCTS => 'Products',
        PlanLimit::CONTACTS => 'Contacts',
        PlanLimit::WHATSAPP_INSTANCES => 'WhatsApp Instances',
        PlanLimit::EMAIL_CAMPAIGNS => 'Email Campaigns',
        PlanLimit::AI_CREDITS_MONTHLY => 'AI Credits / Month',
    ];

    public function dashboard(TenantPlanManager $planManager, AiUsageService $aiUsage): View
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
            ->groupBy(fn (Tenant $tenant) => optional(optional($tenant->activeSubscription)->plan)->name ?? 'No active plan')
            ->map(fn ($group, $label) => ['label' => $label, 'count' => $group->count()])
            ->sortByDesc('count')
            ->values();

        $tenantsAtRisk = $tenants
            ->map(function (Tenant $tenant) use ($planManager) {
                $usages = $this->limitUsageRows($planManager, $tenant->id);
                $warningCount = collect($usages)->filter(function (array $row) {
                    return $row['limit'] !== null && $row['limit'] > 0 && $row['usage'] >= $row['limit'];
                })->count();

                return [
                    'tenant' => $tenant,
                    'warning_count' => $warningCount,
                ];
            })
            ->filter(fn (array $row) => $row['warning_count'] > 0 || !$row['tenant']->is_active)
            ->sortByDesc('warning_count')
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
        ]);
    }

    public function tenants(): View
    {
        $tenants = Tenant::query()
            ->with(['activeSubscription.plan:id,name,code'])
            ->withCount(['users', 'companies', 'branches'])
            ->orderByDesc('created_at')
            ->get();

        return view('platform.tenants.index', [
            'tenants' => $tenants,
        ]);
    }

    public function tenant(Tenant $tenant, TenantPlanManager $planManager, AiUsageService $aiUsage): View
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
        ]);
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

    public function invoice(PlatformInvoice $invoice, PlatformMidtransBillingService $midtrans): View
    {
        $invoice->load(['tenant', 'plan', 'order', 'payments', 'items']);

        return view('platform.invoices.show', [
            'invoice' => $invoice,
            'publicInvoiceUrl' => $this->publicInvoiceUrl($invoice),
            'publicCheckoutUrl' => $this->publicCheckoutUrl($invoice),
            'midtransReady' => $midtrans->isConfigured(),
        ]);
    }

    public function publicInvoice(PlatformInvoice $invoice, PlatformMidtransBillingService $midtrans): View
    {
        $invoice->load(['tenant', 'plan', 'order', 'payments', 'items']);

        return view('platform.invoices.public', [
            'invoice' => $invoice,
            'publicCheckoutUrl' => $this->publicCheckoutUrl($invoice),
            'midtransReady' => $midtrans->isConfigured(),
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

        $plan->forceFill([
            'name' => $data['name'],
            'billing_interval' => $data['billing_interval'] ?: null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => (bool) ($data['is_active'] ?? false),
            'is_public' => (bool) ($data['is_public'] ?? false),
            'features' => $features,
            'limits' => $limits,
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
                'meta' => [
                    'assigned_from' => 'platform_owner',
                    'assigned_by_user_id' => auth()->id(),
                ],
            ]);
        });

        return back()->with('status', 'Plan tenant berhasil diperbarui.');
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

        PlatformPlanOrder::create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => (int) $data['subscription_plan_id'],
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
            ],
        ]);

        return back()->with('status', 'Order plan berhasil dibuat.');
    }

    public function markOrderPaid(PlatformPlanOrder $order, TenantOnboardingSalesService $onboardingSales): RedirectResponse
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
            'name' => optional($order->plan)->name ?: 'Subscription Plan',
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

    public function recordPayment(Request $request, PlatformInvoice $invoice, TenantOnboardingSalesService $onboardingSales): RedirectResponse
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

    private function limitUsageRows(TenantPlanManager $planManager, int $tenantId): array
    {
        $rows = [];

        foreach (self::LIMIT_LABELS as $key => $label) {
            $rows[] = [
                'key' => $key,
                'label' => $label,
                'limit' => $planManager->limit($key, $tenantId),
                'usage' => $planManager->usage($key, $tenantId),
            ];
        }

        return $rows;
    }

    private function featureLabels(): array
    {
        return [
            PlanFeature::MULTI_COMPANY => 'Multi company',
            PlanFeature::CONVERSATIONS => 'Conversations inbox',
            PlanFeature::LIVE_CHAT => 'Live chat widget',
            PlanFeature::SOCIAL_MEDIA => 'Social media conversations',
            PlanFeature::CHATBOT_AI => 'Chatbot AI',
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

    private function nullableCarbon(?string $value): ?Carbon
    {
        return $value ? Carbon::parse($value) : null;
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
            'meta' => [
                'assigned_from' => 'platform_billing',
                'assigned_by_user_id' => auth()->id(),
            ],
        ]);
    }
}
