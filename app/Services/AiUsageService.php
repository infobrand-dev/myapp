<?php

namespace App\Services;

use App\Models\AiCreditTransaction;
use App\Models\AiUsageLog;
use App\Support\PlanLimit;
use App\Support\TenantPlanManager;
use Illuminate\Support\Facades\Schema;

class AiUsageService
{
    public function __construct(
        private readonly TenantPlanManager $plans,
        private readonly AiCreditPricingService $pricing,
    ) {
    }

    public function hasCreditsRemaining(int $tenantId): bool
    {
        $summary = $this->summary($tenantId);

        return $summary['remaining'] === null || $summary['remaining'] > 0;
    }

    public function creditsUsedThisMonth(int $tenantId): int
    {
        if (!Schema::hasTable('ai_usage_logs')) {
            return 0;
        }

        return (int) AiUsageLog::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('used_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('credits_used');
    }

    public function topUpCreditsAvailable(int $tenantId): int
    {
        if (!Schema::hasTable('ai_credit_transactions')) {
            return 0;
        }

        return (int) AiCreditTransaction::query()
            ->where('tenant_id', $tenantId)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->sum('credits');
    }

    public function creditsForTokens(int $totalTokens): int
    {
        $unit = $this->pricing->unitTokens();

        return max(1, (int) ceil(max(0, $totalTokens) / $unit));
    }

    public function estimateCost(int $promptTokens, int $completionTokens): ?float
    {
        $inputRate = (float) config('services.openai.input_rate_per_million_tokens', 0);
        $outputRate = (float) config('services.openai.output_rate_per_million_tokens', 0);

        if ($inputRate <= 0 && $outputRate <= 0) {
            return null;
        }

        $cost = (($promptTokens / 1000000) * $inputRate) + (($completionTokens / 1000000) * $outputRate);

        return round($cost, 6);
    }

    public function recordUsage(array $attributes): ?AiUsageLog
    {
        if (!Schema::hasTable('ai_usage_logs')) {
            return null;
        }

        $promptTokens = max(0, (int) ($attributes['prompt_tokens'] ?? 0));
        $completionTokens = max(0, (int) ($attributes['completion_tokens'] ?? 0));
        $totalTokens = max(0, (int) ($attributes['total_tokens'] ?? ($promptTokens + $completionTokens)));

        if ($totalTokens <= 0) {
            return null;
        }

        return AiUsageLog::query()->create([
            'tenant_id' => (int) $attributes['tenant_id'],
            'source_module' => (string) ($attributes['source_module'] ?? 'chatbot'),
            'source_type' => (string) ($attributes['source_type'] ?? 'unknown'),
            'source_id' => isset($attributes['source_id']) ? (int) $attributes['source_id'] : null,
            'chatbot_account_id' => isset($attributes['chatbot_account_id']) ? (int) $attributes['chatbot_account_id'] : null,
            'provider' => (string) ($attributes['provider'] ?? 'openai'),
            'model' => (string) ($attributes['model'] ?? ''),
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $totalTokens,
            'credits_used' => $this->creditsForTokens($totalTokens),
            'estimated_cost' => $attributes['estimated_cost'] ?? $this->estimateCost($promptTokens, $completionTokens),
            'metadata' => $attributes['metadata'] ?? null,
            'used_at' => $attributes['used_at'] ?? now(),
        ]);
    }

    public function summary(int $tenantId): array
    {
        $included = $this->plans->limit(PlanLimit::AI_CREDITS_MONTHLY, $tenantId);
        $topUp = $this->topUpCreditsAvailable($tenantId);
        $used = $this->creditsUsedThisMonth($tenantId);

        $totalAvailable = $included === null ? null : max($included + $topUp, 0);

        return [
            'included' => $included,
            'top_up' => $topUp,
            'used' => $used,
            'available' => $totalAvailable,
            'remaining' => $totalAvailable === null ? null : max($totalAvailable - $used, 0),
        ];
    }
}
