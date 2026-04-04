<?php

namespace App\Support;

class PlanProductLineMap
{
    public static function canonicalProductLine(?string $productLine): ?string
    {
        if (!is_string($productLine) || trim($productLine) === '') {
            return $productLine;
        }

        return match (trim($productLine)) {
            'commerce', 'accounting' => 'accounting',
            default => trim($productLine),
        };
    }

    /**
     * @return array<int, string>
     */
    public static function productLineCandidates(string $productLine): array
    {
        $canonical = self::canonicalProductLine($productLine) ?: $productLine;

        return match ($canonical) {
            'accounting' => ['accounting', 'commerce'],
            default => [$canonical],
        };
    }

    /**
     * Limits that apply to the workspace globally.
     * In phase 1 multi-plan, use the highest active entitlement.
     *
     * @return array<int, string>
     */
    public static function sharedLimitKeys(): array
    {
        return [
            PlanLimit::COMPANIES,
            PlanLimit::BRANCHES,
            PlanLimit::USERS,
            PlanLimit::TOTAL_STORAGE_BYTES,
            PlanLimit::AI_CREDITS_MONTHLY,
        ];
    }

    public static function isSharedLimit(string $key): bool
    {
        return in_array($key, self::sharedLimitKeys(), true);
    }

    public static function featureProductLine(string $feature): ?string
    {
        return match ($feature) {
            PlanFeature::CONVERSATIONS,
            PlanFeature::LIVE_CHAT,
            PlanFeature::SOCIAL_MEDIA,
            PlanFeature::CHATBOT_AI,
            PlanFeature::CHATBOT_BYO_AI,
            PlanFeature::WHATSAPP_API,
            PlanFeature::WHATSAPP_WEB => 'omnichannel',

            PlanFeature::CRM,
            PlanFeature::EMAIL_MARKETING => 'crm',

            PlanFeature::COMMERCE,
            PlanFeature::POINT_OF_SALE => 'accounting',
            PlanFeature::PROJECT_MANAGEMENT => 'project_management',

            default => null,
        };
    }

    public static function limitProductLine(string $key): ?string
    {
        return match ($key) {
            PlanLimit::PRODUCTS => 'accounting',

            PlanLimit::WHATSAPP_INSTANCES,
            PlanLimit::SOCIAL_ACCOUNTS,
            PlanLimit::LIVE_CHAT_WIDGETS,
            PlanLimit::CHATBOT_ACCOUNTS,
            PlanLimit::WA_BLAST_RECIPIENTS_MONTHLY,
            PlanLimit::CHATBOT_KNOWLEDGE_DOCUMENTS,
            PlanLimit::BYO_CHATBOT_ACCOUNTS,
            PlanLimit::BYO_AI_REQUESTS_MONTHLY,
            PlanLimit::BYO_AI_TOKENS_MONTHLY,
            PlanLimit::AUTOMATION_WORKFLOWS,
            PlanLimit::AUTOMATION_EXECUTIONS_MONTHLY => 'omnichannel',

            PlanLimit::EMAIL_INBOX_ACCOUNTS,
            PlanLimit::EMAIL_CAMPAIGNS,
            PlanLimit::EMAIL_RECIPIENTS_MONTHLY => 'crm',

            default => null,
        };
    }

    /**
     * @return array<int, string>
     */
    public static function limitProductLineCandidates(string $key): array
    {
        return match ($key) {
            PlanLimit::CONTACTS => ['accounting', 'commerce', 'crm'],
            default => array_values(array_filter([self::limitProductLine($key)])),
        };
    }
}
