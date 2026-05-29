<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\TenantPaymentGateway;
use App\Support\CompanyContext;
use App\Support\Payments\PaymentGatewayManager;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PaymentGatewaySettingsController extends Controller
{
    public function update(Request $request, PaymentGatewayManager $paymentGateways): RedirectResponse
    {
        $company = CompanyContext::currentCompany();
        abort_unless($company, 404);
        $tenantId = TenantContext::currentId();
        $providerOptions = collect($paymentGateways->providers())
            ->pluck('provider')
            ->prepend('manual')
            ->unique()
            ->values()
            ->all();

        $data = $request->validate([
            'provider' => ['required', Rule::in($providerOptions)],
        ]);

        TenantPaymentGateway::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $company->id)
            ->update([
                'is_enabled' => false,
                'updated_by' => $request->user()?->id,
                'updated_at' => now(),
            ]);

        if ($data['provider'] !== 'manual') {
            $driver = $paymentGateways->driver($data['provider']);

            if (!$driver || !$driver->isConfigured()) {
                throw ValidationException::withMessages([
                    'provider' => 'Provider yang dipilih belum dikonfigurasi untuk tenant ini.',
                ]);
            }

            TenantPaymentGateway::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'company_id' => $company->id,
                    'provider' => $data['provider'],
                ],
                [
                    'display_name' => $driver->label(),
                    'is_enabled' => true,
                    'meta' => [
                        'selected_from' => 'settings_payment_gateway',
                    ],
                    'created_by' => $request->user()?->id,
                    'updated_by' => $request->user()?->id,
                ]
            );
        }

        return redirect()->route('settings.payment-gateway')->with('status', 'Payment gateway aktif berhasil diperbarui.');
    }
}
