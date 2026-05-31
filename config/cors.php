<?php

$splitCsv = static function (?string $value, array $default = []): array {
    $value = trim((string) $value);

    if ($value === '') {
        return $default;
    }

    return array_values(array_filter(array_map(
        static fn (string $item): string => trim($item),
        explode(',', $value)
    )));
};

$appOrigin = (string) parse_url((string) env('APP_URL', ''), PHP_URL_SCHEME);
$appHost = (string) parse_url((string) env('APP_URL', ''), PHP_URL_HOST);
$defaultOrigins = [];

if ($appOrigin !== '' && $appHost !== '') {
    $defaultOrigins[] = $appOrigin . '://' . $appHost;
}

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => $splitCsv(env('CORS_ALLOWED_METHODS'), ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS']),

    'allowed_origins' => $splitCsv(env('CORS_ALLOWED_ORIGINS'), $defaultOrigins),

    'allowed_origins_patterns' => [],

    'allowed_headers' => $splitCsv(env('CORS_ALLOWED_HEADERS'), ['Content-Type', 'X-Requested-With', 'Authorization', 'X-CSRF-TOKEN', 'Accept', 'Origin']),

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => env('CORS_SUPPORTS_CREDENTIALS', false),

];
