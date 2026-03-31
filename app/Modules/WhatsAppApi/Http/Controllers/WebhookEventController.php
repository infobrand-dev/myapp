<?php

namespace App\Modules\WhatsAppApi\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\WhatsAppApi\Models\WhatsAppInstance;
use App\Modules\WhatsAppApi\Models\WhatsAppWebhookEvent;
use App\Support\BooleanQuery;
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
            'date_from' => $request->string('date_from')->toString() ?: null,
            'date_to' => $request->string('date_to')->toString() ?: null,
        ];

        $instances = WhatsAppInstance::query()
            ->where('tenant_id', $this->tenantId())
            ->orderBy('name')
            ->get(['id', 'name']);

        $baseQuery = WhatsAppWebhookEvent::query()
            ->where('tenant_id', $this->tenantId());

        $this->applyFilters($baseQuery, $filters);

        $events = (clone $baseQuery)
            ->with('instance:id,name')
            ->orderByDesc('received_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $summary = [
            'failed' => (clone $baseQuery)->where('process_status', 'failed')->count(),
            'ignored' => (clone $baseQuery)->where('process_status', 'ignored')->count(),
            'pending' => (clone $baseQuery)->where('process_status', 'pending')->count(),
            'invalid_signature' => BooleanQuery::apply(clone $baseQuery, 'signature_valid', false)->count(),
            'retryable' => (clone $baseQuery)->whereIn('process_status', ['failed', 'pending'])->count(),
        ];

        return view('whatsappapi::webhook-events.index', compact('events', 'instances', 'filters', 'summary'));
    }

    private function tenantId(): int
    {
        return TenantContext::currentId();
    }

    private function applyFilters($query, array $filters): void
    {
        $query
            ->when($filters['instance_id'], fn ($q, $instanceId) => $q->where('instance_id', $instanceId))
            ->when($filters['provider'], fn ($q, $provider) => $q->where('provider', $provider))
            ->when($filters['process_status'], fn ($q, $processStatus) => $q->where('process_status', $processStatus))
            ->when($filters['signature_valid'] !== null && $filters['signature_valid'] !== '', function ($q) use ($filters) {
                BooleanQuery::apply($q, 'signature_valid', $filters['signature_valid'] === '1');
            })
            ->when($filters['date_from'], fn ($q, $from) => $q->where('received_at', '>=', $from . ' 00:00:00'))
            ->when($filters['date_to'], fn ($q, $to) => $q->where('received_at', '<=', $to . ' 23:59:59'));
    }
}
