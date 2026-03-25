<?php

namespace App\Modules\Midtrans\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Midtrans\Http\Requests\UpdateMidtransSettingsRequest;
use App\Modules\Midtrans\Models\MidtransSetting;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MidtransSettingsController extends Controller
{
    public function edit(): View
    {
        $setting = MidtransSetting::forCurrentTenant() ?? new MidtransSetting([
            'environment' => 'sandbox',
            'is_active'   => false,
        ]);

        return view('midtrans::settings', compact('setting'));
    }

    public function update(UpdateMidtransSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $setting = MidtransSetting::query()
            ->firstOrNew(['tenant_id' => TenantContext::currentId()]);

        $isNew = !$setting->exists;

        // Only update keys if provided (don't overwrite with empty string)
        if (!empty($data['server_key'])) {
            $setting->server_key = $data['server_key'];
        }
        if (!empty($data['client_key'])) {
            $setting->client_key = $data['client_key'];
        }

        $setting->environment      = $data['environment'];
        $setting->merchant_id      = $data['merchant_id'] ?? null;
        $setting->is_active        = $request->boolean('is_active');
        $setting->enabled_payments = $data['enabled_payments'] ?? [];
        $setting->updated_by  = $request->user()?->id;

        if ($isNew) {
            $setting->tenant_id  = TenantContext::currentId();
            $setting->created_by = $request->user()?->id;
        }

        $setting->save();

        return redirect()
            ->route('midtrans.settings.edit')
            ->with('status', 'Pengaturan Midtrans disimpan.');
    }
}
