<?php

namespace App\Modules\WhatsAppApi\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\WhatsAppApi\Http\Requests\RetryFailedMessagesRequest;
use App\Modules\WhatsAppApi\Jobs\SendWhatsAppMessage;
use App\Modules\WhatsAppApi\Models\WhatsAppInstance;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'instance_id' => $request->integer('instance_id') ?: null,
            'status' => $request->string('status')->toString() ?: null,
            'direction' => $request->string('direction')->toString() ?: null,
            'date_from' => $request->string('date_from')->toString() ?: null,
            'date_to' => $request->string('date_to')->toString() ?: null,
        ];

        $instances = WhatsAppInstance::query()
            ->where('tenant_id', $this->tenantId())
            ->orderBy('name')
            ->get(['id', 'name']);

        $statusOptions = [
            'queued' => 'Queued',
            'sent' => 'Sent',
            'delivered' => 'Delivered',
            'read' => 'Read',
            'error_retryable' => 'Error Retryable',
            'error_permanent' => 'Error Permanent',
            'error' => 'Error Legacy',
        ];

        $messages = ConversationMessage::query()
            ->where('tenant_id', $this->tenantId())
            ->with(['conversation.instance', 'user'])
            ->whereHas('conversation', fn ($q) => $q->where('tenant_id', $this->tenantId())->where('channel', 'wa_api'))
            ->when($filters['instance_id'], fn ($q, $instanceId) => $q->whereHas('conversation', fn ($cq) => $cq->where('instance_id', $instanceId)))
            ->when($filters['status'], fn ($q, $status) => $q->where('status', $status))
            ->when($filters['direction'], fn ($q, $direction) => $q->where('direction', $direction))
            ->when($filters['date_from'], fn ($q, $from) => $q->whereDate('created_at', '>=', $from))
            ->when($filters['date_to'], fn ($q, $to) => $q->whereDate('created_at', '<=', $to))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $summary = [
            'queued' => ConversationMessage::query()
                ->where('tenant_id', $this->tenantId())
                ->whereHas('conversation', fn ($q) => $q->where('tenant_id', $this->tenantId())->where('channel', 'wa_api'))
                ->where('status', 'queued')
                ->count(),
            'retryable' => ConversationMessage::query()
                ->where('tenant_id', $this->tenantId())
                ->whereHas('conversation', fn ($q) => $q->where('tenant_id', $this->tenantId())->where('channel', 'wa_api'))
                ->whereIn('status', ['error', 'error_retryable'])
                ->count(),
            'permanent' => ConversationMessage::query()
                ->where('tenant_id', $this->tenantId())
                ->whereHas('conversation', fn ($q) => $q->where('tenant_id', $this->tenantId())->where('channel', 'wa_api'))
                ->where('status', 'error_permanent')
                ->count(),
            'delivered' => ConversationMessage::query()
                ->where('tenant_id', $this->tenantId())
                ->whereHas('conversation', fn ($q) => $q->where('tenant_id', $this->tenantId())->where('channel', 'wa_api'))
                ->whereIn('status', ['delivered', 'read'])
                ->count(),
        ];

        return view('whatsappapi::logs.index', compact('messages', 'instances', 'filters', 'statusOptions', 'summary'));
    }

    public function retryFailed(RetryFailedMessagesRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $query = ConversationMessage::query()
            ->where('tenant_id', $this->tenantId())
            ->where('direction', 'out')
            ->whereIn('status', ['error', 'error_retryable'])
            ->whereHas('conversation', fn ($q) => $q->where('tenant_id', $this->tenantId())->where('channel', 'wa_api'));

        if (!empty($data['instance_id'])) {
            $instanceId = (int) $data['instance_id'];
            $query->whereHas('conversation', fn ($q) => $q->where('instance_id', $instanceId));
        }
        if (!empty($data['date_from'])) {
            $query->whereDate('created_at', '>=', $data['date_from']);
        }
        if (!empty($data['date_to'])) {
            $query->whereDate('created_at', '<=', $data['date_to']);
        }

        $messages = $query->orderBy('id')->limit(200)->get(['id']);

        if ($messages->isEmpty()) {
            return back()->with('status', 'Tidak ada pesan retryable yang cocok untuk diretry.');
        }

        $count = 0;
        foreach ($messages as $msg) {
            ConversationMessage::whereKey($msg->id)->update([
                'status' => 'queued',
                'error_message' => null,
                'sent_at' => null,
                'delivered_at' => null,
                'read_at' => null,
            ]);
            SendWhatsAppMessage::dispatch($msg->id);
            $count++;
        }

        return back()->with('status', "Retry queued untuk {$count} pesan retryable.");
    }

    public function requeue(ConversationMessage $message): RedirectResponse
    {
        $conversation = Conversation::query()
            ->where('tenant_id', $this->tenantId())
            ->whereKey($message->conversation_id)
            ->where('channel', 'wa_api')
            ->first();

        if (!$conversation || $message->direction !== 'out') {
            return back()->with('status', 'Pesan ini bukan outbound WhatsApp API.');
        }

        if (!in_array((string) $message->status, ['error', 'error_retryable', 'error_permanent'], true)) {
            return back()->with('status', 'Requeue hanya tersedia untuk pesan error.');
        }

        $message->update([
            'status' => 'queued',
            'error_message' => null,
            'sent_at' => null,
            'delivered_at' => null,
            'read_at' => null,
        ]);

        SendWhatsAppMessage::dispatch($message->id);

        return back()->with('status', 'Pesan berhasil di-requeue.');
    }

    private function tenantId(): int
    {
        return TenantContext::currentId();
    }
}
