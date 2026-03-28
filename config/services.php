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

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'credit_token_unit' => (int) env('OPENAI_CREDIT_TOKEN_UNIT', 1000),
        'input_rate_per_million_tokens' => (float) env('OPENAI_INPUT_RATE_PER_MILLION_TOKENS', 0),
        'output_rate_per_million_tokens' => (float) env('OPENAI_OUTPUT_RATE_PER_MILLION_TOKENS', 0),
    ],

    'midtrans' => [
        'is_active' => env('MIDTRANS_IS_ACTIVE', false),
        'environment' => env('MIDTRANS_ENVIRONMENT', 'sandbox'),
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
        'merchant_id' => env('MIDTRANS_MERCHANT_ID'),
        'enabled_payments' => array_values(array_filter(array_map('trim', explode(',', (string) env('MIDTRANS_ENABLED_PAYMENTS', ''))))),
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

];
