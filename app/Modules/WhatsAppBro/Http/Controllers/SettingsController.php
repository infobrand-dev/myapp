<?php

namespace App\Modules\WhatsAppBro\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\WhatsAppBro\Models\WhatsAppBroSetting;
use App\Modules\WhatsAppBro\Support\RuntimeSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        $setting = WhatsAppBroSetting::first();

        return view('whatsappbro::settings', compact('setting'));
    }

    public function update(Request $request): RedirectResponse
    {
        $setting = WhatsAppBroSetting::first();

        $validated = $request->validate([
            'base_url' => ['required', 'url'],
            'verify_token' => ['nullable', 'string', 'max:255'],
        ]);

        $webhookToken = $validated['verify_token'] ?? null;
        unset($validated['verify_token']);

        DB::transaction(function () use (&$setting, $request, $validated, $webhookToken): void {
            if (!$setting) {
                $setting = new WhatsAppBroSetting();
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
            ->route('whatsappbro.settings.edit')
            ->with('status', 'Settings WhatsApp Bro berhasil disimpan.');
    }
}
