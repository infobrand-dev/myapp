<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'mailtrap' => [
        'webhook_secret' => env('MAILTRAP_WEBHOOK_SECRET'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'meta' => [
        'graph_version' => env('META_GRAPH_VERSION', 'v22.0'),
        'app_id' => env('META_APP_ID'),
        'app_secret' => env('META_APP_SECRET'),
        'page_token' => env('META_PAGE_TOKEN'),
        'page_id' => env('META_PAGE_ID'),
        'ig_business_id' => env('META_IG_BUSINESS_ID'),
        'verify_token' => env('META_VERIFY_TOKEN', 'changeme'),
        'oauth_scopes' => array_values(array_filter(array_map('trim', explode(',', (string) env('META_OAUTH_SCOPES', 'pages_show_list,pages_manage_metadata,pages_messaging,instagram_basic,instagram_manage_messages,business_management'))))),
    ],

    'x_api' => [
        'base_url' => env('X_API_BASE_URL', 'https://api.x.com'),
        'authorize_url' => env('X_API_AUTHORIZE_URL', 'https://x.com/i/oauth2/authorize'),
        'token_url' => env('X_API_TOKEN_URL', 'https://api.x.com/2/oauth2/token'),
        'client_id' => env('X_API_CLIENT_ID'),
        'client_secret' => env('X_API_CLIENT_SECRET'),
        'webhook_environment' => env('X_API_WEBHOOK_ENVIRONMENT'),
        'webhook_secret' => env('X_API_WEBHOOK_SECRET'),
        'internal_enabled' => filter_var(env('X_API_INTERNAL_ENABLED', false), FILTER_VALIDATE_BOOL),
        'tenant_beta_enabled' => filter_var(env('X_API_TENANT_BETA_ENABLED', true), FILTER_VALIDATE_BOOL),
        'oauth_scopes' => array_values(array_filter(array_map('trim', explode(',', (string) env('X_API_OAUTH_SCOPES', 'users.read,dm.read,dm.write,offline.access'))))),
    ],

    'tiktok_api' => [
        'base_url' => env('TIKTOK_API_BASE_URL', 'https://open.tiktokapis.com'),
        'authorize_url' => env('TIKTOK_API_AUTHORIZE_URL', 'https://www.tiktok.com/v2/auth/authorize/'),
        'token_url' => env('TIKTOK_API_TOKEN_URL', 'https://open.tiktokapis.com/v2/oauth/token/'),
        'client_key' => env('TIKTOK_API_CLIENT_KEY'),
        'client_secret' => env('TIKTOK_API_CLIENT_SECRET'),
        'oauth_scopes' => array_values(array_filter(array_map('trim', explode(',', (string) env('TIKTOK_API_OAUTH_SCOPES', 'user.info.profile,user.info.stats,video.list'))))),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'embeddings_model' => env('OPENAI_EMBEDDINGS_MODEL', 'text-embedding-3-small'),
        'embeddings_dimensions' => (int) env('OPENAI_EMBEDDINGS_DIMENSIONS', 1536),
        'credit_token_unit' => (int) env('OPENAI_CREDIT_TOKEN_UNIT', 1000),
        'input_rate_per_million_tokens' => (float) env('OPENAI_INPUT_RATE_PER_MILLION_TOKENS', 0),
        'output_rate_per_million_tokens' => (float) env('OPENAI_OUTPUT_RATE_PER_MILLION_TOKENS', 0),
    ],

    'ai_credits' => [
        'currency' => env('AI_CREDIT_CURRENCY', 'IDR'),
        'unit_tokens' => (int) env('AI_CREDIT_UNIT_TOKENS', 1000),
        'price_per_credit' => (int) env('AI_CREDIT_PRICE_PER_CREDIT', 100),
        'pack_options' => array_values(array_filter(array_map('intval', explode(',', (string) env('AI_CREDIT_PACK_OPTIONS', '500,1000'))))),
    ],

    'midtrans' => [
        'is_active' => env('MIDTRANS_IS_ACTIVE', false),
        'environment' => env('MIDTRANS_ENVIRONMENT', 'sandbox'),
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
        'merchant_id' => env('MIDTRANS_MERCHANT_ID'),
        'enabled_payments' => array_values(array_filter(array_map('trim', explode(',', (string) env('MIDTRANS_ENABLED_PAYMENTS', ''))))),
    ],

    'platform_manual_payment' => [
        'enabled' => env('PLATFORM_MANUAL_PAYMENT_ENABLED', false),
        'bank_name' => env('PLATFORM_MANUAL_PAYMENT_BANK_NAME'),
        'account_name' => env('PLATFORM_MANUAL_PAYMENT_ACCOUNT_NAME'),
        'account_number' => env('PLATFORM_MANUAL_PAYMENT_ACCOUNT_NUMBER'),
        'review_sla_hours' => (int) env('PLATFORM_MANUAL_PAYMENT_REVIEW_SLA_HOURS', 24),
    ],

    'wa_cloud' => [
        'base_url' => env('WA_CLOUD_BASE_URL', 'https://graph.facebook.com/v22.0'),
        'app_id' => env('WA_CLOUD_APP_ID', env('META_APP_ID')),
        'phone_number_id' => env('WA_CLOUD_PHONE_NUMBER_ID'),
        'token' => env('WA_CLOUD_ACCESS_TOKEN'),
        'verify_token' => env('WA_CLOUD_VERIFY_TOKEN', 'changeme'),
        'app_secret' => env('WA_CLOUD_APP_SECRET'),
        'allowed_domains' => env('WA_CLOUD_ALLOWED_DOMAINS', ''),
    ],

    'platform_affiliate' => [
        'cookie_days' => (int) env('PLATFORM_AFFILIATE_COOKIE_DAYS', 30),
        'first_purchase_only' => filter_var(env('PLATFORM_AFFILIATE_FIRST_PURCHASE_ONLY', true), FILTER_VALIDATE_BOOL),
        'default_commission_type' => env('PLATFORM_AFFILIATE_DEFAULT_COMMISSION_TYPE', 'percentage'),
        'default_commission_rate' => (float) env('PLATFORM_AFFILIATE_DEFAULT_COMMISSION_RATE', 20),
        'payout_schedule' => env('PLATFORM_AFFILIATE_PAYOUT_SCHEDULE', 'monthly'),
        'payout_day' => (int) env('PLATFORM_AFFILIATE_PAYOUT_DAY', 10),
        'payout_methods' => array_values(array_filter(array_map('trim', explode(',', (string) env('PLATFORM_AFFILIATE_PAYOUT_METHODS', 'bank_transfer'))))),
    ],

];
