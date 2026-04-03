<?php

return [
    'task_management' => [
        'enabled' => true,
    ],
    'whatsapp_web' => [
        'enabled' => true,
        'bridge_url' => env('WHATSAPP_WEB_BRIDGE_URL', env('WHATSAPP_BRO_BRIDGE_URL', 'http://localhost:3020')),
        'webhook_token' => env('WHATSAPP_WEB_WEBHOOK_TOKEN', env('WHATSAPP_BRO_WEBHOOK_TOKEN')),
    ],
    'whatsapp_api' => [
        'enabled' => true,
    ],
    'contacts' => [
        'enabled' => true,
    ],
    'shortlink' => [
        'enabled' => true,
    ],
    'email_marketing' => [
        'enabled' => true,
    ],
    'email_inbox' => [
        'enabled' => true,
        'fetch_limit' => env('EMAIL_INBOX_FETCH_LIMIT', 20),
        'schedule_enabled' => env('EMAIL_INBOX_SCHEDULE_ENABLED', true),
    ],
    'social_media' => [
        'enabled' => true,
    ],
    'chatbot' => [
        'enabled' => true,
    ],
    'storage_efficiency' => [
        'whatsapp_webhook_payload_retention_days' => env('WHATSAPP_WEBHOOK_PAYLOAD_RETENTION_DAYS', 14),
    ],
];
