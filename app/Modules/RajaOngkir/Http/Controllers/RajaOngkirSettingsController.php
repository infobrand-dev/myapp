<?php

namespace App\Modules\RajaOngkir\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RajaOngkir\Http\Requests\UpdateRajaOngkirSettingsRequest;
use App\Modules\RajaOngkir\Models\RajaOngkirSetting;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RajaOngkirSettingsController extends Controller
{
    public function edit(): View
    {
        $setting = RajaOngkirSetting::forCurrentTenant() ?? new RajaOngkirSetting([
            'environment' => 'production',
            'is_active' => false,
        ]);

        return view('rajaongkir::settings', compact('setting'));
    }

    public function update(UpdateRajaOngkirSettingsRequest $request): RedirectResponse
    {
        $setting = RajaOngkirSetting::query()->firstOrNew([
            'tenant_id' => TenantContext::currentId(),
        ]);

        $isNew = !$setting->exists;
        $data = $request->validated();

        if (!empty($data['api_key'])) {
            $setting->api_key = $data['api_key'];
        }

        $setting->environment = $data['environment'];
        $setting->default_origin_area_id = $data['default_origin_area_id'] ?: null;
        $setting->default_couriers = collect(explode(',', (string) ($data['default_couriers'] ?? '')))
            ->map(fn ($value) => trim($value))
            ->filter()
            ->values()
            ->all();
        $setting->is_active = $request->boolean('is_active');
        $setting->updated_by = $request->user()?->id;

        if ($isNew) {
            $setting->tenant_id = TenantContext::currentId();
            $setting->created_by = $request->user()?->id;
        }

        $setting->save();

        return redirect()->route('rajaongkir.settings.edit')->with('status', 'Pengaturan RajaOngkir disimpan.');
    }
}
