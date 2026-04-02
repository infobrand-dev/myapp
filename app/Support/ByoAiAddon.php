<?php

namespace App\Support;

class ByoAiAddon
{
    public const REQUEST_STATUS_PENDING = 'pending_review';
    public const REQUEST_STATUS_CONTACTING_TENANT = 'contacting_tenant';
    public const REQUEST_STATUS_APPROVED = 'approved';
    public const REQUEST_STATUS_REJECTED = 'rejected';
    public const REQUEST_STATUS_NOT_ELIGIBLE = 'not_eligible';

    /**
     * @return array<int, string>
     */
    public static function providers(): array
    {
        return ['openai', 'anthropic', 'groq'];
    }

    /**
     * @return array<int, string>
     */
    public static function requestStatuses(): array
    {
        return [
            self::REQUEST_STATUS_PENDING,
            self::REQUEST_STATUS_CONTACTING_TENANT,
            self::REQUEST_STATUS_APPROVED,
            self::REQUEST_STATUS_REJECTED,
            self::REQUEST_STATUS_NOT_ELIGIBLE,
        ];
    }

    public static function featureKey(): string
    {
        return PlanFeature::CHATBOT_BYO_AI;
    }

    /**
     * @return array<int, string>
     */
    public static function limitKeys(): array
    {
        return [
            PlanLimit::BYO_CHATBOT_ACCOUNTS,
            PlanLimit::BYO_AI_REQUESTS_MONTHLY,
            PlanLimit::BYO_AI_TOKENS_MONTHLY,
        ];
    }

    /**
     * @param  array<string, mixed>  $featureOverrides
     * @param  array<string, mixed>  $limitOverrides
     * @return array{feature_overrides: array<string, mixed>, limit_overrides: array<string, mixed>}
     */
    public static function extractOverrideSubset(array $featureOverrides, array $limitOverrides): array
    {
        $subsetFeatureOverrides = [];
        if (array_key_exists(self::featureKey(), $featureOverrides)) {
            $subsetFeatureOverrides[self::featureKey()] = (bool) $featureOverrides[self::featureKey()];
        }

        $subsetLimitOverrides = [];
        foreach (self::limitKeys() as $key) {
            if (array_key_exists($key, $limitOverrides)) {
                $subsetLimitOverrides[$key] = $limitOverrides[$key];
            }
        }

        return [
            'feature_overrides' => $subsetFeatureOverrides,
            'limit_overrides' => $subsetLimitOverrides,
        ];
    }
}
