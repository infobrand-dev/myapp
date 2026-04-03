<?php

namespace App\Support;

class PlanFeature
{
    public const MULTI_COMPANY = 'multi_company';
    public const CONVERSATIONS = 'conversations';
    public const CRM = 'crm';
    public const COMMERCE = 'commerce';
    public const PROJECT_MANAGEMENT = 'project_management';
    public const LIVE_CHAT = 'live_chat';
    public const SOCIAL_MEDIA = 'social_media';
    public const CHATBOT_AI = 'chatbot_ai';
    public const CHATBOT_BYO_AI = 'chatbot_byo_ai';
    public const EMAIL_MARKETING = 'email_marketing';
    public const WHATSAPP_API = 'whatsapp_api';
    public const WHATSAPP_WEB = 'whatsapp_web';
    public const ADVANCED_REPORTS = 'advanced_reports';

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
            'contacts' => [self::CRM, self::COMMERCE],
            'crm' => [self::CRM],
            'sales' => [self::COMMERCE],
            'payments' => [self::COMMERCE],
            'products' => [self::COMMERCE],
            'inventory' => [self::COMMERCE],
            'purchases' => [self::COMMERCE],
            'discounts' => [self::COMMERCE],
            'finance' => [self::COMMERCE],
            'point-of-sale' => [self::COMMERCE],
            'task_management' => [self::PROJECT_MANAGEMENT],
            'live_chat' => [self::LIVE_CHAT],
            'social_media' => [self::SOCIAL_MEDIA],
            'chatbot' => [self::CHATBOT_AI],
            'whatsapp_api' => [self::WHATSAPP_API],
            'whatsapp_web' => [self::WHATSAPP_WEB],
            'reports' => [self::ADVANCED_REPORTS],
        ][$slug] ?? [];
    }
}
