<?php

namespace App\Modules\Tripay\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tripay\Http\Requests\UpdateTripaySettingsRequest;
use App\Modules\Tripay\Models\TripaySetting;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TripaySettingsController extends Controller
{
    public function edit(): View
    {
        $setting = TripaySetting::forCurrentTenant() ?? new TripaySetting([
            'environment' => 'sandbox',
            'is_active' => false,
        ]);

        return view('tripay::settings', compact('setting'));
    }

    public function update(UpdateTripaySettingsRequest $request): RedirectResponse
    {
        $setting = TripaySetting::query()->firstOrNew([
            'tenant_id' => TenantContext::currentId(),
        ]);

        $isNew = !$setting->exists;
        $data = $request->validated();

        foreach (['api_key', 'private_key', 'callback_signature_key'] as $field) {
            if (!empty($data[$field])) {
                $setting->{$field} = $data[$field];
            }
        }

        $setting->environment = $data['environment'];
        $setting->merchant_code = $data['merchant_code'] ?? null;
        $setting->is_active = $request->boolean('is_active');
        $setting->updated_by = $request->user()?->id;

        if ($isNew) {
            $setting->tenant_id = TenantContext::currentId();
            $setting->created_by = $request->user()?->id;
        }

        $setting->save();

        return redirect()
            ->route('tripay.settings.edit')
            ->with('status', 'Pengaturan Tripay disimpan.');
    }
}
