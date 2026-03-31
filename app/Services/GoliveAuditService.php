<?php

namespace App\Services;

use App\Models\SubscriptionPlan;
use App\Support\ModuleFilesystemAudit;
use App\Support\ModuleManager;
use App\Support\PlanFeature;
use App\Support\PlanLimit;
use Illuminate\Support\Facades\Schema;

class GoliveAuditService
{
    public function __construct(
        private readonly AiCreditPricingService $aiPricing,
        private readonly ModuleManager $modules,
        private readonly ModuleFilesystemAudit $moduleFilesystemAudit
    ) {
    }

    public function run(): array
    {
        $aiPricing = $this->aiPricing->snapshot();

        $checks = [
            $this->check('app_env', 'APP_ENV production', $this->isProduction(), $this->env('app.env'), 'Set APP_ENV=production in production runtime.', 'fail'),
            $this->check('app_debug', 'APP_DEBUG disabled', !$this->env('app.debug'), $this->boolString($this->env('app.debug')), 'Set APP_DEBUG=false before launch.', 'fail'),
            $this->check('app_url_https', 'APP_URL https', str_starts_with((string) $this->env('app.url'), 'https://'), (string) $this->env('app.url'), 'Use the canonical https URL for signed links, emails, and redirects.', 'fail'),
            $this->check('tenant_mode', 'TENANT_MODE saas', $this->env('multitenancy.mode') === 'saas', (string) $this->env('multitenancy.mode'), 'Set TENANT_MODE=saas for tenant subdomain login flow.', 'fail'),
            $this->check('saas_domain', 'SAAS_DOMAIN set', $this->filled($this->env('multitenancy.saas_domain')), (string) $this->env('multitenancy.saas_domain'), 'Set the apex SaaS domain used for tenant subdomains.', 'fail'),
            $this->check('platform_admin_subdomain', 'PLATFORM_ADMIN_SUBDOMAIN set', $this->filled($this->env('multitenancy.platform_admin_subdomain')), (string) $this->env('multitenancy.platform_admin_subdomain'), 'Set the reserved control-plane subdomain, for example dash.', 'fail'),
            $this->check('session_secure', 'SESSION_SECURE_COOKIE enabled', (bool) $this->env('session.secure'), $this->boolString($this->env('session.secure')), 'Enable secure cookies in production.', 'fail'),
            $this->check('session_domain', 'SESSION_DOMAIN configured', $this->filled($this->env('session.domain')), (string) $this->env('session.domain'), 'Set SESSION_DOMAIN for apex/subdomain auth continuity.', 'fail'),
            $this->check('session_driver', 'SESSION_DRIVER production-ready', in_array((string) $this->env('session.driver'), ['database', 'redis', 'cookie', 'file'], true), (string) $this->env('session.driver'), 'Database or Redis is recommended if you run multiple app instances.', (string) $this->env('session.driver') === 'file' ? 'warn' : 'pass'),
            $this->check('cache_driver', 'CACHE_DRIVER production-ready', in_array((string) config('cache.default'), ['database', 'redis', 'file'], true), (string) config('cache.default'), 'Redis or database cache is recommended for multi-instance deployments.', (string) config('cache.default') === 'file' ? 'warn' : 'pass'),
            $this->check('queue_connection', 'QUEUE_CONNECTION async', in_array(config('queue.default'), ['database', 'redis', 'sqs', 'beanstalkd'], true), (string) config('queue.default'), 'Use async queue for mails, webhook work, and background jobs.', 'fail'),
            $this->check('jobs_table', 'jobs table exists', Schema::hasTable('jobs'), $this->yesNo('jobs'), 'Run migrations for queued jobs.', 'fail'),
            $this->check('job_batches_table', 'job_batches table exists', Schema::hasTable('job_batches'), $this->yesNo('job_batches'), 'Run migrations for queued job batches.', 'fail'),
            $this->check('failed_jobs_table', 'failed_jobs table exists', Schema::hasTable('failed_jobs'), $this->yesNo('failed_jobs'), 'Run migrations for failed jobs tracking.', 'fail'),
            $this->check('platform_orders_table', 'platform_plan_orders table exists', Schema::hasTable('platform_plan_orders'), $this->yesNo('platform_plan_orders'), 'Run billing platform migrations before launch.', 'fail'),
            $this->check('platform_invoices_table', 'platform_invoices table exists', Schema::hasTable('platform_invoices'), $this->yesNo('platform_invoices'), 'Run billing platform migrations before launch.', 'fail'),
            $this->check('platform_invoice_items_table', 'platform_invoice_items table exists', Schema::hasTable('platform_invoice_items'), $this->yesNo('platform_invoice_items'), 'Run billing platform item migration so invoices can carry plan, top up, and add-on products safely.', 'fail'),
            $this->check('platform_payments_table', 'platform_payments table exists', Schema::hasTable('platform_payments'), $this->yesNo('platform_payments'), 'Run billing platform migrations before launch.', 'fail'),
            $this->check('ai_usage_logs_table', 'ai_usage_logs table exists', Schema::hasTable('ai_usage_logs'), $this->yesNo('ai_usage_logs'), 'Run AI usage migration so AI Credits can be tracked and enforced.', 'fail'),
            $this->check('ai_credit_transactions_table', 'ai_credit_transactions table exists', Schema::hasTable('ai_credit_transactions'), $this->yesNo('ai_credit_transactions'), 'Run AI credit transaction migration if you plan to sell AI Credit top ups.', 'warn'),
            $this->check('ai_credit_pricing_settings_table', 'ai_credit_pricing_settings table exists', Schema::hasTable('ai_credit_pricing_settings'), $this->yesNo('ai_credit_pricing_settings'), 'Run AI credit pricing settings migration so launch pricing can be managed from control plane.', 'warn'),
            $this->check('ai_credit_currency', 'AI Credit currency fixed to IDR', strtoupper((string) ($aiPricing['currency'] ?? '')) === 'IDR', (string) ($aiPricing['currency'] ?? '-'), 'Keep launch pricing in IDR for consistency with billing copy and tenant messaging.', 'warn'),
            $this->check('ai_credit_unit_tokens', 'AI Credit token unit configured', (int) ($aiPricing['unit_tokens'] ?? 0) > 0, (string) ($aiPricing['unit_tokens'] ?? '-'), 'Set how many internal tokens equal 1 AI Credit. Launch default is 1000.', 'fail'),
            $this->check('ai_credit_price_per_credit', 'AI Credit base price configured', (int) ($aiPricing['price_per_credit'] ?? 0) > 0, (string) ($aiPricing['price_per_credit'] ?? '-'), 'Set the base launch sell price per AI Credit before opening AI top-up offers.', 'fail'),
            $this->check('ai_credit_pack_options', 'AI Credit launch packs configured', !empty($aiPricing['pack_options']), empty($aiPricing['pack_options']) ? '-' : implode(', ', $aiPricing['pack_options']), 'Configure at least one AI Credit top-up pack such as 500 and 1000.', 'warn'),
            $this->check('platform_affiliates_table', 'platform_affiliates table exists', Schema::hasTable('platform_affiliates'), $this->yesNo('platform_affiliates'), 'Run affiliate migrations if invite-only partner links will be used in production.', 'warn'),
            $this->check('platform_affiliate_referrals_table', 'platform_affiliate_referrals table exists', Schema::hasTable('platform_affiliate_referrals'), $this->yesNo('platform_affiliate_referrals'), 'Run affiliate referral migrations if partner attribution should be tracked in production.', 'warn'),
            $this->check('affiliate_cookie_days', 'Affiliate cookie days configured', (int) config('services.platform_affiliate.cookie_days') > 0, (string) config('services.platform_affiliate.cookie_days'), 'Set affiliate attribution cookie duration. Current launch decision is 30 days.', 'warn'),
            $this->check('affiliate_first_purchase_only', 'Affiliate first purchase only rule set', is_bool(config('services.platform_affiliate.first_purchase_only')), $this->boolString(config('services.platform_affiliate.first_purchase_only')), 'Keep affiliate commission rule explicit. Current launch decision is first purchase only.', 'warn'),
            $this->check('affiliate_commission_type', 'Affiliate default commission type configured', in_array((string) config('services.platform_affiliate.default_commission_type'), ['percentage', 'flat'], true), (string) config('services.platform_affiliate.default_commission_type'), 'Use percentage or flat as the default affiliate commission type.', 'warn'),
            $this->check('affiliate_commission_rate', 'Affiliate default commission rate configured', (float) config('services.platform_affiliate.default_commission_rate') > 0, (string) config('services.platform_affiliate.default_commission_rate'), 'Set a non-zero default affiliate commission rate before sharing partner links.', 'warn'),
            $this->check('affiliate_payout_schedule', 'Affiliate payout schedule configured', $this->filled(config('services.platform_affiliate.payout_schedule')), (string) config('services.platform_affiliate.payout_schedule'), 'Set the affiliate payout cadence shown on the partner info page.', 'warn'),
            $this->check('affiliate_payout_day', 'Affiliate payout day configured', (int) config('services.platform_affiliate.payout_day') >= 1 && (int) config('services.platform_affiliate.payout_day') <= 31, (string) config('services.platform_affiliate.payout_day'), 'Use a valid monthly payout day between 1 and 31.', 'warn'),
            $this->check('affiliate_payout_methods', 'Affiliate payout methods configured', !empty(config('services.platform_affiliate.payout_methods', [])), empty(config('services.platform_affiliate.payout_methods', [])) ? '-' : implode(', ', (array) config('services.platform_affiliate.payout_methods', [])), 'List at least one payout method for partner transparency.', 'warn'),
            $this->check('sentry_dsn', 'Sentry DSN configured', $this->filled(config('sentry.dsn')), $this->masked((string) config('sentry.dsn')), 'Fill SENTRY_LARAVEL_DSN so production errors can be captured.', 'warn'),
            $this->check('sentry_environment', 'Sentry environment configured', $this->filled(config('sentry.environment')), (string) config('sentry.environment'), 'Set SENTRY_ENVIRONMENT so production issues are grouped correctly.', 'warn'),
            $this->check('sentry_release', 'Sentry release configured', $this->filled(config('sentry.release')), (string) config('sentry.release'), 'Set SENTRY_RELEASE so issues can be tied to a deploy version.', 'warn'),
            $this->check('mail_mailer', 'MAIL_MAILER not local array/log', !in_array(config('mail.default'), ['array', 'log'], true), (string) config('mail.default'), 'Use SMTP or another real transport in production.', 'fail'),
            $this->check('mail_host', 'MAIL host configured', $this->isMeaningful((string) config('mail.mailers.smtp.host'), ['mailhog', 'smtp.example.com', '']), (string) config('mail.mailers.smtp.host'), 'Set the real SMTP host used by production mail.', 'fail'),
            $this->check('mail_from', 'MAIL from configured', $this->isMeaningful((string) config('mail.from.address'), ['hello@example.com', 'no-reply@example.com', '']), (string) config('mail.from.address'), 'Set a verified sender address.', 'fail'),
            $this->check('midtrans_active', 'Midtrans active', (bool) config('services.midtrans.is_active'), $this->boolString(config('services.midtrans.is_active')), 'Enable platform billing gateway before launch.', 'fail'),
            $this->check('midtrans_server_key', 'Midtrans server key configured', $this->filled(config('services.midtrans.server_key')), $this->masked((string) config('services.midtrans.server_key')), 'Fill MIDTRANS_SERVER_KEY in production .env.', 'fail'),
            $this->check('midtrans_client_key', 'Midtrans client key configured', $this->filled(config('services.midtrans.client_key')), $this->masked((string) config('services.midtrans.client_key')), 'Fill MIDTRANS_CLIENT_KEY in production .env.', 'fail'),
            $this->check('meta_app_id', 'Meta app id configured', $this->filled(config('services.meta.app_id')), $this->masked((string) config('services.meta.app_id')), 'Fill META_APP_ID to enable platform-owned social media OAuth.', 'warn'),
            $this->check('meta_app_secret', 'Meta app secret configured', $this->filled(config('services.meta.app_secret')), $this->masked((string) config('services.meta.app_secret')), 'Fill META_APP_SECRET to enable platform-owned social media OAuth.', 'warn'),
            $this->check('wa_verify_token', 'WA verify token changed', $this->isMeaningful((string) config('services.wa_cloud.verify_token'), ['changeme', '']), (string) config('services.wa_cloud.verify_token'), 'Replace default WA webhook verify token.', 'fail'),
            $this->check('meta_verify_token', 'Meta verify token changed', $this->isMeaningful((string) config('services.meta.verify_token'), ['changeme', '']), (string) config('services.meta.verify_token'), 'Replace default Meta webhook verify token.', 'fail'),
        ];

        $checks = array_merge($checks, $this->planCatalogChecks(), $this->moduleFilesystemChecks(), $this->moduleDatabaseChecks());

        $stats = [
            'pass' => count(array_filter($checks, fn (array $check) => $check['status'] === 'pass')),
            'warn' => count(array_filter($checks, fn (array $check) => $check['status'] === 'warn')),
            'fail' => count(array_filter($checks, fn (array $check) => $check['status'] === 'fail')),
            'total' => count($checks),
        ];

        return [
            'checks' => $checks,
            'stats' => $stats,
            'ready' => $stats['fail'] === 0,
            'manual_checks' => $this->manualChecks(),
        ];
    }

    private function check(string $key, string $label, bool $ok, string $value, string $hint, string $statusWhenNotOk = 'fail'): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'ok' => $ok,
            'status' => $ok ? 'pass' : $statusWhenNotOk,
            'value' => $value !== '' ? $value : '-',
            'hint' => $hint,
        ];
    }

    private function env(string $path)
    {
        return config($path);
    }

    private function filled($value): bool
    {
        return trim((string) $value) !== '';
    }

    private function isMeaningful(string $value, array $invalid): bool
    {
        return !in_array(trim($value), $invalid, true);
    }

    private function boolString($value): string
    {
        return $value ? 'true' : 'false';
    }

    private function yesNo(string $table): string
    {
        return Schema::hasTable($table) ? 'yes' : 'no';
    }

    private function masked(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '-';
        }

        if (strlen($value) <= 8) {
            return str_repeat('*', strlen($value));
        }

        return substr($value, 0, 4) . str_repeat('*', max(strlen($value) - 8, 0)) . substr($value, -4);
    }

    private function isProduction(): bool
    {
        return $this->env('app.env') === 'production';
    }

    private function manualChecks(): array
    {
        return [
            'DNS wildcard/subdomain' => 'Pastikan apex domain, wildcard subdomain tenant, dan host `dash` semuanya resolve ke server production.',
            'SSL / HTTPS' => 'Pastikan sertifikat untuk apex domain dan subdomain tenant valid dan browser tidak menampilkan warning.',
            'Queue worker running' => 'Pastikan process queue worker berjalan stabil di supervisor, systemd, PM2, atau layanan sejenis.',
            'Scheduler / cron running' => 'Pastikan cron memanggil `php artisan schedule:run` setiap menit.',
            'Midtrans webhook configured' => 'Pastikan notification URL Midtrans mengarah ke `/platform/billing/midtrans/webhook` di domain production.',
            'Smoke login tenant' => 'Coba login tenant lewat `slug.domain.com/login` dan pastikan session tetap stabil setelah redirect.',
            'Smoke login platform' => 'Coba login owner platform lewat `dash.domain.com/login` dan pastikan user tenant biasa tidak bisa masuk.',
            'Smoke billing payment' => 'Buat invoice uji, jalankan checkout Midtrans, dan pastikan payment serta subscription aktif di platform.',
            'Smoke AI top-up pricing' => 'Cek dashboard tenant dan settings subscription: harga per credit serta pack AI Credits harus tampil sesuai pricing launch.',
            'Smoke affiliate link' => 'Jika affiliate dipakai, buka `/affiliate-program` dan satu link `/aff/{slug}` untuk memastikan attribution dan halaman partner berjalan.',
            'Smoke Sentry event' => 'Trigger satu error uji atau gunakan check runtime agar event benar-benar masuk ke project Sentry production.',
            'Smoke email delivery' => 'Pastikan email invoice dan payment confirmation benar-benar terkirim ke inbox tujuan.',
            'Rollback readiness' => 'Simpan backup database dan prosedur rollback sebelum publish trafik penuh.',
        ];
    }

    private function planCatalogChecks(): array
    {
        if (!Schema::hasTable('subscription_plans')) {
            return [];
        }

        $publicPlans = SubscriptionPlan::query()
            ->public()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        if ($publicPlans->isEmpty()) {
            return [];
        }

        $expensiveLimitKeys = [
            PlanLimit::USERS,
            PlanLimit::CONTACTS,
            PlanLimit::WHATSAPP_INSTANCES,
            PlanLimit::SOCIAL_ACCOUNTS,
            PlanLimit::LIVE_CHAT_WIDGETS,
            PlanLimit::CHATBOT_ACCOUNTS,
            PlanLimit::AI_CREDITS_MONTHLY,
        ];

        $plansMissingCaps = $publicPlans
            ->filter(function (SubscriptionPlan $plan) use ($expensiveLimitKeys) {
                $limits = is_array($plan->limits) ? $plan->limits : [];

                foreach ($expensiveLimitKeys as $key) {
                    if (!array_key_exists($key, $limits) || $limits[$key] === null || (int) $limits[$key] < 0) {
                        return true;
                    }
                }

                return false;
            })
            ->pluck('code')
            ->all();

        $featureLimitMap = [
            PlanFeature::SOCIAL_MEDIA => [PlanLimit::SOCIAL_ACCOUNTS],
            PlanFeature::LIVE_CHAT => [PlanLimit::LIVE_CHAT_WIDGETS],
            PlanFeature::CHATBOT_AI => [PlanLimit::CHATBOT_ACCOUNTS, PlanLimit::AI_CREDITS_MONTHLY, PlanLimit::CHATBOT_KNOWLEDGE_DOCUMENTS],
            PlanFeature::WHATSAPP_API => [PlanLimit::WHATSAPP_INSTANCES, PlanLimit::WA_BLAST_RECIPIENTS_MONTHLY],
            PlanFeature::EMAIL_MARKETING => [PlanLimit::EMAIL_CAMPAIGNS, PlanLimit::EMAIL_RECIPIENTS_MONTHLY],
        ];

        $plansWithFeatureRisk = $publicPlans
            ->filter(function (SubscriptionPlan $plan) use ($featureLimitMap) {
                $features = is_array($plan->features) ? $plan->features : [];
                $limits = is_array($plan->limits) ? $plan->limits : [];

                foreach ($featureLimitMap as $feature => $requiredLimitKeys) {
                    if (empty($features[$feature])) {
                        continue;
                    }

                    foreach ($requiredLimitKeys as $key) {
                        if (!array_key_exists($key, $limits) || $limits[$key] === null || (int) $limits[$key] < 0) {
                            return true;
                        }
                    }
                }

                return false;
            })
            ->pluck('code')
            ->all();

        return [
            $this->check(
                'public_plans_have_cost_caps',
                'Public plans have hard caps for expensive resources',
                empty($plansMissingCaps),
                empty($plansMissingCaps) ? 'all public plans capped' : implode(', ', $plansMissingCaps),
                'Set explicit caps for users, contacts, channel connections, live chat widgets, chatbot accounts, and AI credits on every public plan.',
                'warn'
            ),
            $this->check(
                'public_plan_feature_cost_control',
                'Enabled public plan features have matching cost controls',
                empty($plansWithFeatureRisk),
                empty($plansWithFeatureRisk) ? 'all enabled features controlled' : implode(', ', $plansWithFeatureRisk),
                'Do not publish expensive features without matching hard caps or monthly usage caps.',
                'warn'
            ),
        ];
    }

    private function moduleDatabaseChecks(): array
    {
        $checks = [];

        foreach ($this->activeModuleTableExpectations() as $slug => $tables) {
            $module = collect($this->modules->all())->firstWhere('slug', $slug);

            if (!$module || empty($module['installed']) || empty($module['active'])) {
                continue;
            }

            $missing = collect($tables)
                ->filter(fn (string $table) => !Schema::hasTable($table))
                ->values()
                ->all();

            $checks[] = $this->check(
                'module_tables_' . $slug,
                sprintf('Module %s has required database tables', (string) ($module['name'] ?? $slug)),
                empty($missing),
                empty($missing) ? 'all required tables present' : implode(', ', $missing),
                sprintf(
                    'Module `%s` is active but some required tables are missing. Run module migrations or reinstall/reactivate the module cleanly.',
                    $slug
                ),
                'fail'
            );
        }

        return $checks;
    }

    private function moduleFilesystemChecks(): array
    {
        $issues = $this->moduleFilesystemAudit->activeInstalledIssues();

        if (empty($issues)) {
            return [
                $this->check(
                    'active_module_filesystem',
                    'Installed active modules have expected filesystem structure',
                    true,
                    'all active module paths present',
                    'Re-deploy app/Modules with exact casing if any module directory, migration path, resources, or routes are missing.',
                    'fail'
                ),
            ];
        }

        $summary = collect($issues)
            ->map(fn (array $module) => sprintf('%s: %s', $module['slug'], implode('; ', $module['issues'])))
            ->implode(' | ');

        return [
            $this->check(
                'active_module_filesystem',
                'Installed active modules have expected filesystem structure',
                false,
                $summary,
                'One or more active modules are missing exact paths like Database/Migrations, resources/views, or routes/web.php. Re-deploy app/Modules with exact Linux-safe casing.',
                'fail'
            ),
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function activeModuleTableExpectations(): array
    {
        return [
            'conversations' => [
                'conversations',
                'conversation_messages',
                'conversation_participants',
                'conversation_activity_logs',
            ],
            'contacts' => [
                'contacts',
            ],
            'crm' => [
                'crm_leads',
            ],
            'live_chat' => [
                'live_chat_widgets',
            ],
            'whatsapp_api' => [
                'whatsapp_instances',
                'wa_templates',
                'wa_blast_campaigns',
                'wa_webhook_events',
            ],
            'whatsapp_web' => [
                'whatsapp_web_settings',
            ],
            'social_media' => [
                'social_accounts',
            ],
            'email_inbox' => [
                'email_accounts',
                'email_messages',
            ],
            'chatbot' => [
                'chatbot_accounts',
                'chatbot_messages',
                'chatbot_knowledge_documents',
            ],
            'email_marketing' => [
                'email_campaigns',
                'email_campaign_recipients',
            ],
            'products' => [
                'products',
            ],
            'inventory' => [
                'inventory_stocks',
                'inventory_stock_movements',
            ],
            'discounts' => [
                'discounts',
                'discount_vouchers',
            ],
            'sales' => [
                'sales',
                'sale_items',
            ],
            'payments' => [
                'payments',
                'payment_methods',
            ],
            'purchases' => [
                'purchases',
                'purchase_items',
            ],
            'finance' => [
                'finance_transactions',
                'finance_categories',
            ],
            'point-of-sale' => [
                'pos_cash_sessions',
                'pos_carts',
                'pos_cash_session_movements',
            ],
            'task_management' => [
                'memos',
                'tasks',
            ],
            'shortlink' => [
                'shortlinks',
                'shortlink_clicks',
            ],
        ];
    }
}
