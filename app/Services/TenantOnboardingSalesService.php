<?php

namespace App\Services;

use App\Mail\PlatformInvoiceIssuedMail;
use App\Mail\TenantWelcomeMail;
use App\Models\PlatformInvoice;
use App\Models\PlatformInvoiceItem;
use App\Models\PlatformPlanOrder;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Support\TenantRoleProvisioner;
use App\Support\WorkspaceContextProvisioner;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class TenantOnboardingSalesService
{
    private const DEFAULT_PUBLIC_PRODUCT_LINE = 'accounting';

    private const PUBLIC_PLAN_ALIASES = [
        'starter' => 'starter-v2',
        'growth' => 'growth-v2',
        'scale' => 'scale-v2',
        'starter-6m' => 'starter-6m-v2',
        'growth-6m' => 'growth-6m-v2',
        'scale-6m' => 'scale-6m-v2',
        'starter-yearly' => 'starter-yearly-v2',
        'growth-yearly' => 'growth-yearly-v2',
        'scale-yearly' => 'scale-yearly-v2',
    ];

    private const PLAN_CATALOG = [
        'starter' => [
            'price' => 149000,
            'currency' => 'IDR',
            'tagline' => 'Inbox sosial media dan percakapan tim untuk mulai jualan.',
            'description' => 'Cocok untuk tim yang baru membangun kanal support dan lead handling dari sosial media.',
            'highlights' => [
                'Conversation inbox tim',
                'Social media conversation',
                'Kontak dan histori percakapan dasar',
            ],
        ],
        'growth' => [
            'price' => 349000,
            'currency' => 'IDR',
            'tagline' => 'Omnichannel aktif dengan chatbot AI dan WhatsApp API.',
            'description' => 'Untuk tim sales dan support yang mulai butuh otomasi, balasan AI, dan integrasi WhatsApp Business API.',
            'highlights' => [
                'Semua fitur Starter',
                'Chatbot AI',
                'WhatsApp API',
                'Reporting dasar omnichannel',
            ],
        ],
        'scale' => [
            'price' => 799000,
            'currency' => 'IDR',
            'tagline' => 'Stack omnichannel penuh dengan WhatsApp Web dan kapasitas lebih besar.',
            'description' => 'Paket untuk tim operasional yang butuh kombinasi social inbox, chatbot AI, WhatsApp API, dan WhatsApp Web.',
            'highlights' => [
                'Semua fitur Growth',
                'WhatsApp Web',
                'Kapasitas user dan kontak lebih besar',
                'Advanced reports',
            ],
        ],
        'starter-v2' => [
            'price' => 149000,
            'currency' => 'IDR',
            'tagline' => 'Social inbox, live chat, dan CRM lite untuk tim kecil yang baru mulai omnichannel.',
            'description' => 'Cocok untuk UKM sales dan customer service yang ingin mulai dari social inbox, live chat website, dan pipeline follow-up tanpa biaya AI atau channel WhatsApp.',
            'highlights' => [
                'Shared inbox untuk percakapan tim',
                'CRM lite untuk follow-up lead',
                'Live chat website',
                'Social media inbox',
                'Belum termasuk AI dan WhatsApp',
            ],
            'audience' => 'Cocok untuk tim kecil yang baru mulai omnichannel',
            'recommended' => false,
        ],
        'growth-v2' => [
            'price' => 349000,
            'currency' => 'IDR',
            'tagline' => 'Paket rekomendasi untuk omnichannel aktif dengan AI, WhatsApp API, dan WhatsApp Web.',
            'description' => 'Untuk tim yang mulai serius mengelola lead dan support lintas channel, dengan AI, WhatsApp API, dan WhatsApp Web yang dihubungkan dari akun bisnis Anda sendiri.',
            'highlights' => [
                'Semua fitur Starter',
                'CRM lite untuk follow-up lead',
                'Chatbot AI dengan kuota bawaan',
                '500 AI Credits per bulan + top up tersedia',
                'WhatsApp API',
                'WhatsApp Web',
                'Limit channel tetap terukur',
            ],
            'audience' => 'Cocok untuk tim yang mulai scale follow-up dan automasi',
            'recommended' => true,
        ],
        'scale-v2' => [
            'price' => 799000,
            'currency' => 'IDR',
            'tagline' => 'Stack omnichannel lengkap untuk tim yang butuh kapasitas besar dan channel penuh.',
            'description' => 'Paket premium self-serve untuk operasional yang butuh social inbox, AI, WhatsApp API, dan WhatsApp Web dengan batas user, kontak, dan channel yang lebih tinggi.',
            'highlights' => [
                'Semua fitur Growth',
                'CRM lite untuk follow-up lead',
                '2.500 AI Credits per bulan + top up tersedia',
                'WhatsApp Web',
                'Kapasitas user, kontak, dan channel lebih besar',
                'Advanced reports',
            ],
            'audience' => 'Cocok untuk operasional multi-admin dengan channel lengkap',
            'recommended' => false,
        ],
    ];

    public function publicPlans(string $productLine = self::DEFAULT_PUBLIC_PRODUCT_LINE): Collection
    {
        return SubscriptionPlan::query()
            ->active()
            ->public()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->filter(fn (SubscriptionPlan $plan) => $this->isSellablePublicPlan($plan, $productLine))
            ->map(function (SubscriptionPlan $plan) {
                $plan->setAttribute('sales_meta', $this->salesMeta($plan));

                return $plan;
            })
            ->values();
    }

    public function resolvePublicPlanIdByCode(?string $code, string $productLine = self::DEFAULT_PUBLIC_PRODUCT_LINE): ?int
    {
        $normalized = strtolower(trim((string) $code));
        if ($normalized === '') {
            return null;
        }

        $resolvedCode = self::PUBLIC_PLAN_ALIASES[$normalized] ?? $normalized;

        $plan = SubscriptionPlan::query()
            ->where('code', $resolvedCode)
            ->active()
            ->public()
            ->first();

        return $this->isSellablePublicPlan($plan, $productLine) ? $plan?->id : null;
    }

    public function resolvePlanForNewSale(SubscriptionPlan $plan): SubscriptionPlan
    {
        $replacementCode = (string) ($plan->meta['replaced_by_code'] ?? '');

        if ($replacementCode === '') {
            return $plan;
        }

        return SubscriptionPlan::query()
            ->where('code', $replacementCode)
            ->active()
            ->first() ?? $plan;
    }

    public function createPendingWorkspace(array $data, SubscriptionPlan $plan, string $paymentChannel = 'midtrans'): array
    {
        return DB::transaction(function () use ($data, $plan, $paymentChannel): array {
            $salesMeta = $this->salesMeta($plan);
            $startsAt = now();
            $tenant = Tenant::query()->create([
                'name' => $data['company_name'],
                'slug' => $data['slug'],
                'is_active' => false,
                'meta' => [
                    'onboarding_status' => 'pending_payment',
                    'requested_plan_code' => $plan->code,
                ],
            ]);

            app(TenantRoleProvisioner::class)->ensureForTenant($tenant->id);

            $registrar = app(PermissionRegistrar::class);
            $registrar->setPermissionsTeamId($tenant->id);

            try {
                $user = User::query()->create([
                    'tenant_id' => $tenant->id,
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make($data['password']),
                ]);

                $superAdminRole = Role::query()
                    ->where('name', 'Super-admin')
                    ->where('tenant_id', $tenant->id)
                    ->where('guard_name', 'web')
                    ->firstOrFail();

                $user->assignRole($superAdminRole);
            } finally {
                $registrar->setPermissionsTeamId(null);
                $registrar->forgetCachedPermissions();
            }

            app(WorkspaceContextProvisioner::class)->ensureForTenant($tenant->id, $user);

            $order = PlatformPlanOrder::query()->create([
                'tenant_id' => $tenant->id,
                'subscription_plan_id' => $plan->id,
                'product_line' => $plan->productLine() ?: 'default',
                'order_number' => $this->nextOrderNumber(),
                'status' => 'pending',
                'amount' => (float) ($salesMeta['price'] ?? 0),
                'currency' => (string) ($salesMeta['currency'] ?? 'IDR'),
                'billing_period' => $plan->billing_interval ?: 'monthly',
                'buyer_email' => $data['email'],
                'payment_channel' => $paymentChannel,
                'starts_at' => $startsAt,
                'ends_at' => $this->resolveEndsAt($plan->billing_interval, $startsAt),
                'meta' => [
                    'created_from' => 'self_serve_onboarding',
                    'admin_name' => $data['name'],
                    'admin_email' => $data['email'],
                    'tenant_slug' => $tenant->slug,
                    'plan_code' => $plan->code,
                    'selected_payment_method' => $paymentChannel,
                    'sales_meta_snapshot' => $salesMeta,
                ],
            ]);

            $invoice = PlatformInvoice::query()->create([
                'tenant_id' => $tenant->id,
                'platform_plan_order_id' => $order->id,
                'subscription_plan_id' => $plan->id,
                'product_line' => $order->product_line,
                'invoice_number' => $this->nextInvoiceNumber(),
                'status' => 'issued',
                'amount' => $order->amount,
                'currency' => $order->currency,
                'issued_at' => now(),
                'due_at' => now()->addDays(1),
                'meta' => [
                    'source_order_id' => $order->id,
                    'created_from' => 'self_serve_onboarding',
                    'selected_payment_method' => $paymentChannel,
                ],
            ]);

            PlatformInvoiceItem::query()->create([
                'platform_invoice_id' => $invoice->id,
                'item_type' => 'plan',
                'item_code' => $plan->code,
                'name' => $plan->display_name,
                'description' => (string) ($salesMeta['tagline'] ?? $salesMeta['description'] ?? $plan->display_name),
                'quantity' => 1,
                'unit_price' => $order->amount,
                'total_price' => $order->amount,
                'meta' => [
                    'subscription_plan_id' => $plan->id,
                    'billing_interval' => $plan->billing_interval,
                    'source' => 'self_serve_onboarding',
                ],
            ]);

            $invoice->syncAmountFromItems();

            return compact('tenant', 'user', 'order', 'invoice');
        });
    }

    public function createTrialWorkspace(array $data, SubscriptionPlan $plan, int $trialDays = 14): array
    {
        return DB::transaction(function () use ($data, $plan, $trialDays): array {
            $startsAt = now();
            $trialEndsAt = $startsAt->copy()->addDays(max($trialDays, 1));

            $tenant = Tenant::query()->create([
                'name' => $data['company_name'],
                'slug' => $data['slug'],
                'is_active' => true,
                'meta' => [
                    'onboarding_status' => 'trialing',
                    'requested_plan_code' => $plan->code,
                    'trial_started_at' => $startsAt->toIso8601String(),
                    'trial_ends_at' => $trialEndsAt->toIso8601String(),
                ],
            ]);

            app(TenantRoleProvisioner::class)->ensureForTenant($tenant->id);

            $registrar = app(PermissionRegistrar::class);
            $registrar->setPermissionsTeamId($tenant->id);

            try {
                $user = User::query()->create([
                    'tenant_id' => $tenant->id,
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make($data['password']),
                ]);

                $superAdminRole = Role::query()
                    ->where('name', 'Super-admin')
                    ->where('tenant_id', $tenant->id)
                    ->where('guard_name', 'web')
                    ->firstOrFail();

                $user->assignRole($superAdminRole);
            } finally {
                $registrar->setPermissionsTeamId(null);
                $registrar->forgetCachedPermissions();
            }

            app(WorkspaceContextProvisioner::class)->ensureForTenant($tenant->id, $user);

            $subscription = TenantSubscription::query()->create([
                'tenant_id' => $tenant->id,
                'subscription_plan_id' => $plan->id,
                'product_line' => $plan->productLine() ?: 'default',
                'status' => 'trialing',
                'billing_provider' => 'trial',
                'billing_reference' => 'trial-' . strtolower($plan->code) . '-' . str_pad((string) $tenant->id, 6, '0', STR_PAD_LEFT),
                'starts_at' => $startsAt,
                'ends_at' => $trialEndsAt,
                'trial_ends_at' => $trialEndsAt,
                'auto_renews' => false,
                'feature_overrides' => null,
                'limit_overrides' => null,
                'meta' => [
                    'created_from' => 'self_serve_trial',
                    'plan_code' => $plan->code,
                    'trial_days' => $trialDays,
                ],
            ]);

            return compact('tenant', 'user', 'subscription');
        });
    }

    public function queueInvoiceMail(PlatformInvoice $invoice): void
    {
        $invoice->loadMissing(['tenant', 'plan', 'order']);

        $recipient = optional($invoice->order)->buyer_email ?: optional($invoice->tenant?->users()->orderBy('id')->first())->email;
        if (!$recipient) {
            return;
        }

        try {
            Mail::to($recipient)->queue(
                new PlatformInvoiceIssuedMail($invoice, $this->publicInvoiceUrl($invoice))
            );
        } catch (\Throwable $e) {
            Log::error('Onboarding invoice email failed', [
                'invoice_id' => $invoice->id,
                'recipient' => $recipient,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function completePaidOnboarding(PlatformPlanOrder $order, ?Carbon $paidAt = null): ?array
    {
        $order->loadMissing('tenant');

        $orderMeta = (array) ($order->meta ?? []);
        if (($orderMeta['created_from'] ?? null) !== 'self_serve_onboarding') {
            return null;
        }

        $tenant = $order->tenant;
        if (!$tenant) {
            return null;
        }

        $timestamp = $paidAt ?: now();
        $tenantMeta = (array) ($tenant->meta ?? []);
        $wasActive = $tenant->is_active;
        $tenantMeta['onboarding_status'] = 'active';
        $tenantMeta['onboarding_activated_at'] = $timestamp->toIso8601String();

        if (!$wasActive || ($tenantMeta['onboarding_status'] ?? null) !== 'active') {
            $tenant->forceFill([
                'is_active' => true,
                'meta' => $tenantMeta,
            ])->save();
        } else {
            $tenant->forceFill(['meta' => $tenantMeta])->save();
        }

        if (!empty($orderMeta['welcome_email_queued_at'])) {
            return null;
        }

        $orderMeta['welcome_email_queued_at'] = $timestamp->toIso8601String();
        $order->forceFill(['meta' => $orderMeta])->save();

        $adminEmail = $orderMeta['admin_email'] ?? optional($tenant->users()->orderBy('id')->first())->email;
        if (!$adminEmail) {
            return null;
        }

        return [
            'admin_name' => (string) ($orderMeta['admin_name'] ?? optional($tenant->users()->orderBy('id')->first())->name ?? 'Owner'),
            'admin_email' => (string) $adminEmail,
            'tenant_name' => $tenant->name,
            'tenant_slug' => $tenant->slug,
            'login_url' => $this->tenantLoginUrl($tenant),
        ];
    }

    public function queueWelcomeMail(array $payload): void
    {
        try {
            Mail::to($payload['admin_email'])->queue(
                new TenantWelcomeMail(
                    adminName: $payload['admin_name'],
                    adminEmail: $payload['admin_email'],
                    tenantName: $payload['tenant_name'],
                    tenantSlug: $payload['tenant_slug'],
                    loginUrl: $payload['login_url'],
                )
            );
        } catch (\Throwable $e) {
            Log::error('Onboarding welcome email failed', [
                'email' => $payload['admin_email'] ?? null,
                'tenant_slug' => $payload['tenant_slug'] ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function publicInvoiceUrl(PlatformInvoice $invoice): string
    {
        return URL::temporarySignedRoute(
            'platform.invoices.public',
            now()->addDays(30),
            ['invoice' => $invoice->id]
        );
    }

    public function salesMeta(SubscriptionPlan $plan): array
    {
        $catalogKey = self::PUBLIC_PLAN_ALIASES[$plan->code] ?? $plan->code;

        $defaults = self::PLAN_CATALOG[$catalogKey] ?? self::PLAN_CATALOG[$plan->code] ?? [
            'price' => 0,
            'currency' => 'IDR',
            'tagline' => $plan->name,
            'description' => null,
            'highlights' => [],
        ];

        $meta = array_merge($defaults, (array) ($plan->meta ?? []));
        $meta['price'] = (float) ($meta['price'] ?? 0);
        $meta['currency'] = (string) ($meta['currency'] ?? 'IDR');
        $meta['highlights'] = array_values(array_filter((array) ($meta['highlights'] ?? [])));
        $meta['product_line'] = $plan->productLine();
        $meta['product_line_label'] = $plan->productLineLabel();
        $meta['display_name'] = $plan->display_name;

        return $meta;
    }

    public function tenantLoginUrl(Tenant $tenant): string
    {
        $appUrl = (string) config('app.url');
        $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?: 'https';

        return $scheme . '://' . $tenant->slug . '.' . config('multitenancy.saas_domain') . '/login?registered=1';
    }

    private function resolveEndsAt(?string $billingInterval, Carbon $startsAt): ?Carbon
    {
        return match ($billingInterval) {
            'semiannual', 'biannual', 'half_yearly', '6_months', '6-months' => $startsAt->copy()->addMonths(6),
            'yearly' => $startsAt->copy()->addYear(),
            'monthly' => $startsAt->copy()->addMonth(),
            default => null,
        };
    }

    private function isSellablePublicPlan(?SubscriptionPlan $plan, string $productLine = self::DEFAULT_PUBLIC_PRODUCT_LINE): bool
    {
        if (!$plan) {
            return false;
        }

        return $plan->productLine() === $productLine;
    }

    private function nextOrderNumber(): string
    {
        return 'PLAN-' . now()->format('YmdHis') . '-' . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    private function nextInvoiceNumber(): string
    {
        return 'INV-PLATFORM-' . now()->format('YmdHis') . '-' . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }
}
