<?php

namespace App\Modules\Affiliate\Services;

use App\Modules\Affiliate\Models\AffiliateListing;
use App\Modules\Affiliate\Models\AffiliateReferral;
use App\Modules\Payments\Models\Payment;
use App\Modules\Sales\Models\Sale;
use App\Support\Commerce\CommerceOrderLifecycleService;
use Illuminate\Support\Collection;

class TenantAffiliateConversionService
{
    public function __construct(
        private readonly CommerceOrderLifecycleService $commerceOrders,
    ) {
    }

    /**
     * @param  Collection<int, mixed>  $payables
     */
    public function handle(mixed $payment, Collection $payables): void
    {
        if (!$payment instanceof Payment) {
            return;
        }

        $payables
            ->filter(fn ($payable): bool => $payable instanceof Sale)
            ->each(function (Sale $sale): void {
                $sale = $sale->fresh();
                if (!$sale || !$this->commerceOrders->isCommerceOrder($sale)) {
                    return;
                }

                if (!in_array((string) $sale->payment_status, [Sale::PAYMENT_PAID, Sale::PAYMENT_OVERPAID], true)) {
                    return;
                }

                if (data_get($sale->meta, 'commerce.affiliate.converted_at')) {
                    return;
                }

                $code = trim((string) data_get($sale->meta, 'commerce.affiliate.code', ''));
                if ($code === '') {
                    return;
                }

                $listing = AffiliateListing::query()
                    ->where('source_tenant_id', (int) $sale->tenant_id)
                    ->where('share_code', $code)
                    ->where('status', 'active')
                    ->first();

                if (!$listing) {
                    return;
                }

                $gross = round((float) $sale->grand_total, 2);
                $commissionType = (string) $listing->commission_type;
                $commissionAmount = $commissionType === 'flat'
                    ? round((float) $listing->commission_rate, 2)
                    : round($gross * ((float) $listing->commission_rate / 100), 2);

                AffiliateReferral::query()->updateOrCreate(
                    [
                        'tenant_id' => (int) $sale->tenant_id,
                        'sale_id' => (int) $sale->id,
                    ],
                    [
                        'affiliate_partner_id' => null,
                        'affiliate_listing_id' => (int) $listing->id,
                        'affiliate_tenant_id' => (int) $listing->tenant_id,
                        'affiliate_user_id' => (int) $listing->user_id,
                        'source_product_id' => (int) $listing->source_product_id,
                        'referral_code' => $listing->share_code,
                        'landing_url' => (string) data_get($sale->meta, 'commerce.affiliate.landing_url', ''),
                        'channel' => (string) data_get($sale->meta, 'commerce.channel', 'affiliate_referral'),
                        'status' => 'converted',
                        'commission_type' => $commissionType,
                        'commission_amount' => $commissionAmount,
                        'order_gross' => $gross,
                        'meta' => [
                            'affiliate_tenant_id' => (int) $listing->tenant_id,
                            'sale_number' => $sale->sale_number,
                        ],
                        'captured_at' => now(),
                        'converted_at' => now(),
                    ]
                );

                $meta = is_array($sale->meta) ? $sale->meta : [];
                data_set($meta, 'commerce.channel', 'affiliate_referral');
                data_set($meta, 'commerce.affiliate.listing_id', $listing->id);
                data_set($meta, 'commerce.affiliate.affiliate_tenant_id', $listing->tenant_id);
                data_set($meta, 'commerce.affiliate.affiliate_user_id', $listing->user_id);
                data_set($meta, 'commerce.affiliate.commission_type', $commissionType);
                data_set($meta, 'commerce.affiliate.commission_amount', $commissionAmount);
                data_set($meta, 'commerce.affiliate.converted_at', now()->toIso8601String());
                data_set($meta, 'commerce.affiliate.status', 'converted');

                $sale->update(['meta' => $meta]);
            });
    }
}
