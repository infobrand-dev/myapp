<?php

namespace App\Modules\WhatsAppWeb\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\WhatsAppWeb\Http\Requests\UpdateWhatsAppWebSettingsRequest;
use App\Modules\WhatsAppWeb\Models\WhatsAppWebSetting;
use App\Modules\WhatsAppWeb\Support\RuntimeSettings;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        $setting = WhatsAppWebSetting::query()
            ->where('tenant_id', TenantContext::currentId())
            ->first();

        return view('whatsappweb::settings', compact('setting'));
    }

    public function update(UpdateWhatsAppWebSettingsRequest $request): RedirectResponse
    {
        $setting = WhatsAppWebSetting::query()
            ->where('tenant_id', TenantContext::currentId())
            ->first();

        $validated = $request->validated();

        $webhookToken = $validated['verify_token'] ?? null;
        unset($validated['verify_token']);

        DB::transaction(function () use (&$setting, $request, $validated, $webhookToken): void {
            if (!$setting) {
                $setting = new WhatsAppWebSetting();
                $setting->tenant_id = TenantContext::currentId();
                $setting->provider = 'whatsapp_web';
                $setting->created_by = $request->user()->id;
            }

            $setting->base_url = $validated['base_url'];
            if ($webhookToken !== null && $webhookToken !== '') {
                $setting->verify_token = $webhookToken;
            }

            $setting->updated_by = $request->user()->id;
            $setting->save();
        });

        RuntimeSettings::clearCache();

        return redirect()
            ->route('whatsappweb.settings.edit')
            ->with('status', 'Settings WhatsApp Web berhasil disimpan.');
    }
}
