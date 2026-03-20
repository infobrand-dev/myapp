<?php

namespace App\Modules\WhatsAppApi\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\WhatsAppApi\Models\WhatsAppInstance;
use App\Modules\WhatsAppApi\Models\WhatsAppWebhookEvent;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WebhookEventController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'instance_id' => $request->integer('instance_id') ?: null,
            'provider' => $request->string('provider')->toString() ?: null,
            'process_status' => $request->string('process_status')->toString() ?: null,
            'signature_valid' => $request->string('signature_valid')->toString() ?: null,
        ];

        $instances = WhatsAppInstance::query()
            ->where('tenant_id', $this->tenantId())
            ->orderBy('name')
            ->get(['id', 'name']);

        $events = WhatsAppWebhookEvent::query()
            ->where('tenant_id', $this->tenantId())
            ->with('instance:id,name')
            ->when($filters['instance_id'], fn ($q, $instanceId) => $q->where('instance_id', $instanceId))
            ->when($filters['provider'], fn ($q, $provider) => $q->where('provider', $provider))
            ->when($filters['process_status'], fn ($q, $processStatus) => $q->where('process_status', $processStatus))
            ->when($filters['signature_valid'] !== null && $filters['signature_valid'] !== '', function ($q) use ($filters) {
                $q->where('signature_valid', $filters['signature_valid'] === '1');
            })
            ->orderByDesc('received_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $summary = [
            'failed' => WhatsAppWebhookEvent::query()->where('tenant_id', $this->tenantId())->where('process_status', 'failed')->count(),
            'ignored' => WhatsAppWebhookEvent::query()->where('tenant_id', $this->tenantId())->where('process_status', 'ignored')->count(),
            'pending' => WhatsAppWebhookEvent::query()->where('tenant_id', $this->tenantId())->where('process_status', 'pending')->count(),
            'invalid_signature' => WhatsAppWebhookEvent::query()->where('tenant_id', $this->tenantId())->where('signature_valid', false)->count(),
        ];

        return view('whatsappapi::webhook-events.index', compact('events', 'instances', 'filters', 'summary'));
    }

    private function tenantId(): int
    {
        return TenantContext::currentId();
    }
}
