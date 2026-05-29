<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\TenantShippingProvider;
use App\Support\CompanyContext;
use App\Support\Shipping\ShippingProviderManager;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ShippingProviderSettingsController extends Controller
{
    public function update(Request $request, ShippingProviderManager $shippingProviders): RedirectResponse
    {
        $company = CompanyContext::currentCompany();
        abort_unless($company, 404);
        $tenantId = TenantContext::currentId();
        $providerOptions = collect($shippingProviders->providers())
            ->pluck('provider')
            ->prepend('manual')
            ->unique()
            ->values()
            ->all();

        $data = $request->validate([
            'provider' => ['required', Rule::in($providerOptions)],
        ]);

        TenantShippingProvider::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $company->id)
            ->update([
                'is_enabled' => false,
                'updated_by' => $request->user()?->id,
                'updated_at' => now(),
            ]);

        if ($data['provider'] !== 'manual') {
            $driver = $shippingProviders->driver($data['provider']);

            if (!$driver || !$driver->isConfigured()) {
                throw ValidationException::withMessages([
                    'provider' => 'Provider yang dipilih belum dikonfigurasi untuk tenant ini.',
                ]);
            }

            TenantShippingProvider::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'company_id' => $company->id,
                    'provider' => $data['provider'],
                ],
                [
                    'display_name' => $driver->label(),
                    'is_enabled' => true,
                    'meta' => [
                        'selected_from' => 'settings_shipping_provider',
                    ],
                    'created_by' => $request->user()?->id,
                    'updated_by' => $request->user()?->id,
                ]
            );
        }

        return redirect()->route('settings.shipping-provider')->with('status', 'Shipping provider aktif berhasil diperbarui.');
    }
}
