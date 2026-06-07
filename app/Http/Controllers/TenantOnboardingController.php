<?php

namespace App\Http\Controllers;

use App\Models\PlatformPromoCode;
use App\Models\SubscriptionPlan;
use App\Services\PlatformAffiliateService;
use App\Services\PlatformManualPaymentService;
use App\Services\PlatformMidtransBillingService;
use App\Services\TenantSlugReservationService;
use App\Services\TenantOnboardingSalesService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class TenantOnboardingController extends Controller
{
    /**
     * Show the new-tenant registration form.
     * Only accessible in SaaS mode.
     */
    public function create(Request $request, TenantOnboardingSalesService $sales, PlatformAffiliateService $affiliateService, PlatformManualPaymentService $manualPayment, PlatformMidtransBillingService $midtrans)
    {
        abort_unless(config('multitenancy.mode') === 'saas', 404);

        $affiliate = $affiliateService->captureFromRequest($request);
        $requestedProductLine = $this->requestedPublicProductLineRaw($request);
        $selectionRequired = $requestedProductLine === null;
        $productLine = $requestedProductLine ?? 'accounting';
        $trialAvailable = $requestedProductLine !== null
            ? $this->trialAvailableForProductLine($productLine, $request)
            : false;
        $promoCode = strtoupper(trim((string) $request->query('promo_code', '')));
        $promoPreview = $promoCode !== ''
            ? PlatformPromoCode::findByCode($promoCode)
            : null;

        if (!$promoPreview || ($requestedProductLine !== null && !$promoPreview->isUsable($productLine))) {
            $promoPreview = null;
        }

        $preferredPlanId = $requestedProductLine !== null
            ? $sales->resolvePublicPlanIdByCode((string) request()->query('plan'), $productLine)
            : null;

        return view('onboarding.create', [
            'plans' => $requestedProductLine !== null ? $sales->publicPlans($productLine) : collect(),
            'preferredPlanId' => $preferredPlanId,
            'affiliate' => $affiliate,
            'manualPaymentReady' => $manualPayment->isConfigured(),
            'midtransReady' => $midtrans->isConfigured(),
            'selectedProductLine' => $requestedProductLine,
            'productLine' => $productLine,
            'productLineSelectionRequired' => $selectionRequired,
            'productLineLabel' => $this->productLineLabel($productLine),
            'productLineOptions' => $this->productLineOptions($sales, $request),
            'trialAvailable' => $trialAvailable,
            'trialDays' => $trialAvailable ? $this->trialDaysForProductLine($productLine) : null,
            'trialEntry' => $this->trialEntryFromRequest($request),
            'promoCode' => $promoPreview ? (string) $promoPreview->code : '',
            'promoPreview' => $promoPreview,
        ]);
    }

    /**
     * Validate, create the pending tenant sale, then redirect to checkout.
     */
    public function store(Request $request, TenantOnboardingSalesService $sales, PlatformMidtransBillingService $midtrans, PlatformAffiliateService $affiliateService, PlatformManualPaymentService $manualPayment, TenantSlugReservationService $slugReservations)
    {
        abort_unless(config('multitenancy.mode') === 'saas', 404);

        $affiliateService->captureFromRequest($request);
        $productLine = $this->requireRequestedPublicProductLine($request);
        $trialAvailable = $this->trialAvailableForProductLine($productLine, $request);

        $reservedSlugs = config('multitenancy.reserved_slugs', []);

        $data = $request->validate([
            'product_line' => ['required', 'string', Rule::in(['accounting', 'commerce', 'omnichannel', 'crm'])],
            'signup_mode' => ['required', 'string', Rule::in(['paid', 'trial'])],
            'trial_entry' => ['nullable', 'string', 'max:100'],
            'subscription_plan_id' => [
                'required',
                'integer',
                Rule::exists('subscription_plans', 'id')->where(
                    fn ($query) => $query->whereRaw('is_active = TRUE')->whereRaw('is_public = TRUE')
                ),
            ],
            'company_name' => ['required', 'string', 'max:100'],
            'slug' => [
                'required',
                'string',
                'max:63',
                'regex:/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?$/',
                Rule::notIn($reservedSlugs),
                Rule::unique('tenants', 'slug'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'payment_method' => [
                Rule::requiredIf(fn () => $request->input('signup_mode', 'paid') === 'paid'),
                'nullable',
                'string',
                Rule::in(['midtrans', 'bank_transfer']),
            ],
            'promo_code' => ['nullable', 'string', 'max:64'],
            'terms_accepted' => ['accepted'],
        ], [
            'slug.regex' => 'Subdomain hanya boleh huruf kecil, angka, dan tanda hubung, dan tidak boleh diawali/diakhiri tanda hubung.',
            'slug.not_in' => 'Subdomain tersebut tidak tersedia. Pilih nama lain.',
            'slug.unique' => 'Subdomain tersebut sudah dipakai. Pilih nama lain.',
            'payment_method.required' => 'Pilih metode pembayaran terlebih dahulu.',
            'terms_accepted.accepted' => 'Anda harus menyetujui kebijakan privasi dan syarat ketentuan.',
        ]);

        if ($slugReservations->isReserved($data['slug'])) {
            throw ValidationException::withMessages([
                'slug' => 'Subdomain tersebut masih dikunci sementara. Pilih nama lain atau coba lagi beberapa waktu lagi.',
            ]);
        }

        if ($data['payment_method'] === 'bank_transfer' && !$manualPayment->isConfigured()) {
            throw ValidationException::withMessages([
                'payment_method' => 'Transfer bank manual belum tersedia saat ini.',
            ]);
        }

        if ($data['payment_method'] === 'midtrans' && !$midtrans->isConfigured()) {
            throw ValidationException::withMessages([
                'payment_method' => 'Pembayaran Midtrans belum tersedia saat ini.',
            ]);
        }

        $plan = SubscriptionPlan::query()
            ->whereKey($data['subscription_plan_id'])
            ->active()
            ->public()
            ->firstOrFail();

        if ($plan->productLine() !== $productLine) {
            throw ValidationException::withMessages([
                'subscription_plan_id' => 'Plan yang dipilih belum tersedia di alur pendaftaran publik saat ini.',
            ]);
        }

        if (($data['signup_mode'] ?? 'paid') === 'trial') {
            if (!$trialAvailable) {
                throw ValidationException::withMessages([
                    'signup_mode' => 'Free trial belum tersedia untuk produk ini.',
                ]);
            }

            if ($plan->billing_interval !== 'monthly') {
                throw ValidationException::withMessages([
                    'subscription_plan_id' => 'Free trial saat ini hanya tersedia untuk plan bulanan.',
                ]);
            }

            $result = $sales->createTrialWorkspace($data, $plan, $this->trialDaysForProductLine($productLine));
            $sales->queueWelcomeMail([
                'admin_name' => (string) $result['user']->name,
                'admin_user_id' => (int) $result['user']->id,
                'admin_email' => (string) $result['user']->email,
                'tenant_name' => (string) $result['tenant']->name,
                'tenant_slug' => (string) $result['tenant']->slug,
                'login_url' => $sales->tenantLoginUrl($result['tenant']) . '&trial=1',
            ]);

            return redirect()->away($sales->tenantLoginUrl($result['tenant']) . '&trial=1');
        }

        $promo = $this->resolvePromoCode($data['promo_code'] ?? null, $plan->productLine());

        $result = $sales->createPendingWorkspace($data, $plan, $data['payment_method'], $promo);
        $affiliateService->attachReferralToOrder($request, $result['order'], $result['tenant'], $plan);
        $invoice = $result['invoice']->fresh(['tenant', 'plan', 'order']);

        if ($data['payment_method'] === 'bank_transfer') {
            $invoice = $manualPayment->attachQuote($invoice);
        }

        $sales->queueInvoiceMail($invoice);

        if ($data['payment_method'] === 'bank_transfer') {
            return redirect()->away($sales->publicInvoiceUrl($invoice));
        }

        try {
            $checkout = $midtrans->createOrReuseCheckout($invoice);

            return redirect()->away($checkout['redirect_url']);
        } catch (\Throwable $e) {
            logger()->error('TenantOnboarding: failed to create checkout', [
                'tenant' => $result['tenant']->slug,
                'plan' => $plan->code,
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->away($sales->publicInvoiceUrl($invoice));
        }
    }

    private function requestedPublicProductLineRaw(Request $request): ?string
    {
        $requested = strtolower(trim((string) (
            $request->input('product_line')
            ?: $request->query('product_line')
            ?: $request->input('product')
            ?: $request->query('product')
        )));

        return in_array($requested, ['accounting', 'omnichannel', 'commerce', 'crm'], true)
            ? $requested
            : null;
    }

    private function requireRequestedPublicProductLine(Request $request): string
    {
        $requested = $this->requestedPublicProductLineRaw($request);

        if ($requested !== null) {
            return $requested;
        }

        throw ValidationException::withMessages([
            'product_line' => 'Pilih business suite terlebih dahulu sebelum melanjutkan pendaftaran.',
        ]);
    }

    private function productLineLabel(string $productLine): string
    {
        if ($productLine === 'accounting') {
            return 'Accounting';
        }

        if ($productLine === 'commerce') {
            return 'Commerce';
        }

        if ($productLine === 'crm') {
            return 'CRM';
        }

        return 'Omnichannel';
    }

    private function trialAvailableForProductLine(string $productLine, Request $request): bool
    {
        return $productLine === 'accounting'
            && $this->trialEntryFromRequest($request) === 'accounting_landing';
    }

    private function trialDaysForProductLine(string $productLine): int
    {
        return $productLine === 'accounting' ? 14 : 0;
    }

    private function trialEntryFromRequest(Request $request): string
    {
        return trim((string) ($request->input('trial_entry') ?: $request->query('trial_entry', '')));
    }

    private function resolvePromoCode(?string $code, ?string $productLine): ?PlatformPromoCode
    {
        if (empty($code)) {
            return null;
        }

        $promo = PlatformPromoCode::findByCode($code);

        if (! $promo || ! $promo->isUsable($productLine)) {
            throw ValidationException::withMessages([
                'promo_code' => 'Kode promo tidak valid atau sudah tidak berlaku.',
            ]);
        }

        return $promo;
    }

    private function productLineOptions(TenantOnboardingSalesService $sales, Request $request): array
    {
        $base = [
            'promo_code' => $request->query('promo_code'),
        ];

        $trialEntry = $this->trialEntryFromRequest($request);
        if ($trialEntry !== '') {
            $base['trial_entry'] = $trialEntry;
        }

        return [
            [
                'key' => 'accounting',
                'label' => 'Accounting',
                'description' => 'Untuk transaksi, pembayaran, finance operasional, products, contacts, dan reporting.',
                'highlights' => ['Sales', 'Payments', 'Finance', 'Reports'],
                'available' => $sales->publicPlans('accounting')->isNotEmpty(),
                'url' => route('onboarding.create', array_filter($base + ['product_line' => 'accounting'])),
            ],
            [
                'key' => 'commerce',
                'label' => 'Commerce',
                'description' => 'Untuk storefront, order commerce, shipping, fulfillment, affiliate, dan wallet.',
                'highlights' => ['Storefront', 'Shipping', 'Fulfillment', 'Affiliate'],
                'available' => $sales->publicPlans('commerce')->isNotEmpty(),
                'url' => route('onboarding.create', array_filter($base + ['product_line' => 'commerce'])),
            ],
            [
                'key' => 'omnichannel',
                'label' => 'Omnichannel',
                'description' => 'Untuk shared inbox, social inbox, live chat, WhatsApp, dan automasi percakapan.',
                'highlights' => ['Conversations', 'Live Chat', 'WhatsApp', 'CRM'],
                'available' => $sales->publicPlans('omnichannel')->isNotEmpty(),
                'url' => route('onboarding.create', array_filter($base + ['product_line' => 'omnichannel'])),
            ],
            [
                'key' => 'crm',
                'label' => 'CRM',
                'description' => 'Untuk pipeline penjualan, follow-up queue, customer 360, dan operasional sales yang mobile-friendly.',
                'highlights' => ['Deals', 'Customer 360', 'Follow-Up', 'Pipeline'],
                'available' => $sales->publicPlans('crm')->isNotEmpty(),
                'url' => route('onboarding.create', array_filter($base + ['product_line' => 'crm'])),
            ],
        ];
    }
}
