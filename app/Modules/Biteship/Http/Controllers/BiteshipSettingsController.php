<?php

namespace App\Modules\Biteship\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Biteship\Http\Requests\UpdateBiteshipSettingsRequest;
use App\Modules\Biteship\Models\BiteshipSetting;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BiteshipSettingsController extends Controller
{
    public function edit(): View
    {
        $setting = BiteshipSetting::forCurrentTenant() ?? new BiteshipSetting([
            'environment' => 'sandbox',
            'is_active' => false,
        ]);

        return view('biteship::settings', compact('setting'));
    }

    public function update(UpdateBiteshipSettingsRequest $request): RedirectResponse
    {
        $setting = BiteshipSetting::query()->firstOrNew([
            'tenant_id' => TenantContext::currentId(),
        ]);

        $isNew = !$setting->exists;
        $data = $request->validated();

        if (!empty($data['api_key'])) {
            $setting->api_key = $data['api_key'];
        }

        $setting->environment = $data['environment'];
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

        return redirect()->route('biteship.settings.edit')->with('status', 'Pengaturan Biteship disimpan.');
    }
}
