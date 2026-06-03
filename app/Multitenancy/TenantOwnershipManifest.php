<?php

namespace App\Multitenancy;

use Illuminate\Support\Facades\File;

class TenantOwnershipManifest
{
    public const CENTRAL = 'central';
    public const TENANT_SCOPED_SHARED = 'tenant_scoped_shared';
    public const FUTURE_TENANT_ISOLATED = 'future_tenant_isolated';

    /**
     * @return array<int, string>
     */
    public function centralModelClasses(): array
    {
        return [
            \App\Models\Tenant::class,
            \App\Models\TenantServer::class,
            \App\Models\TenantDatabase::class,
            \App\Models\TenantDomain::class,
            \App\Models\TenantTopology::class,
            \App\Models\AppServer::class,
            \App\Models\StorageServer::class,
            \App\Models\StorageBucket::class,
            \App\Models\StorageProfile::class,
            \App\Models\TenantRuntimeTopology::class,
            \App\Models\TenantStorageTopology::class,
            \App\Models\Module::class,
            \App\Models\SubscriptionPlan::class,
            \App\Models\TenantSubscription::class,
            \App\Models\PlatformPlanOrder::class,
            \App\Models\PlatformInvoice::class,
            \App\Models\PlatformInvoiceItem::class,
            \App\Models\PlatformPayment::class,
            \App\Models\PlatformAffiliate::class,
            \App\Models\PlatformAffiliateReferral::class,
            \App\Models\PlatformPromoCode::class,
        ];
    }

    /**
     * @return array<int, string>
     */
    public function centralTables(): array
    {
        return [
            'tenants',
            'tenant_servers',
            'tenant_databases',
            'tenant_domains',
            'tenant_topologies',
            'app_servers',
            'storage_servers',
            'storage_buckets',
            'storage_profiles',
            'tenant_runtime_topologies',
            'tenant_storage_topologies',
            'modules',
            'subscription_plans',
            'tenant_subscriptions',
            'platform_plan_orders',
            'platform_invoices',
            'platform_invoice_items',
            'platform_payments',
            'platform_affiliates',
            'platform_affiliate_referrals',
            'platform_promo_codes',
            'tenant_slug_reservations',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function tenantRouteBindingModelClasses(): array
    {
        return [
            \App\Models\User::class,
            \App\Models\StoredFile::class,
            \App\Models\ApprovalRequest::class,
            \App\Modules\Sales\Models\Sale::class,
            \App\Modules\PointOfSale\Models\PosCashSession::class,
            \App\Modules\SocialMedia\Models\SocialAccount::class,
        ];
    }

    public function classifyModel(string $class): string
    {
        if (in_array($class, $this->centralModelClasses(), true)) {
            return self::CENTRAL;
        }

        return self::TENANT_SCOPED_SHARED;
    }

    public function classifyTable(string $table): string
    {
        if (in_array($table, $this->centralTables(), true)) {
            return self::CENTRAL;
        }

        return self::TENANT_SCOPED_SHARED;
    }

    public function isCentralModel(string $class): bool
    {
        return $this->classifyModel($class) === self::CENTRAL;
    }

    public function isCentralTable(string $table): bool
    {
        return $this->classifyTable($table) === self::CENTRAL;
    }

    /**
     * @return array<int, string>
     */
    public function discoveredModelClasses(): array
    {
        $classes = [];

        foreach ([app_path('Models'), app_path('Modules')] as $basePath) {
            if (!is_dir($basePath)) {
                continue;
            }

            foreach (File::allFiles($basePath) as $file) {
                $path = $file->getRealPath();
                if (!$path || substr($path, -4) !== '.php') {
                    continue;
                }

                $contents = File::get($path);
                if (!preg_match('/namespace\s+([^;]+);/m', $contents, $namespaceMatch)) {
                    continue;
                }

                if (!preg_match('/class\s+([A-Za-z0-9_]+)\s+extends\s+Model/m', $contents)) {
                    continue;
                }

                preg_match('/class\s+([A-Za-z0-9_]+)\s+extends\s+Model/m', $contents, $classMatch);
                $classes[] = trim($namespaceMatch[1]) . '\\' . trim($classMatch[1]);
            }
        }

        sort($classes);

        return array_values(array_unique($classes));
    }
}
