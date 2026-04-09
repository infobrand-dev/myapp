<?php

namespace App\Support;

class PlanFeature
{
    public const MULTI_COMPANY = 'multi_company';
    public const CONVERSATIONS = 'conversations';
    public const CRM = 'crm';
    public const ACCOUNTING = 'accounting';
    public const COMMERCE = 'commerce';
    public const PROJECT_MANAGEMENT = 'project_management';
    public const LIVE_CHAT = 'live_chat';
    public const SOCIAL_MEDIA = 'social_media';
    public const CHATBOT_AI = 'chatbot_ai';
    public const CHATBOT_BYO_AI = 'chatbot_byo_ai';
    public const EMAIL_MARKETING = 'email_marketing';
    public const WHATSAPP_API = 'whatsapp_api';
    public const WHATSAPP_WEB = 'whatsapp_web';
    public const PURCHASES = 'purchases';
    public const INVENTORY = 'inventory';
    public const ADVANCED_REPORTS = 'advanced_reports';
    public const POINT_OF_SALE = 'point_of_sale';

    public static function moduleFeatureForSlug(string $slug): ?string
    {
        return self::moduleFeaturesForSlug($slug)[0] ?? null;
    }

    /**
     * @return array<int, string>
     */
    public static function moduleFeaturesForSlug(string $slug): array
    {
        return [
            'conversations' => [self::CONVERSATIONS],
            'contacts' => [self::CRM, self::ACCOUNTING],
            'crm' => [self::CRM],
            'sales' => [self::ACCOUNTING],
            'payments' => [self::ACCOUNTING],
            'products' => [self::ACCOUNTING],
            'inventory' => [self::INVENTORY],
            'purchases' => [self::PURCHASES],
            'discounts' => [self::POINT_OF_SALE],
            'finance' => [self::ACCOUNTING],
            'point-of-sale' => [self::POINT_OF_SALE],
            'task_management' => [self::PROJECT_MANAGEMENT],
            'live_chat' => [self::LIVE_CHAT],
            'social_media' => [self::SOCIAL_MEDIA],
            'chatbot' => [self::CHATBOT_AI],
            'whatsapp_api' => [self::WHATSAPP_API],
            'whatsapp_web' => [self::WHATSAPP_WEB],
            'reports' => [self::ACCOUNTING],
        ][$slug] ?? [];
    }

    /**
     * @return array{any?: array<int, string>, all?: array<int, string>}
     */
    public static function moduleFeatureRequirement(string $slug): array
    {
        return match ($slug) {
            'inventory' => ['all' => [self::ACCOUNTING, self::INVENTORY]],
            'purchases' => ['all' => [self::ACCOUNTING, self::PURCHASES]],
            'discounts' => ['all' => [self::ACCOUNTING, self::POINT_OF_SALE]],
            'point-of-sale' => ['all' => [self::ACCOUNTING, self::POINT_OF_SALE]],
            default => self::moduleFeaturesForSlug($slug) !== []
                ? ['any' => self::moduleFeaturesForSlug($slug)]
                : [],
        };
    }

    /**
     * @return array<int, string>
     */
    public static function featureKeyCandidates(string $feature): array
    {
        return match ($feature) {
            self::ACCOUNTING, self::COMMERCE => [self::ACCOUNTING, self::COMMERCE],
            default => [$feature],
        };
    }
}
