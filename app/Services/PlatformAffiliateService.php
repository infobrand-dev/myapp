<?php

namespace App\Services;

use App\Mail\PlatformAffiliateRegisteredMail;
use App\Mail\PlatformAffiliateSaleGeneratedMail;
use App\Models\PlatformAffiliate;
use App\Models\PlatformAffiliateReferral;
use App\Models\PlatformPlanOrder;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PlatformAffiliateService
{
    private const SESSION_KEY = 'platform_affiliate_referral_code';
    private const COOKIE_KEY = 'platform_affiliate_referral_code';

    public function tablesReady(): bool
    {
        return Schema::hasTable('platform_affiliates')
            && Schema::hasTable('platform_affiliate_referrals')
            && Schema::hasColumn('platform_affiliates', 'slug')
            && Schema::hasColumn('platform_affiliates', 'click_count')
            && Schema::hasColumn('platform_affiliates', 'last_clicked_at');
    }

    public function findActiveBySlug(string $slug): ?PlatformAffiliate
    {
        if (!$this->tablesReady()) {
            return null;
        }

        return PlatformAffiliate::query()
            ->where('slug', Str::slug($slug))
            ->where('status', 'active')
            ->first();
    }

    public function publicPolicy(): array
    {
        $commissionType = (string) config('services.platform_affiliate.default_commission_type', 'percentage');
        $commissionRate = (float) config('services.platform_affiliate.default_commission_rate', 20);
        $cookieDays = max(1, (int) config('services.platform_affiliate.cookie_days', 30));
        $firstPurchaseOnly = (bool) config('services.platform_affiliate.first_purchase_only', true);
        $payoutSchedule = (string) config('services.platform_affiliate.payout_schedule', 'monthly');
        $payoutDay = max(1, (int) config('services.platform_affiliate.payout_day', 10));
        $payoutMethods = array_values(config('services.platform_affiliate.payout_methods', ['bank_transfer']));

        return [
            'commission_type' => $commissionType,
            'commission_rate' => $commissionRate,
            'cookie_days' => $cookieDays,
            'first_purchase_only' => $firstPurchaseOnly,
            'payout_schedule' => $payoutSchedule,
            'payout_day' => $payoutDay,
            'payout_methods' => $payoutMethods,
            'terms_url' => route('affiliate.program'),
        ];
    }

    public function captureFromRequest(Request $request): ?PlatformAffiliate
    {
        if (!$this->tablesReady()) {
            return null;
        }

        $rawCode = $request->query('ref');
        if (!is_string($rawCode) || trim($rawCode) === '') {
            return $this->currentAffiliate($request);
        }

        $code = $this->normalizeCode($rawCode);
        if ($code === null) {
            return null;
        }

        $affiliate = PlatformAffiliate::query()
            ->where('referral_code', $code)
            ->where('status', 'active')
            ->first();

        if (!$affiliate) {
            return null;
        }

        $this->storeAttribution($request, $affiliate);
        $this->registerClick($affiliate, $request);

        return $affiliate;
    }

    public function captureAffiliate(Request $request, PlatformAffiliate $affiliate): ?PlatformAffiliate
    {
        if (!$this->tablesReady() || $affiliate->status !== 'active') {
            return null;
        }

        $this->storeAttribution($request, $affiliate);
        $this->registerClick($affiliate, $request);

        return $affiliate;
    }

    public function currentAffiliate(Request $request): ?PlatformAffiliate
    {
        if (!$this->tablesReady()) {
            return null;
        }

        $code = null;

        if ($request->hasSession()) {
            $code = $this->normalizeCode((string) $request->session()->get(self::SESSION_KEY, ''));
        }

        if ($code === null) {
            $code = $this->normalizeCode((string) $request->cookie(self::COOKIE_KEY, ''));
        }

        if ($code === null) {
            return null;
        }

        return PlatformAffiliate::query()
            ->where('referral_code', $code)
            ->where('status', 'active')
            ->first();
    }

    public function createAffiliate(array $data): PlatformAffiliate
    {
        $affiliate = PlatformAffiliate::query()->create([
            'name' => $data['name'],
            'email' => mb_strtolower($data['email']),
            'phone' => $data['phone'] ?: null,
            'slug' => $this->generateSlug($data['name']),
            'referral_code' => $this->generateReferralCode($data['name']),
            'status' => $data['status'] ?? 'active',
            'commission_type' => $data['commission_type'] ?? 'percentage',
            'commission_rate' => (float) ($data['commission_rate'] ?? 10),
            'notes' => $data['notes'] ?: null,
            'click_count' => 0,
            'meta' => [
                'created_by_user_id' => auth()->id(),
            ],
        ]);

        $this->sendRegisteredMail($affiliate);

        return $affiliate;
    }

    public function attachReferralToOrder(Request $request, PlatformPlanOrder $order, Tenant $tenant, ?SubscriptionPlan $plan = null): ?PlatformAffiliateReferral
    {
        $affiliate = $this->currentAffiliate($request);
        if (!$affiliate || !$this->tablesReady()) {
            return null;
        }

        $referral = PlatformAffiliateReferral::query()->updateOrCreate(
            ['platform_plan_order_id' => $order->id],
            [
                'platform_affiliate_id' => $affiliate->id,
                'tenant_id' => $tenant->id,
                'referral_code' => $affiliate->referral_code,
                'buyer_email' => $order->buyer_email,
                'landing_path' => '/' . ltrim((string) $request->path(), '/'),
                'status' => 'registered',
                'order_amount' => $order->amount,
                'order_currency' => $order->currency,
                'registered_at' => now(),
                'meta' => [
                    'tenant_slug' => $tenant->slug,
                    'plan_code' => $plan?->code ?? optional($order->plan)->code,
                    'created_from' => 'self_serve_onboarding',
                ],
            ]
        );

        $meta = (array) ($order->meta ?? []);
        $meta['affiliate'] = [
            'platform_affiliate_id' => $affiliate->id,
            'referral_code' => $affiliate->referral_code,
        ];
        $order->forceFill(['meta' => $meta])->save();

        return $referral;
    }

    public function finalizeSale(PlatformPlanOrder $order, ?Carbon $paidAt = null): ?PlatformAffiliateReferral
    {
        if (!$this->tablesReady()) {
            return null;
        }

        $order->loadMissing(['tenant', 'plan', 'affiliateReferral.affiliate']);

        $referral = $order->affiliateReferral;
        if (!$referral || !$referral->affiliate) {
            return null;
        }

        $timestamp = $paidAt ?: now();
        $commissionEligible = $this->isCommissionEligible($order, $referral);
        $commissionAmount = $commissionEligible
            ? $this->commissionAmount($referral->affiliate, (float) $order->amount)
            : 0.0;
        $meta = (array) ($referral->meta ?? []);
        $saleMailAlreadyQueued = !empty($meta['sale_email_queued_at']);
        $meta['commission_eligible'] = $commissionEligible;

        if (!$commissionEligible) {
            $meta['commission_reason'] = 'first_purchase_only';
        }

        $referral->forceFill([
            'status' => 'converted',
            'order_amount' => $order->amount,
            'order_currency' => $order->currency,
            'commission_amount' => $commissionAmount,
            'payout_status' => $commissionEligible ? 'pending' : 'not_eligible',
            'converted_at' => $referral->converted_at ?: $timestamp,
            'meta' => $saleMailAlreadyQueued ? $meta : array_merge($meta, [
                'plan_code' => optional($order->plan)->code,
                'tenant_slug' => optional($order->tenant)->slug,
                ...($commissionEligible ? [
                    'sale_email_queued_at' => $timestamp->toIso8601String(),
                ] : []),
            ]),
        ])->save();

        $referral->affiliate->forceFill([
            'last_sale_at' => $timestamp,
        ])->save();

        if ($commissionEligible && !$saleMailAlreadyQueued) {
            $this->sendSaleGeneratedMail($referral->fresh(['affiliate', 'tenant', 'order.plan']));
        }

        return $referral;
    }

    public function voidSale(PlatformPlanOrder $order, ?string $reason = null): ?PlatformAffiliateReferral
    {
        if (!$this->tablesReady()) {
            return null;
        }

        $order->loadMissing(['affiliateReferral.affiliate']);

        $referral = $order->affiliateReferral;
        if (!$referral) {
            return null;
        }

        $meta = (array) ($referral->meta ?? []);
        $meta['voided_at'] = now()->toIso8601String();
        $meta['voided_by_user_id'] = auth()->id();
        $meta['void_reason'] = $reason ?: 'platform_owner_void';

        $referral->forceFill([
            'status' => 'void',
            'commission_amount' => 0,
            'payout_status' => 'void',
            'converted_at' => null,
            'approved_at' => null,
            'paid_at' => null,
            'meta' => $meta,
        ])->save();

        return $referral;
    }

    public function referralLink(PlatformAffiliate $affiliate): string
    {
        return rtrim((string) config('app.url'), '/') . '/aff/' . $affiliate->slug;
    }

    private function commissionAmount(PlatformAffiliate $affiliate, float $orderAmount): float
    {
        if ($affiliate->commission_type === 'flat') {
            return (float) $affiliate->commission_rate;
        }

        return round($orderAmount * (((float) $affiliate->commission_rate) / 100), 2);
    }

    private function sendRegisteredMail(PlatformAffiliate $affiliate): void
    {
        try {
            Mail::to($affiliate->email)->queue(
                new PlatformAffiliateRegisteredMail(
                    affiliateName: $affiliate->name,
                    referralCode: $affiliate->referral_code,
                    referralLink: $this->referralLink($affiliate),
                    commissionType: $affiliate->commission_type,
                    commissionRate: (float) $affiliate->commission_rate,
                    policy: $this->publicPolicy(),
                )
            );

            $affiliate->forceFill(['welcome_emailed_at' => now()])->save();
        } catch (\Throwable $e) {
            Log::error('Platform affiliate registered email failed', [
                'affiliate_id' => $affiliate->id,
                'email' => $affiliate->email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendSaleGeneratedMail(PlatformAffiliateReferral $referral): void
    {
        try {
            Mail::to($referral->affiliate->email)->queue(
                new PlatformAffiliateSaleGeneratedMail(
                    affiliateName: $referral->affiliate->name,
                    tenantName: optional($referral->tenant)->name ?? 'Workspace baru',
                    orderNumber: optional($referral->order)->order_number ?? '-',
                    planName: optional(optional($referral->order)->plan)->display_name ?? optional(optional($referral->order)->plan)->name ?? 'Plan SaaS',
                    orderAmount: (float) $referral->order_amount,
                    orderCurrency: $referral->order_currency ?: 'IDR',
                    commissionAmount: (float) ($referral->commission_amount ?? 0),
                    referralLink: $this->referralLink($referral->affiliate),
                    policy: $this->publicPolicy(),
                )
            );
        } catch (\Throwable $e) {
            Log::error('Platform affiliate sale email failed', [
                'affiliate_referral_id' => $referral->id,
                'affiliate_id' => $referral->platform_affiliate_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function generateReferralCode(string $name): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name) ?: 'AFF', 0, 6));

        do {
            $code = $prefix . '-' . strtoupper(str_pad(base_convert((string) random_int(10000, 999999), 10, 36), 4, '0', STR_PAD_LEFT));
        } while (PlatformAffiliate::query()->where('referral_code', $code)->exists());

        return $code;
    }

    private function normalizeCode(string $rawCode): ?string
    {
        $code = strtoupper(trim($rawCode));

        return $code !== '' ? $code : null;
    }

    private function generateSlug(string $name): string
    {
        $baseSlug = Str::slug($name) ?: 'affiliate';
        $slug = $baseSlug;
        $counter = 2;

        while (PlatformAffiliate::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function storeAttribution(Request $request, PlatformAffiliate $affiliate): void
    {
        if ($request->hasSession()) {
            $request->session()->put(self::SESSION_KEY, $affiliate->referral_code);
        }

        Cookie::queue(cookie(
            self::COOKIE_KEY,
            $affiliate->referral_code,
            $this->cookieMinutes(),
            '/',
            config('session.domain'),
            (bool) config('session.secure'),
            false,
            false,
            config('session.same_site', 'lax')
        ));
    }

    private function registerClick(PlatformAffiliate $affiliate, Request $request): void
    {
        $meta = (array) ($affiliate->meta ?? []);
        $meta['last_click_path'] = '/' . ltrim((string) $request->path(), '/');
        $meta['last_click_query'] = $request->getQueryString();

        $affiliate->forceFill([
            'click_count' => (int) $affiliate->click_count + 1,
            'last_clicked_at' => now(),
            'meta' => $meta,
        ])->save();
    }

    private function cookieMinutes(): int
    {
        $days = max(1, (int) config('services.platform_affiliate.cookie_days', 30));

        return $days * 24 * 60;
    }

    private function isCommissionEligible(PlatformPlanOrder $order, PlatformAffiliateReferral $referral): bool
    {
        if (!config('services.platform_affiliate.first_purchase_only', true)) {
            return true;
        }

        if (!$order->tenant_id) {
            return true;
        }

        return !PlatformPlanOrder::query()
            ->where('tenant_id', $order->tenant_id)
            ->where('status', 'paid')
            ->whereKeyNot($order->id)
            ->exists();
    }
}
