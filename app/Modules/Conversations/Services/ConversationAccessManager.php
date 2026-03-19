<?php

namespace App\Modules\Conversations\Services;

use App\Models\User;
use App\Modules\Conversations\Contracts\ConversationAccessRegistry;
use App\Modules\Conversations\Models\Conversation;
use Illuminate\Database\Eloquent\Builder;

class ConversationAccessManager implements ConversationAccessRegistry
{
    /**
     * @var array<string, callable>
     */
    private array $viewRules = [];

    /**
     * @var array<string, callable>
     */
    private array $participateRules = [];

    /**
     * @var array<string, callable>
     */
    private array $visibilityScopes = [];

    public function registerViewRule(string $key, callable $rule): void
    {
        $this->viewRules[$key] = $rule;
    }

    public function registerParticipateRule(string $key, callable $rule): void
    {
        $this->participateRules[$key] = $rule;
    }

    public function registerVisibilityScope(string $key, callable $scope): void
    {
        $this->visibilityScopes[$key] = $scope;
    }

    public function canView(Conversation $conversation, User $user): bool
    {
        foreach ($this->viewRules as $rule) {
            if ($rule($conversation, $user) === true) {
                return true;
            }
        }

        return false;
    }

    public function canParticipate(Conversation $conversation, User $user): bool
    {
        foreach ($this->participateRules as $rule) {
            if ($rule($conversation, $user) === true) {
                return true;
            }
        }

        return false;
    }

    public function applyVisibilityScope(Builder $query, User $user): Builder
    {
        foreach ($this->visibilityScopes as $scope) {
            $scope($query, $user);
        }

        return $query;
    }
}
