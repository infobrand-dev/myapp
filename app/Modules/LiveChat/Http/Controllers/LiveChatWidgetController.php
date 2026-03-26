<?php

namespace App\Modules\LiveChat\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserPresence;
use App\Modules\Conversations\Contracts\ConversationAccessRegistry;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\LiveChat\Http\Requests\StoreLiveChatWidgetRequest;
use App\Modules\LiveChat\Models\LiveChatWidget;
use App\Modules\LiveChat\Support\LiveChatRealtimeState;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LiveChatWidgetController extends Controller
{
    public function __construct(private readonly LiveChatRealtimeState $realtimeState)
    {
    }

    public function index(): View
    {
        $widgets = LiveChatWidget::query()
            ->where('tenant_id', TenantContext::currentId())
            ->latest('id')
            ->paginate(15);

        return view('livechat::widgets.index', compact('widgets'));
    }

    public function create(): View
    {
        $widget = new LiveChatWidget([
            'theme_color' => '#206bc4',
            'welcome_text' => 'Halo, ada yang bisa kami bantu?',
            'launcher_label' => 'Chat',
            'position' => 'right',
            'header_bg_color' => '#206bc4',
            'visitor_bubble_color' => '#206bc4',
            'agent_bubble_color' => '#ffffff',
            'is_active' => true,
        ]);

        return view('livechat::widgets.form', compact('widget'));
    }

    public function store(StoreLiveChatWidgetRequest $request): RedirectResponse
    {
        $widget = LiveChatWidget::query()->create($this->prepareData($request));

        return redirect()
            ->route('live-chat.widgets.edit', $widget)
            ->with('status', 'Widget live chat dibuat.');
    }

    public function edit(LiveChatWidget $widget): View
    {
        return view('livechat::widgets.form', compact('widget'));
    }

    public function update(StoreLiveChatWidgetRequest $request, LiveChatWidget $widget): RedirectResponse
    {
        $widget->update($this->prepareData($request));

        return redirect()
            ->route('live-chat.widgets.edit', $widget)
            ->with('status', 'Widget live chat diperbarui.');
    }

    public function destroy(LiveChatWidget $widget): RedirectResponse
    {
        if ($widget->is_active) {
            return back()->with('error', 'Nonaktifkan widget sebelum menghapus.');
        }

        $widget->delete();

        return redirect()
            ->route('live-chat.widgets.index')
            ->with('status', 'Widget live chat dihapus.');
    }

    public function typing(Request $request, Conversation $conversation): JsonResponse
    {
        abort_unless($conversation->channel === 'live_chat', 404);

        $user = $request->user();
        $this->authorizeConversationRealtime($conversation, $user);

        $this->realtimeState->markAgentTyping((int) $conversation->id, (int) $user->id);

        return response()->json(['ok' => true]);
    }

    public function status(Request $request, Conversation $conversation): JsonResponse
    {
        abort_unless($conversation->channel === 'live_chat', 404);

        $user = $request->user();
        $this->authorizeConversationRealtime($conversation, $user);
        $conversation->loadMissing('owner');

        $claimable = !$conversation->owner_id || optional($conversation->locked_until)->isPast();

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'status' => $conversation->status,
            ],
            'typing' => [
                'visitor' => $this->realtimeState->isVisitorTyping((int) ($conversation->instance_id ?? 0), (string) ($conversation->contact_external_id ?? '')),
                'agent' => $this->realtimeState->isAgentTyping((int) $conversation->id, $conversation->owner_id),
            ],
            'presence' => [
                'agent' => $this->agentPresence($conversation),
            ],
            'assignment' => [
                'owner_id' => $conversation->owner_id,
                'owner_name' => $conversation->owner->name ?? null,
                'claimable' => $claimable,
                'claimed_by_me' => (int) ($conversation->owner_id ?? 0) === (int) $user->id,
                'locked_until' => optional($conversation->locked_until)->toIso8601String(),
            ],
        ]);
    }

    private function prepareData(StoreLiveChatWidgetRequest $request): array
    {
        $data = $request->validated();

        $domains = preg_split('/\r\n|\r|\n/', (string) ($data['allowed_domains'] ?? '')) ?: [];
        $domains = array_values(array_filter(array_map(static fn ($item) => trim($item), $domains)));

        return [
            'tenant_id'            => TenantContext::currentId(),
            'name'                 => $data['name'],
            'website_name'         => $data['website_name'] ?? null,
            'welcome_text'         => $data['welcome_text'] ?? null,
            'theme_color'          => $data['theme_color'],
            'launcher_label'       => trim((string) ($data['launcher_label'] ?? '')) ?: null,
            'position'             => trim((string) ($data['position'] ?? '')) ?: null,
            'logo_url'             => trim((string) ($data['logo_url'] ?? '')) ?: null,
            'header_bg_color'      => trim((string) ($data['header_bg_color'] ?? '')) ?: null,
            'visitor_bubble_color' => trim((string) ($data['visitor_bubble_color'] ?? '')) ?: null,
            'agent_bubble_color'   => trim((string) ($data['agent_bubble_color'] ?? '')) ?: null,
            'allowed_domains'      => $domains ?: null,
            'is_active'            => $request->boolean('is_active', true),
        ];
    }

    private function authorizeConversationRealtime(Conversation $conversation, User $user): void
    {
        abort_unless(
            $user->hasRole('Super-admin')
            || (int) ($conversation->owner_id ?? 0) === (int) $user->id
            || $conversation->participants()->where('user_id', $user->id)->exists()
            || app(ConversationAccessRegistry::class)->canView($conversation, $user),
            403
        );
    }

    private function agentPresence(Conversation $conversation): string
    {
        if (!$conversation->owner_id) {
            return UserPresence::STATUS_OFFLINE;
        }

        $presence = UserPresence::query()->where('user_id', (int) $conversation->owner_id)->first();

        return $presence ? $presence->effectiveStatus() : UserPresence::STATUS_OFFLINE;
    }
}
