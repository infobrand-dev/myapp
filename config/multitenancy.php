<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tenancy Mode
    |--------------------------------------------------------------------------
    |
    | 'standalone' — single-tenant, no subdomain routing (jual putus / self-hosted)
    | 'saas'       — multi-tenant via subdomain: {slug}.SAAS_DOMAIN
    |
    */
    'mode' => env('TENANT_MODE', 'standalone'),

    /*
    |--------------------------------------------------------------------------
    | SaaS Root Domain
    |--------------------------------------------------------------------------
    |
    | The apex domain under which tenant subdomains are served.
    | Only used when mode = 'saas'.
    |
    | Example: 'myapp.com' → tenant acme gets acme.myapp.com
    |
    */
    'saas_domain' => env('SAAS_DOMAIN', 'myapp.com'),

    /*
    |--------------------------------------------------------------------------
    | Platform Admin Subdomain
    |--------------------------------------------------------------------------
    |
    | Dedicated host for the SaaS owner / platform-level control plane.
    | Example: dash.myapp.com
    |
    */
    'platform_admin_subdomain' => env('PLATFORM_ADMIN_SUBDOMAIN', 'dash'),

    /*
    |--------------------------------------------------------------------------
    | Reserved Slugs
    |--------------------------------------------------------------------------
    |
    | Subdomains that tenants are not allowed to register.
    |
    */
    'reserved_slugs' => [
        'www', 'api', 'app', 'admin', 'mail', 'ftp', 'cdn',
        'dev', 'staging', 'test', 'demo', 'status', 'docs',
        'support', 'help', 'login', 'register', 'onboarding', 'dash',
        'auth', 'static', 'assets', 'media', 'billing', 'dashboard',
    ],

];
