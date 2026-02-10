<?php

namespace App\Modules\WhatsAppApi\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\WhatsAppApi\Models\WhatsAppInstance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstanceController extends Controller
{
    public function index(): View
    {
        $instances = WhatsAppInstance::orderByDesc('created_at')->paginate(15);

        return view('whatsappapi::instances.index', compact('instances'));
    }

    public function create(): View
    {
        $instance = new WhatsAppInstance(['status' => 'disconnected', 'is_active' => true]);

        return view('whatsappapi::instances.form', compact('instance'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['is_active'] = $request->boolean('is_active');
        $data['created_by'] = $request->user() ? $request->user()->id : null;
        $data['updated_by'] = $request->user() ? $request->user()->id : null;

        WhatsAppInstance::create($data);

        return redirect()->route('whatsapp-api.instances.index')->with('status', 'Instance dibuat.');
    }

    public function edit(WhatsAppInstance $instance): View
    {
        return view('whatsappapi::instances.form', compact('instance'));
    }

    public function update(Request $request, WhatsAppInstance $instance): RedirectResponse
    {
        $data = $this->validated($request);
        $data['is_active'] = $request->boolean('is_active');
        $data['updated_by'] = $request->user() ? $request->user()->id : null;

        $instance->update($data);

        return redirect()->route('whatsapp-api.instances.index')->with('status', 'Instance diperbarui.');
    }

    public function destroy(WhatsAppInstance $instance): RedirectResponse
    {
        if ($instance->conversations()->exists()) {
            return back()->with('status', 'Tidak bisa menghapus: masih ada percakapan.');
        }

        $instance->delete();

        return redirect()->route('whatsapp-api.instances.index')->with('status', 'Instance dihapus.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'provider' => ['required', 'string', 'max:50'],
            'api_base_url' => ['nullable', 'url', 'max:255'],
            'api_token' => ['nullable', 'string', 'max:255'],
            'webhook_url' => ['nullable', 'url', 'max:255'],
            'status' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
            'settings' => ['nullable'],
        ]);

        if (isset($data['settings']) && is_string($data['settings']) && $data['settings'] !== '') {
            $decoded = json_decode($data['settings'], true);
            $data['settings'] = $decoded ?: null;
        }

        return $data;
    }
}
