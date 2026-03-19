<?php

namespace App\Modules\WhatsAppWeb\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\WhatsAppWeb\Models\WhatsAppWebSetting;
use App\Modules\WhatsAppWeb\Support\RuntimeSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        $setting = WhatsAppWebSetting::first();

        return view('whatsappweb::settings', compact('setting'));
    }

    public function update(Request $request): RedirectResponse
    {
        $setting = WhatsAppWebSetting::first();

        $validated = $request->validate([
            'base_url' => ['required', 'url'],
            'verify_token' => ['nullable', 'string', 'max:255'],
        ]);

        $webhookToken = $validated['verify_token'] ?? null;
        unset($validated['verify_token']);

        DB::transaction(function () use (&$setting, $request, $validated, $webhookToken): void {
            if (!$setting) {
                $setting = new WhatsAppWebSetting();
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
