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

    'central_connection' => env('CENTRAL_DB_CONNECTION', 'central'),

    'tenant_connection' => env('TENANT_DB_CONNECTION', 'tenant'),

    /*
    |----------------------------------------------------------------------
    | Runtime Isolation Mode
    |----------------------------------------------------------------------
    |
    | column   => shared database + shared schema, isolate by tenant_id
    | schema   => shared database + per-tenant schema
    | database => per-tenant database / server
    |
    */
    'runtime_mode' => env('TENANT_RUNTIME_MODE', 'column'),

    'strict' => env('TENANT_STRICT_MODE', true),

    'require_tenant_on_api' => env('TENANT_REQUIRE_API_CONTEXT', true),

    'session' => [
        'tenant_cookie' => env('TENANT_SESSION_COOKIE', true),
    ],

    'tenant_migration_paths' => [
        database_path('tenant-migrations'),
    ],

    'tenant_core_migrations' => [
        '2014_10_12_000000_create_users_table.php',
        '2014_10_12_100000_create_password_resets_table.php',
        '2019_12_14_000001_create_personal_access_tokens_table.php',
        '2026_02_03_072300_create_permissions_table.php',
        '2026_02_03_072301_create_roles_table.php',
        '2026_02_03_072302_create_model_has_permissions_table.php',
        '2026_02_03_072303_create_model_has_roles_table.php',
        '2026_02_03_072304_create_role_has_permissions_table.php',
        '2026_03_12_230000_create_user_presences_table.php',
        '2026_03_20_010200_create_companies_table.php',
        '2026_03_20_010300_create_branches_table.php',
        '2026_03_20_015000_upgrade_permission_tables_for_tenant_teams.php',
        '2026_03_20_020000_create_document_settings_table.php',
        '2026_03_20_020100_finalize_document_settings_table.php',
        '2026_03_20_020200_add_invoice_last_period_to_document_settings_table.php',
        '2026_03_21_090000_create_user_company_and_branch_access_tables.php',
        '2026_03_24_000000_create_jobs_table.php',
        '2026_03_24_000100_add_two_factor_to_users_table.php',
        '2026_03_26_000000_add_locale_to_users_table.php',
        '2026_03_28_130000_create_ai_usage_logs_table.php',
        '2026_03_28_131000_create_ai_credit_transactions_table.php',
        '2026_04_02_090100_add_billing_mode_to_ai_usage_logs_table.php',
        '2026_04_02_090200_create_tenant_byo_ai_requests_table.php',
        '2026_04_08_181736_create_activity_log_table.php',
        '2026_04_08_181737_add_event_column_to_activity_log_table.php',
        '2026_04_08_181738_add_batch_uuid_column_to_activity_log_table.php',
        '2026_04_12_130000_create_accounting_governance_tables.php',
        '2026_04_22_120000_create_document_numbering_rules_table.php',
        '2026_04_22_140000_create_document_workflow_rules_table.php',
        '2026_05_23_090000_create_notifications_tables.php',
        '2026_05_23_160000_create_user_feature_preferences_table.php',
        '2026_05_24_090000_create_user_invitations_table.php',
        '2026_05_25_210000_create_tenant_transactional_mail_tables.php',
        '2026_05_26_090000_add_delivery_mode_to_tenant_transactional_mail_settings.php',
        '2026_05_27_180000_create_tenant_payment_gateways_table.php',
        '2026_05_27_190000_create_tenant_shipping_providers_table.php',
        '2026_06_01_150000_create_stored_files_tables.php',
    ],

    'tenant_module_migration_paths' => array_values(array_filter(array_map(function (string $module) {
        $path = app_path('Modules/' . $module . '/Database/Migrations');

        return is_dir($path) ? $path : null;
    }, [
        'Affiliate',
        'Biteship',
        'Chatbot',
        'Contacts',
        'Conversations',
        'Crm',
        'Discounts',
        'EmailInbox',
        'EmailMarketing',
        'Finance',
        'Inventory',
        'LiveChat',
        'Midtrans',
        'Payments',
        'PointOfSale',
        'Products',
        'Purchases',
        'RajaOngkir',
        'Sales',
        'Shortlink',
        'SocialMedia',
        'TaskManagement',
        'Tripay',
        'Wallet',
        'WhatsAppApi',
        'WhatsAppWeb',
        'Xendit',
    ]))),

    'tenant_seeders' => [
        Database\Seeders\TenantSeeder::class,
    ],

];
