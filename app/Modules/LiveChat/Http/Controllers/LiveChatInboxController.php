<?php

namespace App\Modules\LiveChat\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Conversations\Contracts\InboxMessageIngester;
use App\Modules\Conversations\Data\InboxMessageEnvelope;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\LiveChat\Http\Requests\ReplyLiveChatRequest;
use App\Modules\LiveChat\Models\LiveChatWidget;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LiveChatInboxController extends Controller
{
    public function __construct(private readonly InboxMessageIngester $ingester)
    {
    }

    public function index(Request $request): View
    {
        $status = $request->query('status', 'open');
        $widgetId = $request->query('widget_id');

        $conversations = Conversation::query()
            ->where('channel', 'live_chat')
            ->when($status && $status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($widgetId, fn ($q) => $q->where('instance_id', $widgetId))
            ->with('owner')
            ->orderByDesc('last_message_at')
            ->paginate(20)
            ->withQueryString();

        $widgets = LiveChatWidget::query()->orderBy('name')->get(['id', 'name']);

        return view('livechat::inbox.index', compact('conversations', 'widgets', 'status', 'widgetId'));
    }

    public function show(Conversation $conversation): View
    {
        abort_unless($conversation->channel === 'live_chat', 404);

        $messages = ConversationMessage::query()
            ->where('conversation_id', $conversation->id)
            ->with('user')
            ->orderBy('id')
            ->get();

        $widget = $conversation->instance_id
            ? LiveChatWidget::find($conversation->instance_id)
            : null;

        return view('livechat::inbox.show', compact('conversation', 'messages', 'widget'));
    }

    public function reply(ReplyLiveChatRequest $request, Conversation $conversation): RedirectResponse
    {
        abort_unless($conversation->channel === 'live_chat', 404);

        $this->ingester->ingest(new InboxMessageEnvelope(
            channel: 'live_chat',
            instanceId: (int) $conversation->instance_id,
            conversationExternalId: null,
            contactExternalId: (string) $conversation->contact_external_id,
            contactName: $conversation->contact_name,
            direction: 'out',
            type: 'text',
            body: $request->input('body'),
            externalMessageId: null,
            actorUserId: $request->user()->id,
            incrementUnread: false,
        ));

        return redirect()->route('live-chat.inbox.show', $conversation)
            ->with('success', 'Pesan terkirim.');
    }

    public function close(Conversation $conversation): RedirectResponse
    {
        abort_unless($conversation->channel === 'live_chat', 404);

        $conversation->update(['status' => 'closed']);

        return redirect()->route('live-chat.inbox.show', $conversation)
            ->with('success', 'Percakapan ditutup.');
    }
}
