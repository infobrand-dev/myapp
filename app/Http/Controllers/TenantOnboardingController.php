<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Services\PlatformAffiliateService;
use App\Services\PlatformManualPaymentService;
use App\Services\PlatformMidtransBillingService;
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
        $productLine = $this->requestedPublicProductLine($request);
        $trialRequested = $this->wantsTrial($request, $productLine);

        $preferredPlanId = $sales->resolvePublicPlanIdByCode((string) request()->query('plan'), $productLine);

        return view('onboarding.create', [
            'plans' => $sales->publicPlans($productLine),
            'preferredPlanId' => $preferredPlanId,
            'affiliate' => $affiliate,
            'manualPaymentReady' => $manualPayment->isConfigured(),
            'midtransReady' => $midtrans->isConfigured(),
            'productLine' => $productLine,
            'productLineLabel' => $this->productLineLabel($productLine),
            'trialRequested' => $trialRequested,
        ]);
    }

    /**
     * Validate, create the pending tenant sale, then redirect to checkout.
     */
    public function store(Request $request, TenantOnboardingSalesService $sales, PlatformMidtransBillingService $midtrans, PlatformAffiliateService $affiliateService, PlatformManualPaymentService $manualPayment)
    {
        abort_unless(config('multitenancy.mode') === 'saas', 404);

        $affiliateService->captureFromRequest($request);

        $reservedSlugs = config('multitenancy.reserved_slugs', []);

        $data = $request->validate([
            'subscription_plan_id' => [
                'required',
                'integer',
                Rule::exists('subscription_plans', 'id')->where(
                    fn ($query) => $query->where('is_active', true)->where('is_public', true)
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
            'payment_method' => ['nullable', 'string', Rule::in(['midtrans', 'bank_transfer'])],
            'trial' => ['nullable', 'boolean'],
        ], [
            'slug.regex' => 'Subdomain hanya boleh huruf kecil, angka, dan tanda hubung, dan tidak boleh diawali/diakhiri tanda hubung.',
            'slug.not_in' => 'Subdomain tersebut tidak tersedia. Pilih nama lain.',
            'slug.unique' => 'Subdomain tersebut sudah dipakai. Pilih nama lain.',
        ]);

        $trialRequested = $this->wantsTrial($request, $this->requestedPublicProductLine($request));

        if (!$trialRequested && empty($data['payment_method'])) {
            throw ValidationException::withMessages([
                'payment_method' => 'Pilih metode pembayaran untuk melanjutkan pendaftaran.',
            ]);
        }

        if (!$trialRequested && $data['payment_method'] === 'bank_transfer' && !$manualPayment->isConfigured()) {
            throw ValidationException::withMessages([
                'payment_method' => 'Transfer bank manual belum tersedia saat ini.',
            ]);
        }

        if (!$trialRequested && $data['payment_method'] === 'midtrans' && !$midtrans->isConfigured()) {
            throw ValidationException::withMessages([
                'payment_method' => 'Pembayaran Midtrans belum tersedia saat ini.',
            ]);
        }

        $plan = SubscriptionPlan::query()
            ->whereKey($data['subscription_plan_id'])
            ->active()
            ->public()
            ->firstOrFail();

        if (!in_array($plan->productLine(), ['omnichannel', 'accounting'], true)) {
            throw ValidationException::withMessages([
                'subscription_plan_id' => 'Plan yang dipilih belum tersedia di alur pendaftaran publik saat ini.',
            ]);
        }

        if ($trialRequested) {
            if ($plan->productLine() !== 'accounting') {
                throw ValidationException::withMessages([
                    'subscription_plan_id' => 'Free trial 14 hari saat ini hanya tersedia untuk paket Accounting.',
                ]);
            }

            $result = $sales->createTrialWorkspace($data, $plan, 14);

            return redirect()->away($sales->tenantLoginUrl($result['tenant']) . '&trial=1');
        }

        $result = $sales->createPendingWorkspace($data, $plan, $data['payment_method']);
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

    private function requestedPublicProductLine(Request $request): string
    {
        $requested = strtolower(trim((string) ($request->query('product_line') ?: $request->query('product'))));

        return in_array($requested, ['accounting', 'omnichannel'], true)
            ? $requested
            : 'omnichannel';
    }

    private function productLineLabel(string $productLine): string
    {
        return match ($productLine) {
            'accounting' => 'Accounting',
            default => 'Omnichannel',
        };
    }

    private function wantsTrial(Request $request, string $productLine): bool
    {
        if ($productLine !== 'accounting') {
            return false;
        }

        return filter_var($request->input('trial', $request->query('trial')), FILTER_VALIDATE_BOOL);
    }
}
