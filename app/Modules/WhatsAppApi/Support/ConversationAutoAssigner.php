<?php

namespace App\Modules\WhatsAppApi\Support;

use App\Models\UserPresence;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationParticipant;
use App\Modules\WhatsAppApi\Models\WhatsAppInstance;

class ConversationAutoAssigner
{
    public function assignIfEligible(Conversation $conversation, ?WhatsAppInstance $instance): bool
    {
        if (!$instance || !$conversation->exists) {
            return false;
        }

        $settings = is_array($instance->settings) ? $instance->settings : [];
        if (!(bool) ($settings['auto_assignment_enabled'] ?? true)) {
            return false;
        }

        if ($conversation->owner_id && (!$conversation->locked_until || $conversation->locked_until->isFuture())) {
            return false;
        }

        $candidate = $this->pickCandidate($instance);
        if (!$candidate) {
            return false;
        }

        $now = now();
        $metadata = is_array($conversation->metadata) ? $conversation->metadata : [];
        $metadata['auto_assigned'] = true;
        $metadata['auto_assigned_at'] = $now->toIso8601String();
        $metadata['auto_assigned_user_id'] = $candidate->id;

        $conversation->update([
            'owner_id' => $candidate->id,
            'claimed_at' => $now,
            'locked_until' => $now->copy()->addMinutes((int) config('conversations.lock_minutes', 30)),
            'metadata' => $metadata,
        ]);

        if (class_exists(ConversationParticipant::class)) {
            ConversationParticipant::firstOrCreate(
                ['conversation_id' => $conversation->id, 'user_id' => $candidate->id],
                ['role' => 'owner', 'invited_by' => $candidate->id, 'invited_at' => $now]
            );
        }

        return true;
    }

    private function pickCandidate(WhatsAppInstance $instance)
    {
        $users = $instance->users()->get();
        if ($users->isEmpty()) {
            return null;
        }

        $presenceMap = UserPresence::statusMapForUsers($users->pluck('id'));
        $eligibleUsers = $users->filter(function ($user) use ($presenceMap) {
            $status = $presenceMap[$user->id] ?? 'offline';
            return in_array($status, UserPresence::availabilityStatuses(), true);
        });

        if ($eligibleUsers->isEmpty()) {
            return null;
        }

        $workloads = Conversation::query()
            ->where('channel', 'wa_api')
            ->where('instance_id', $instance->id)
            ->whereNotNull('owner_id')
            ->where(function ($query) {
                $query->whereNull('locked_until')
                    ->orWhere('locked_until', '>', now());
            })
            ->selectRaw('owner_id, COUNT(*) as total')
            ->groupBy('owner_id')
            ->pluck('total', 'owner_id');

        return $eligibleUsers->sortBy(function ($user) use ($workloads, $presenceMap) {
            $isSuperAdmin = method_exists($user, 'hasRole') && $user->hasRole('Super-admin');
            $presenceWeight = ($presenceMap[$user->id] ?? UserPresence::STATUS_OFFLINE) === UserPresence::STATUS_ONLINE ? 0 : 1;
            $load = (int) ($workloads[$user->id] ?? 0);

            return sprintf('%d-%d-%06d-%010d', $presenceWeight, $isSuperAdmin ? 1 : 0, $load, (int) $user->id);
        })->first();
    }
}
