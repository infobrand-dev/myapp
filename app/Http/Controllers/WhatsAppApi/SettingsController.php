<?php

namespace App\Http\Controllers\WhatsAppApi;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppApiSetting;
use App\Services\WhatsAppApi\WhatsAppApiClient;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function chat()
    {
        // placeholder page for WhatsApp API chat module
        return view('whatsapp-api.chat');
    }

    public function edit()
    {
        $setting = WhatsAppApiSetting::first();

        return view('whatsapp-api.settings', compact('setting'));
    }

    public function update(Request $request)
    {
        $setting = WhatsAppApiSetting::first();

        $rules = [
            'provider' => ['required', Rule::in(['meta_cloud', 'third_party'])],
            'base_url' => ['nullable', 'url', 'required_if:provider,third_party'],
            'phone_number_id' => ['nullable', 'string', 'required_if:provider,meta_cloud'],
            'waba_id' => ['nullable', 'string'],
            'access_token' => [$setting ? 'nullable' : 'required', 'string'],
            'verify_token' => ['nullable', 'string'],
            'default_sender_name' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'timeout_seconds' => ['nullable', 'integer', 'min:5', 'max:120'],
            'notes' => ['nullable', 'string'],
        ];

        $validated = $request->validate($rules);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['timeout_seconds'] = $validated['timeout_seconds'] ?? 30;

        $accessToken = $validated['access_token'] ?? null;
        unset($validated['access_token']);

        if (!$setting) {
            $setting = new WhatsAppApiSetting();
            $setting->created_by = $request->user()->id;
        }

        $setting->fill($validated);

        if ($accessToken !== null && $accessToken !== '') {
            $setting->access_token = $accessToken;
        }

        $setting->updated_by = $request->user()->id;
        $setting->save();

        return redirect()
            ->route('whatsapp-api.settings')
            ->with('status', 'Settings WhatsApp API berhasil disimpan.');
    }

    public function test(Request $request, WhatsAppApiClient $client)
    {
        $setting = WhatsAppApiSetting::first();

        if (!$setting) {
            return redirect()
                ->route('whatsapp-api.settings')
                ->with('error', 'Settings belum disimpan. Silakan simpan terlebih dahulu.');
        }

        $result = $client->testConnection($setting);

        $setting->last_tested_at = now();
        $setting->last_test_status = $result['ok'] ? 'success' : 'failed';
        $setting->last_test_message = $result['message'];
        $setting->updated_by = $request->user()->id;
        $setting->save();

        return redirect()
            ->route('whatsapp-api.settings')
            ->with('test_status', $result['ok'] ? 'success' : 'danger')
            ->with('test_message', $result['message']);
    }
}
