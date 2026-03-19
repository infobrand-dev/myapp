<?php

namespace App\Modules\Conversations\Contracts;

use App\Models\User;
use App\Modules\Conversations\Models\Conversation;
use Illuminate\Database\Eloquent\Builder;

interface ConversationAccessRegistry
{
    public function registerViewRule(string $key, callable $rule): void;

    public function registerParticipateRule(string $key, callable $rule): void;

    public function registerVisibilityScope(string $key, callable $scope): void;

    public function canView(Conversation $conversation, User $user): bool;

    public function canParticipate(Conversation $conversation, User $user): bool;

    public function applyVisibilityScope(Builder $query, User $user): Builder;
}
