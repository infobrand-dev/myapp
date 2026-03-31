<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Services\PlatformAffiliateService;
use App\Services\PlatformMidtransBillingService;
use App\Services\TenantOnboardingSalesService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class TenantOnboardingController extends Controller
{
    /**
     * Show the new-tenant registration form.
     * Only accessible in SaaS mode.
     */
    public function create(Request $request, TenantOnboardingSalesService $sales, PlatformAffiliateService $affiliateService)
    {
        abort_unless(config('multitenancy.mode') === 'saas', 404);

        $affiliate = $affiliateService->captureFromRequest($request);

        $preferredPlanId = $sales->resolvePublicPlanIdByCode((string) request()->query('plan'));

        return view('onboarding.create', [
            'plans' => $sales->publicPlans(),
            'preferredPlanId' => $preferredPlanId,
            'affiliate' => $affiliate,
        ]);
    }

    /**
     * Validate, create the pending tenant sale, then redirect to checkout.
     */
    public function store(Request $request, TenantOnboardingSalesService $sales, PlatformMidtransBillingService $midtrans, PlatformAffiliateService $affiliateService)
    {
        abort_unless(config('multitenancy.mode') === 'saas', 404);

        $affiliateService->captureFromRequest($request);

        $reservedSlugs = config('multitenancy.reserved_slugs', []);

        $data = $request->validate([
            'subscription_plan_id' => [
                'required',
                'integer',
                Rule::exists('subscription_plans', 'id')->where(
                    fn ($query) => $query->getConnection()->getDriverName() === 'pgsql'
                        ? $query->whereRaw('is_active is true and is_public is true')
                        : $query->where('is_active', true)->where('is_public', true)
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
        ], [
            'slug.regex' => 'Subdomain hanya boleh huruf kecil, angka, dan tanda hubung, dan tidak boleh diawali/diakhiri tanda hubung.',
            'slug.not_in' => 'Subdomain tersebut tidak tersedia. Pilih nama lain.',
            'slug.unique' => 'Subdomain tersebut sudah dipakai. Pilih nama lain.',
        ]);

        $plan = SubscriptionPlan::query()
            ->whereKey($data['subscription_plan_id'])
            ->active()
            ->public()
            ->firstOrFail();

        $result = $sales->createPendingWorkspace($data, $plan);
        $affiliateService->attachReferralToOrder($request, $result['order'], $result['tenant'], $plan);
        $invoice = $result['invoice']->fresh(['tenant', 'plan', 'order']);

        $sales->queueInvoiceMail($invoice);

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
}
