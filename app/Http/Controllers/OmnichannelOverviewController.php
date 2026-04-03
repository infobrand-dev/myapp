<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\LiveChat\Models\LiveChatWidget;
use App\Modules\SocialMedia\Models\SocialAccount;
use App\Modules\WhatsAppApi\Models\WhatsAppInstance;
use App\Services\TenantStorageUsageService;
use App\Support\PlanLimit;
use App\Support\TenantContext;
use App\Support\TenantPlanManager;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OmnichannelOverviewController extends Controller
{
    private const DEFAULT_SLA_TARGET_MINUTES = 15;

    public function __invoke(Request $request, TenantPlanManager $plans, TenantStorageUsageService $storageUsage): View|RedirectResponse
    {
        if (request()->attributes->get('platform_admin_host')) {
            return redirect()->route('platform.dashboard');
        }

        if ($redirect = $this->redirectToExpectedHost()) {
            return $redirect;
        }

        $tenantId = TenantContext::currentId();
        $ownerView = auth()->user()?->hasAnyRole(['Super-admin', 'Admin']) ?? false;
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);
        $filters = [
            'date_from' => $dateFrom->toDateString(),
            'date_to' => $dateTo->toDateString(),
        ];

        $channelHealth = $this->channelHealth($tenantId);
        $volume = $this->conversationVolume($tenantId, $dateFrom, $dateTo);
        $responseQuality = $this->responseQuality($tenantId, $dateFrom, $dateTo);
        $automation = $this->automationSummary($tenantId);
        $capacity = $this->capacitySummary($plans, $storageUsage, $tenantId);
        $channelBreakdown = $this->channelBreakdown($tenantId, $dateFrom, $dateTo);
        $channelTrend = $this->channelTrend($tenantId, $dateFrom, $dateTo);
        $teamWorkload = $this->teamWorkload($tenantId, $dateFrom, $dateTo);

        return view('omnichannel-overview', compact(
            'ownerView',
            'filters',
            'channelHealth',
            'volume',
            'responseQuality',
            'automation',
            'capacity',
            'channelBreakdown',
            'channelTrend',
            'teamWorkload'
        ));
    }

    public function export(Request $request, TenantPlanManager $plans, TenantStorageUsageService $storageUsage): Response|RedirectResponse
    {
        if (request()->attributes->get('platform_admin_host')) {
            return redirect()->route('platform.dashboard');
        }

        if ($redirect = $this->redirectToExpectedHost()) {
            return $redirect;
        }

        $tenantId = TenantContext::currentId();
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);

        $channelHealth = $this->channelHealth($tenantId);
        $volume = $this->conversationVolume($tenantId, $dateFrom, $dateTo);
        $responseQuality = $this->responseQuality($tenantId, $dateFrom, $dateTo);
        $automation = $this->automationSummary($tenantId);
        $capacity = $this->capacitySummary($plans, $storageUsage, $tenantId);
        $channelBreakdown = $this->channelBreakdown($tenantId, $dateFrom, $dateTo);
        $teamWorkload = $this->teamWorkload($tenantId, $dateFrom, $dateTo);

        $filename = 'omnichannel-overview-' . $dateFrom->format('Ymd') . '-' . $dateTo->format('Ymd') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->streamDownload(function () use (
            $dateFrom,
            $dateTo,
            $channelHealth,
            $volume,
            $responseQuality,
            $automation,
            $capacity,
            $channelBreakdown,
            $teamWorkload
        ): void {
            $out = fopen('php://output', 'wb');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['Section', 'Metric', 'Value']);
            fputcsv($out, ['Filters', 'Date From', $dateFrom->toDateString()]);
            fputcsv($out, ['Filters', 'Date To', $dateTo->toDateString()]);
            fputcsv($out, ['Channel Health', 'Connected Channels', $channelHealth['total_connected'] ?? 0]);
            fputcsv($out, ['Channel Health', 'Issue Channels', $channelHealth['total_issue'] ?? 0]);
            fputcsv($out, ['Volume', 'Conversations', $volume['total_period'] ?? 0]);
            fputcsv($out, ['Volume', 'Inbound', $volume['inbound_period'] ?? 0]);
            fputcsv($out, ['Volume', 'Open', $volume['open'] ?? 0]);
            fputcsv($out, ['Volume', 'Unassigned', $volume['unassigned'] ?? 0]);
            fputcsv($out, ['Volume', 'Waiting Reply', $volume['waiting_reply'] ?? 0]);
            fputcsv($out, ['Response Quality', 'Median First Response Minutes', $responseQuality['first_response_median_minutes'] ?? '']);
            fputcsv($out, ['Response Quality', 'Average First Response Minutes', $responseQuality['first_response_average_minutes'] ?? '']);
            fputcsv($out, ['Response Quality', 'Estimated Reply Minutes', $responseQuality['estimated_reply_minutes'] ?? '']);
            fputcsv($out, ['Response Quality', 'SLA Hit Rate', $responseQuality['sla_hit_rate'] ?? '']);
            fputcsv($out, ['Automation', 'Auto Reply Channels', $automation['auto_reply_channels'] ?? 0]);
            fputcsv($out, ['Automation', 'Handoff Queue', $automation['handoff_queue'] ?? 0]);
            fputcsv($out, ['Capacity', 'Users', $this->usageStateLabel($capacity['users'] ?? null)]);
            fputcsv($out, ['Capacity', 'Social Accounts', $this->usageStateLabel($capacity['social_accounts'] ?? null)]);
            fputcsv($out, ['Capacity', 'WhatsApp Instances', $this->usageStateLabel($capacity['whatsapp_instances'] ?? null)]);
            fputcsv($out, ['Capacity', 'Live Chat Widgets', $this->usageStateLabel($capacity['live_chat_widgets'] ?? null)]);
            fputcsv($out, ['Capacity', 'Storage', $this->usageStateLabel($capacity['storage'] ?? null, true)]);

            fputcsv($out, []);
            fputcsv($out, ['Channel Breakdown', 'Channel', 'Total']);
            foreach ($channelBreakdown as $item) {
                fputcsv($out, ['Channel Breakdown', $item['label'], $item['total']]);
            }

            fputcsv($out, []);
            fputcsv($out, ['Team Workload', 'Agent', 'Assigned Open', 'Active in Period', 'Overdue Queue']);
            foreach ($teamWorkload as $agent) {
                fputcsv($out, ['Team Workload', $agent['name'], $agent['assigned_open_count'], $agent['active_period_count'], $agent['overdue_queue_count']]);
            }

            fclose($out);
        }, $filename, $headers);
    }

    private function channelHealth(int $tenantId): array
    {
        $whatsAppConnected = class_exists(WhatsAppInstance::class) && Schema::hasTable('whatsapp_instances')
            ? WhatsAppInstance::query()
                ->where('tenant_id', $tenantId)
                ->whereIn('status', ['connected', 'ready'])
                ->count()
            : 0;

        $whatsAppIssue = class_exists(WhatsAppInstance::class) && Schema::hasTable('whatsapp_instances')
            ? WhatsAppInstance::query()
                ->where('tenant_id', $tenantId)
                ->whereNotIn('status', ['connected', 'ready'])
                ->count()
            : 0;

        $socialConnected = class_exists(SocialAccount::class) && Schema::hasTable('social_accounts')
            ? SocialAccount::query()
                ->where('tenant_id', $tenantId)
                ->where('status', 'connected')
                ->count()
            : 0;

        $socialIssue = class_exists(SocialAccount::class) && Schema::hasTable('social_accounts')
            ? SocialAccount::query()
                ->where('tenant_id', $tenantId)
                ->where('status', '!=', 'connected')
                ->count()
            : 0;

        $liveChatActive = class_exists(LiveChatWidget::class) && Schema::hasTable('live_chat_widgets')
            ? LiveChatWidget::query()
                ->where('tenant_id', $tenantId)
                ->active()
                ->count()
            : 0;

        return [
            'whatsapp_connected' => $whatsAppConnected,
            'whatsapp_issue' => $whatsAppIssue,
            'social_connected' => $socialConnected,
            'social_issue' => $socialIssue,
            'live_chat_active' => $liveChatActive,
            'total_connected' => $whatsAppConnected + $socialConnected + $liveChatActive,
            'total_issue' => $whatsAppIssue + $socialIssue,
        ];
    }

    private function conversationVolume(int $tenantId, Carbon $dateFrom, Carbon $dateTo): array
    {
        if (!Schema::hasTable('conversations') || !Schema::hasTable('conversation_messages')) {
            return [
                'total_period' => 0,
                'inbound_period' => 0,
                'open' => 0,
                'unassigned' => 0,
                'waiting_reply' => 0,
            ];
        }

        $waitingReplyQuery = Conversation::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'open')
            ->whereNotNull('last_incoming_at')
            ->where(function ($query): void {
                $query->whereNull('last_outgoing_at')
                    ->orWhereColumn('last_incoming_at', '>', 'last_outgoing_at');
            });

        return [
            'total_period' => Conversation::query()
                ->where('tenant_id', $tenantId)
                ->whereBetween('last_message_at', [$dateFrom, $dateTo])
                ->count(),
            'inbound_period' => ConversationMessage::query()
                ->where('tenant_id', $tenantId)
                ->where('direction', 'in')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->count(),
            'open' => Conversation::query()
                ->where('tenant_id', $tenantId)
                ->where('status', 'open')
                ->count(),
            'unassigned' => Conversation::query()
                ->where('tenant_id', $tenantId)
                ->where('status', 'open')
                ->whereNull('owner_id')
                ->count(),
            'waiting_reply' => (clone $waitingReplyQuery)->count(),
        ];
    }

    private function responseQuality(int $tenantId, Carbon $dateFrom, Carbon $dateTo): array
    {
        if (!Schema::hasTable('conversations')) {
            return $this->emptyResponseQuality();
        }

        $candidates = Conversation::query()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('last_incoming_at')
            ->whereNotNull('last_outgoing_at')
            ->whereColumn('last_outgoing_at', '>=', 'last_incoming_at')
            ->whereBetween('last_message_at', [$dateFrom, $dateTo])
            ->orderByDesc('last_message_at')
            ->limit(500)
            ->get(['id', 'last_incoming_at', 'last_outgoing_at']);

        $responseMinutes = $candidates
            ->map(function (Conversation $conversation): int {
                return max(0, (int) $conversation->last_incoming_at?->diffInMinutes($conversation->last_outgoing_at));
            })
            ->sort()
            ->values();

        $waitingConversations = Conversation::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'open')
            ->whereNotNull('last_incoming_at')
            ->where(function ($query): void {
                $query->whereNull('last_outgoing_at')
                    ->orWhereColumn('last_incoming_at', '>', 'last_outgoing_at');
            })
            ->get(['id', 'last_incoming_at', 'last_outgoing_at']);

        $waitingAges = $waitingConversations
            ->map(fn (Conversation $conversation): int => max(0, (int) $conversation->last_incoming_at?->diffInMinutes(now())))
            ->values();

        $agentCount = Schema::hasTable('users')
            ? User::query()
                ->where('tenant_id', $tenantId)
                ->whereHas('roles', fn ($query) => $query->whereIn('name', ['Super-admin', 'Admin', 'Customer Service', 'Sales']))
                ->count()
            : 0;

        $medianFirstResponse = $this->median($responseMinutes->all());
        $estimatedReply = $medianFirstResponse === null
            ? null
            : (int) max(
                $medianFirstResponse,
                ceil(($waitingConversations->count() / max($agentCount, 1)) * max($medianFirstResponse, 1))
            );

        if ($responseMinutes->isEmpty()) {
            return $this->emptyResponseQuality($waitingAges->all(), $estimatedReply);
        }

        $slaTargetMinutes = self::DEFAULT_SLA_TARGET_MINUTES;
        $slaHit = $responseMinutes->filter(fn (int $minutes) => $minutes <= $slaTargetMinutes)->count();

        return [
            'has_data' => true,
            'first_response_median_minutes' => $medianFirstResponse,
            'first_response_average_minutes' => (int) round($responseMinutes->avg() ?? 0),
            'estimated_reply_minutes' => $estimatedReply,
            'sla_target_minutes' => $slaTargetMinutes,
            'sla_hit_rate' => (int) round(($slaHit / max($responseMinutes->count(), 1)) * 100),
            'waiting_buckets' => $this->waitingBuckets($waitingAges->all()),
        ];
    }

    private function automationSummary(int $tenantId): array
    {
        $socialAutoReply = Schema::hasTable('social_account_chatbot_integrations')
            ? DB::table('social_account_chatbot_integrations')
                ->join('social_accounts', 'social_accounts.id', '=', 'social_account_chatbot_integrations.social_account_id')
                ->where('social_accounts.tenant_id', $tenantId)
                ->where('social_account_chatbot_integrations.auto_reply', true)
                ->count()
            : 0;

        $waAutoReply = Schema::hasTable('whatsapp_instance_chatbot_integrations')
            ? DB::table('whatsapp_instance_chatbot_integrations')
                ->join('whatsapp_instances', 'whatsapp_instances.id', '=', 'whatsapp_instance_chatbot_integrations.instance_id')
                ->where('whatsapp_instances.tenant_id', $tenantId)
                ->where('whatsapp_instance_chatbot_integrations.auto_reply', true)
                ->count()
            : 0;

        $handoffQueue = Schema::hasTable('conversations')
            ? $this->handoffQueueCount($tenantId)
            : 0;

        return [
            'auto_reply_channels' => $socialAutoReply + $waAutoReply,
            'handoff_queue' => $handoffQueue,
        ];
    }

    private function capacitySummary(TenantPlanManager $plans, TenantStorageUsageService $storageUsage, int $tenantId): array
    {
        return [
            'users' => $plans->usageState(PlanLimit::USERS, $tenantId),
            'social_accounts' => $plans->usageState(PlanLimit::SOCIAL_ACCOUNTS, $tenantId),
            'whatsapp_instances' => $plans->usageState(PlanLimit::WHATSAPP_INSTANCES, $tenantId),
            'live_chat_widgets' => $plans->usageState(PlanLimit::LIVE_CHAT_WIDGETS, $tenantId),
            'storage' => $plans->usageState(PlanLimit::TOTAL_STORAGE_BYTES, $tenantId) + [
                'used_bytes' => $storageUsage->usedBytes($tenantId),
            ],
        ];
    }

    private function channelBreakdown(int $tenantId, Carbon $dateFrom, Carbon $dateTo): array
    {
        if (!Schema::hasTable('conversations')) {
            return [];
        }

        $labels = [
            'wa_api' => 'WhatsApp API',
            'wa_web' => 'WhatsApp Web',
            'social_dm' => 'Social DM',
            'live_chat' => 'Live Chat',
            'internal' => 'Internal',
        ];

        return Conversation::query()
            ->select('channel')
            ->selectRaw('COUNT(*) as total')
            ->where('tenant_id', $tenantId)
            ->whereBetween('last_message_at', [$dateFrom, $dateTo])
            ->groupBy('channel')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'channel' => (string) $row->channel,
                'label' => $labels[(string) $row->channel] ?? strtoupper((string) $row->channel),
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();
    }

    private function teamWorkload(int $tenantId, Carbon $dateFrom, Carbon $dateTo): array
    {
        if (!Schema::hasTable('conversations') || !Schema::hasTable('users')) {
            return [];
        }

        $driver = DB::connection()->getDriverName();
        $overdueExpression = $driver === 'pgsql'
            ? "SUM(CASE WHEN conversations.last_incoming_at IS NOT NULL AND (conversations.last_outgoing_at IS NULL OR conversations.last_incoming_at > conversations.last_outgoing_at) AND conversations.last_incoming_at <= ? THEN 1 ELSE 0 END)"
            : "SUM(CASE WHEN conversations.last_incoming_at IS NOT NULL AND (conversations.last_outgoing_at IS NULL OR conversations.last_incoming_at > conversations.last_outgoing_at) AND conversations.last_incoming_at <= ? THEN 1 ELSE 0 END)";

        return User::query()
            ->select('users.id', 'users.name')
            ->selectRaw('COUNT(conversations.id) as assigned_open_count')
            ->selectRaw('SUM(CASE WHEN conversations.last_message_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as active_period_count', [
                $dateFrom,
                $dateTo,
            ])
            ->selectRaw($overdueExpression . ' as overdue_queue_count', [
                now()->subMinutes(self::DEFAULT_SLA_TARGET_MINUTES),
            ])
            ->leftJoin('conversations', function ($join) use ($tenantId): void {
                $join->on('conversations.owner_id', '=', 'users.id')
                    ->where('conversations.tenant_id', '=', $tenantId)
                    ->where('conversations.status', '=', 'open');
            })
            ->where('users.tenant_id', $tenantId)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['Super-admin', 'Admin', 'Customer Service', 'Sales']))
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('assigned_open_count')
            ->orderByDesc('active_period_count')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'name' => (string) $row->name,
                'assigned_open_count' => (int) $row->assigned_open_count,
                'active_period_count' => (int) $row->active_period_count,
                'overdue_queue_count' => (int) $row->overdue_queue_count,
            ])
            ->values()
            ->all();
    }

    private function channelTrend(int $tenantId, Carbon $dateFrom, Carbon $dateTo): array
    {
        if (!Schema::hasTable('conversations')) {
            return [];
        }

        $driver = DB::connection()->getDriverName();
        $dayExpression = $driver === 'pgsql'
            ? "TO_CHAR(last_message_at, 'YYYY-MM-DD')"
            : "DATE_FORMAT(last_message_at, '%Y-%m-%d')";

        $labels = [
            'wa_api' => 'WhatsApp API',
            'wa_web' => 'WhatsApp Web',
            'social_dm' => 'Social DM',
            'live_chat' => 'Live Chat',
            'internal' => 'Internal',
        ];

        return Conversation::query()
            ->select('channel')
            ->selectRaw($dayExpression . ' as day_key')
            ->selectRaw('COUNT(*) as total')
            ->where('tenant_id', $tenantId)
            ->whereBetween('last_message_at', [$dateFrom, $dateTo])
            ->groupBy('channel', DB::raw($dayExpression))
            ->orderBy('day_key')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'channel' => (string) $row->channel,
                'label' => $labels[(string) $row->channel] ?? strtoupper((string) $row->channel),
                'day' => (string) $row->day_key,
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();
    }

    private function emptyResponseQuality(array $waitingAges = [], ?int $estimatedReply = null): array
    {
        return [
            'has_data' => false,
            'first_response_median_minutes' => null,
            'first_response_average_minutes' => null,
            'estimated_reply_minutes' => $estimatedReply,
            'sla_target_minutes' => 15,
            'sla_hit_rate' => null,
            'waiting_buckets' => $this->waitingBuckets($waitingAges),
        ];
    }

    private function waitingBuckets(array $waitingAges): array
    {
        return [
            'lt_5' => count(array_filter($waitingAges, static fn (int $minutes) => $minutes < 5)),
            'm5_15' => count(array_filter($waitingAges, static fn (int $minutes) => $minutes >= 5 && $minutes < 15)),
            'm15_60' => count(array_filter($waitingAges, static fn (int $minutes) => $minutes >= 15 && $minutes < 60)),
            'gt_60' => count(array_filter($waitingAges, static fn (int $minutes) => $minutes >= 60)),
        ];
    }

    private function handoffQueueCount(int $tenantId): int
    {
        $driver = DB::connection()->getDriverName();
        $query = Conversation::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'open');

        if ($driver === 'pgsql') {
            return (int) $query
                ->whereRaw("LOWER(CAST(COALESCE(metadata, '{}'::json) AS TEXT)) like ?", ['%"needs_human":true%'])
                ->count();
        }

        return (int) $query
            ->whereRaw("LOWER(CAST(COALESCE(metadata, '{}') AS CHAR)) like ?", ['%"needs_human":true%'])
            ->count();
    }

    private function median(array $values): ?int
    {
        if ($values === []) {
            return null;
        }

        sort($values);
        $count = count($values);
        $middle = (int) floor(($count - 1) / 2);

        if ($count % 2 === 1) {
            return (int) $values[$middle];
        }

        return (int) round(($values[$middle] + $values[$middle + 1]) / 2);
    }

    private function usageStateLabel(?array $state, bool $storage = false): string
    {
        if (!$state) {
            return 'N/A';
        }

        $usage = (int) ($state['usage'] ?? 0);
        $limit = $state['limit'] ?? null;
        $status = (string) ($state['status'] ?? 'ok');

        if ($storage) {
            $formatter = app(\App\Support\StorageSizeFormatter::class);
            $usageLabel = $formatter->formatBytes($usage);
            $limitLabel = $limit !== null ? $formatter->formatBytes((int) $limit) : 'unlimited';

            return $usageLabel . ' / ' . $limitLabel . ' (' . $status . ')';
        }

        $limitLabel = $limit !== null ? number_format((int) $limit) : 'unlimited';

        return number_format($usage) . ' / ' . $limitLabel . ' (' . $status . ')';
    }

    /**
     * @return array{0:Carbon,1:Carbon}
     */
    private function resolveDateRange(Request $request): array
    {
        $fromInput = trim((string) $request->query('date_from', ''));
        $toInput = trim((string) $request->query('date_to', ''));

        try {
            $dateFrom = $fromInput !== '' ? Carbon::parse($fromInput)->startOfDay() : now()->startOfDay();
        } catch (\Throwable) {
            $dateFrom = now()->startOfDay();
        }

        try {
            $dateTo = $toInput !== '' ? Carbon::parse($toInput)->endOfDay() : now()->endOfDay();
        } catch (\Throwable) {
            $dateTo = now()->endOfDay();
        }

        if ($dateTo->lt($dateFrom)) {
            [$dateFrom, $dateTo] = [$dateTo->copy()->startOfDay(), $dateFrom->copy()->endOfDay()];
        }

        return [$dateFrom, $dateTo];
    }

    private function redirectToExpectedHost(): ?RedirectResponse
    {
        if (config('multitenancy.mode') !== 'saas') {
            return null;
        }

        $user = auth()->user();
        if (!$user) {
            return null;
        }

        $target = $this->workspaceUrlFor(request());
        $targetHost = parse_url($target, PHP_URL_HOST);
        $currentHost = request()->getHost();

        if (!$targetHost || $targetHost === $currentHost) {
            return null;
        }

        return redirect()->away($target);
    }

    private function workspaceUrlFor($request): string
    {
        $user = $request->user();
        $appUrl = (string) config('app.url');
        $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?: ($request->isSecure() ? 'https' : 'http');

        if ($user && $user->tenant) {
            return $scheme . '://' . $user->tenant->slug . '.' . config('multitenancy.saas_domain') . '/omnichannel-overview';
        }

        return route('workspace.finder');
    }
}
