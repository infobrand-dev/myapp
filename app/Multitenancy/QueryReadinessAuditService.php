<?php

namespace App\Multitenancy;

use Illuminate\Support\Facades\File;
use ReflectionClass;

class QueryReadinessAuditService
{
    private const RAW_QUERY_PATHS = [
        'app/Services/TenantStorageUsageService.php',
        'app/Support/Notifications/NotificationQueryService.php',
        'app/Services/StaleOnboardingCleanupService.php',
        'app/Modules/Reports/Services/SalesReportService.php',
    ];

    private TenantOwnershipManifest $ownership;
    private TenantMigrationManifest $tenantMigrations;

    public function __construct(
        TenantOwnershipManifest $ownership,
        TenantMigrationManifest $tenantMigrations
    ) {
        $this->ownership = $ownership;
        $this->tenantMigrations = $tenantMigrations;
    }

    public function audit(): array
    {
        return [
            'ownership_manifest' => $this->ownershipFindings(),
            'central_models' => $this->centralModelFindings(),
            'storage_control_plane' => $this->storageControlPlaneFindings(),
            'storage_routing' => $this->storageRoutingFindings(),
            'queue_topology' => $this->queueTopologyFindings(),
            'tenant_models' => $this->tenantModelFindings(),
            'raw_queries' => $this->rawQueryFindings(),
            'migration_manifest' => $this->migrationFindings(),
        ];
    }

    private function ownershipFindings(): array
    {
        $issues = [];

        foreach ($this->ownership->centralModelClasses() as $class) {
            if ($this->ownership->classifyModel($class) !== TenantOwnershipManifest::CENTRAL) {
                $issues[] = $class . ': central model misclassified';
            }
        }

        foreach ($this->ownership->tenantRouteBindingModelClasses() as $class) {
            if ($this->ownership->classifyModel($class) === TenantOwnershipManifest::CENTRAL) {
                $issues[] = $class . ': tenant model misclassified as central';
            }
        }

        return $issues;
    }

    private function centralModelFindings(): array
    {
        $issues = [];

        foreach ($this->ownership->centralModelClasses() as $class) {
            if (!class_exists($class)) {
                $issues[] = $class . ': missing class';
                continue;
            }

            $instance = new $class();
            if (method_exists($instance, 'getConnectionName') && $instance->getConnectionName() !== 'central') {
                $issues[] = $class . ': central model must use connection central';
            }
        }

        return $issues;
    }

    private function tenantModelFindings(): array
    {
        $issues = [];

        foreach ($this->ownership->tenantRouteBindingModelClasses() as $class) {
            if (!class_exists($class)) {
                $issues[] = $class . ': missing representative tenant model';
                continue;
            }

            $reflection = new ReflectionClass($class);
            $file = $reflection->getFileName();
            if (!$file || !is_file($file)) {
                continue;
            }

            $contents = File::get($file);
            if (!preg_match('/resolveRouteBinding\s*\(/', $contents)) {
                $issues[] = $class . ': missing route binding scope';
                continue;
            }

            if (!str_contains($contents, "TenantContext::currentId()")) {
                $issues[] = $class . ': route binding missing tenant context';
            }
        }

        return $issues;
    }

    private function rawQueryFindings(): array
    {
        $issues = [];

        foreach (self::RAW_QUERY_PATHS as $relativePath) {
            $path = base_path($relativePath);
            if (!is_file($path)) {
                $issues[] = $relativePath . ': missing hotspot file';
                continue;
            }

            $contents = File::get($path);
            $hasRawQuery = str_contains($contents, 'DB::table(')
                || str_contains($contents, 'DB::select(')
                || str_contains($contents, 'DB::statement(');

            if (!$hasRawQuery) {
                continue;
            }

            $hasGuard = str_contains($contents, 'QueryContextGuard')
                || str_contains($contents, 'TenantContext::currentId()')
                || str_contains($contents, 'applyTenantCompanyBranchScope(')
                || str_contains($contents, 'extends BaseReportService');

            if (!$hasGuard) {
                $issues[] = $relativePath . ': raw query hotspot missing tenant/company guard';
            }
        }

        return $issues;
    }

    private function migrationFindings(): array
    {
        $issues = [];

        if (!in_array(app_path('Modules/Sales/Database/Migrations'), $this->tenantMigrations->moduleMigrationPaths(), true)) {
            $issues[] = 'tenant manifest missing Sales module migrations';
        }

        if (!in_array(database_path('migrations/2014_10_12_000000_create_users_table.php'), $this->tenantMigrations->coreMigrationPaths(), true)) {
            $issues[] = 'tenant manifest missing users core migration';
        }

        if (in_array(database_path('migrations/2026_06_01_160000_create_storage_profiles_and_extend_stored_files.php'), $this->tenantMigrations->coreMigrationPaths(), true)) {
            $issues[] = 'storage_profiles migration must not be in tenant core migration set';
        }

        foreach (app(TenantOwnershipManifest::class)->centralTables() as $table) {
            if ($table === 'subscription_plans' || $table === 'modules') {
                continue;
            }

            foreach ($this->tenantMigrations->coreMigrationPaths() as $path) {
                $contents = File::get($path);
                $createsCentralTable = str_contains($contents, "Schema::create('" . $table . "'")
                    || str_contains($contents, 'Schema::create("' . $table . '"')
                    || str_contains($contents, "Schema::table('" . $table . "'")
                    || str_contains($contents, 'Schema::table("' . $table . '"');

                if ($createsCentralTable) {
                    $issues[] = 'central table present in tenant core migration set: ' . $table;
                    break;
                }
            }
        }

        return $issues;
    }

    private function storageControlPlaneFindings(): array
    {
        $issues = [];

        if ($this->ownership->classifyModel(\App\Models\StorageProfile::class) !== TenantOwnershipManifest::CENTRAL) {
            $issues[] = 'StorageProfile must be classified as central';
        }

        if ($this->ownership->classifyTable('storage_profiles') !== TenantOwnershipManifest::CENTRAL) {
            $issues[] = 'storage_profiles table must be classified as central';
        }

        if ((new \App\Models\StorageProfile())->getConnectionName() !== 'central') {
            $issues[] = 'StorageProfile must use central connection';
        }

        return $issues;
    }

    private function storageRoutingFindings(): array
    {
        $issues = [];

        $routingPath = app_path('Services/StorageRoutingService.php');
        $storedFilePath = app_path('Services/StoredFileService.php');
        $resolvedStoragePath = app_path('Multitenancy/ResolvedTenantStorage.php');

        $routingContents = is_file($routingPath) ? File::get($routingPath) : '';
        $storedFileContents = is_file($storedFilePath) ? File::get($storedFilePath) : '';
        $resolvedStorageContents = is_file($resolvedStoragePath) ? File::get($resolvedStoragePath) : '';

        if (!str_contains($routingContents, 'TenantStorageTopologyResolver')) {
            $issues[] = 'StorageRoutingService must resolve tenant storage topology';
        }

        if (!str_contains($routingContents, 'storage_base_path') && !str_contains($routingContents, 'resolveDirectory(')) {
            $issues[] = 'StorageRoutingService must apply tenant storage base path';
        }

        if (!str_contains($storedFileContents, 'storage_topology_degraded')) {
            $issues[] = 'StoredFileService must surface degraded legacy storage fallback metadata';
        }

        if (!str_contains($resolvedStorageContents, 'tenant_storage_topology_id')) {
            $issues[] = 'Storage routing snapshot must include tenant storage topology id';
        }

        return $issues;
    }

    private function queueTopologyFindings(): array
    {
        $issues = [];
        $providerPath = app_path('Providers/AppServiceProvider.php');
        $contents = is_file($providerPath) ? File::get($providerPath) : '';

        foreach ([
            'isolation_mode',
            'server_key',
            'database_key',
            'app_server_key',
            'queue_cluster',
            'storage_topology_fingerprint',
            'topology_fingerprint',
        ] as $requiredField) {
            if (!str_contains($contents, "'" . $requiredField . "'")) {
                $issues[] = 'queue payload missing topology field: ' . $requiredField;
            }
        }

        if (!str_contains($contents, 'assertQueuedTopologySnapshot')) {
            $issues[] = 'queue bootstrapping must validate queued topology snapshot';
        }

        return $issues;
    }
}
