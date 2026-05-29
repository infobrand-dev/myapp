<?php

namespace App\Modules\Xendit\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Xendit\Http\Requests\UpdateXenditSettingsRequest;
use App\Modules\Xendit\Models\XenditSetting;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class XenditSettingsController extends Controller
{
    public function edit(): View
    {
        $setting = XenditSetting::forCurrentTenant() ?? new XenditSetting([
            'environment' => 'sandbox',
            'is_active' => false,
        ]);

        return view('xendit::settings', compact('setting'));
    }

    public function update(UpdateXenditSettingsRequest $request): RedirectResponse
    {
        $setting = XenditSetting::query()->firstOrNew([
            'tenant_id' => TenantContext::currentId(),
        ]);

        $isNew = !$setting->exists;
        $data = $request->validated();

        if (!empty($data['secret_key'])) {
            $setting->secret_key = $data['secret_key'];
        }

        if (!empty($data['webhook_token'])) {
            $setting->webhook_token = $data['webhook_token'];
        }

        $setting->environment = $data['environment'];
        $setting->is_active = $request->boolean('is_active');
        $setting->updated_by = $request->user()?->id;

        if ($isNew) {
            $setting->tenant_id = TenantContext::currentId();
            $setting->created_by = $request->user()?->id;
        }

        $setting->save();

        return redirect()
            ->route('xendit.settings.edit')
            ->with('status', 'Pengaturan Xendit disimpan.');
    }
}
