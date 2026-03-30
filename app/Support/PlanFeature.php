<?php

namespace App\Support;

class PlanFeature
{
    public const MULTI_COMPANY = 'multi_company';
    public const CONVERSATIONS = 'conversations';
    public const CRM = 'crm';
    public const LIVE_CHAT = 'live_chat';
    public const SOCIAL_MEDIA = 'social_media';
    public const CHATBOT_AI = 'chatbot_ai';
    public const EMAIL_MARKETING = 'email_marketing';
    public const WHATSAPP_API = 'whatsapp_api';
    public const WHATSAPP_WEB = 'whatsapp_web';
    public const ADVANCED_REPORTS = 'advanced_reports';

    public static function moduleFeatureForSlug(string $slug): ?string
    {
        return [
            'conversations' => self::CONVERSATIONS,
            'crm' => self::CRM,
            'live_chat' => self::LIVE_CHAT,
            'social_media' => self::SOCIAL_MEDIA,
            'chatbot' => self::CHATBOT_AI,
            'whatsapp_api' => self::WHATSAPP_API,
            'whatsapp_web' => self::WHATSAPP_WEB,
        ][$slug] ?? null;
    }
}
