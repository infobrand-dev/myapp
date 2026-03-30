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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class PlatformAffiliateService
{
    private const SESSION_KEY = 'platform_affiliate_referral_code';

    public function tablesReady(): bool
    {
        return Schema::hasTable('platform_affiliates') && Schema::hasTable('platform_affiliate_referrals');
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

        if ($request->hasSession()) {
            $request->session()->put(self::SESSION_KEY, $affiliate->referral_code);
        }

        return $affiliate;
    }

    public function currentAffiliate(Request $request): ?PlatformAffiliate
    {
        if (!$this->tablesReady() || !$request->hasSession()) {
            return null;
        }

        $code = $this->normalizeCode((string) $request->session()->get(self::SESSION_KEY, ''));
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
            'referral_code' => $this->generateReferralCode($data['name']),
            'status' => $data['status'] ?? 'active',
            'commission_type' => $data['commission_type'] ?? 'percentage',
            'commission_rate' => (float) ($data['commission_rate'] ?? 10),
            'notes' => $data['notes'] ?: null,
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
        $commissionAmount = $this->commissionAmount($referral->affiliate, (float) $order->amount);
        $meta = (array) ($referral->meta ?? []);
        $saleMailAlreadyQueued = !empty($meta['sale_email_queued_at']);

        $referral->forceFill([
            'status' => 'converted',
            'order_amount' => $order->amount,
            'order_currency' => $order->currency,
            'commission_amount' => $commissionAmount,
            'converted_at' => $referral->converted_at ?: $timestamp,
            'meta' => $saleMailAlreadyQueued ? $meta : array_merge($meta, [
                'sale_email_queued_at' => $timestamp->toIso8601String(),
                'plan_code' => optional($order->plan)->code,
                'tenant_slug' => optional($order->tenant)->slug,
            ]),
        ])->save();

        $referral->affiliate->forceFill([
            'last_sale_at' => $timestamp,
        ])->save();

        if (!$saleMailAlreadyQueued) {
            $this->sendSaleGeneratedMail($referral->fresh(['affiliate', 'tenant', 'order.plan']));
        }

        return $referral;
    }

    public function referralLink(PlatformAffiliate $affiliate): string
    {
        return rtrim((string) config('app.url'), '/') . '/?ref=' . $affiliate->referral_code;
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
                    planName: optional(optional($referral->order)->plan)->name ?? 'Plan SaaS',
                    orderAmount: (float) $referral->order_amount,
                    orderCurrency: $referral->order_currency ?: 'IDR',
                    commissionAmount: (float) ($referral->commission_amount ?? 0),
                    referralLink: $this->referralLink($referral->affiliate),
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
}
