<?php

namespace App\Console\Commands;

use App\Multitenancy\QueryReadinessAuditService;
use App\Multitenancy\TenantRuntimeTopologyResolver;
use App\Multitenancy\TenantStorageTopologyResolver;
use App\Multitenancy\TenantTopologyFingerprint;
use App\Multitenancy\TenantTopologyValidator;
use App\Models\StorageProfile;
use App\Models\Tenant;
use Illuminate\Console\Command;

class TenantHealthCheckCommand extends Command
{
    protected $signature = 'tenant:health-check {tenant? : Tenant ID or slug}';

    protected $description = 'Validate tenant registry/topology completeness and default topology invariants.';

    public function handle(
        QueryReadinessAuditService $audit,
        TenantTopologyValidator $validator,
        TenantRuntimeTopologyResolver $runtimeTopologies,
        TenantStorageTopologyResolver $storageTopologyResolver,
        TenantTopologyFingerprint $fingerprint
    ): int
    {
        $query = Tenant::query()->with(['topology.database.server', 'runtimeTopology.appServer', 'storageTopologies.storageBucket.server']);
        $value = $this->argument('tenant');

        if ($value !== null) {
            $query->where(ctype_digit((string) $value) ? 'id' : 'slug', ctype_digit((string) $value) ? (int) $value : (string) $value);
        }

        $rows = [];
        $failed = false;

        foreach ($query->orderBy('id')->get() as $tenant) {
            $issues = [];
            $topology = $tenant->topology;
            $runtime = $tenant->runtimeTopology;
            $tenantStorageTopologies = $tenant->storageTopologies;
            $registryValid = true;
            $runtimeConsumed = true;
            $storageConsumed = true;
            $issues = array_merge($issues, $validator->validateTenant($tenant));

            if (!$topology) {
                $issues[] = 'missing topology';
                $registryValid = false;
            } else {
                if ($topology->server_key === '') {
                    $issues[] = 'missing server_key';
                    $registryValid = false;
                }
                if ($topology->database_key === '') {
                    $issues[] = 'missing database_key';
                    $registryValid = false;
                }
                if ($topology->schema_name === '') {
                    $issues[] = 'missing schema_name';
                    $registryValid = false;
                }
                if (!in_array($topology->isolation_mode, ['tenant_id', 'schema', 'database'], true)) {
                    $issues[] = 'invalid isolation_mode';
                    $registryValid = false;
                }
                if ($topology->isolation_mode === 'tenant_id' && $topology->schema_name !== 'public') {
                    $issues[] = 'tenant_id mode must use public schema by default';
                    $registryValid = false;
                }
                if (!$topology->server || $topology->server->key !== $topology->server_key) {
                    $issues[] = 'server_key mismatch';
                    $registryValid = false;
                }
                if (!$topology->database || $topology->database->key !== $topology->database_key) {
                    $issues[] = 'database_key mismatch';
                    $registryValid = false;
                }
            }

            if (!$runtime) {
                $issues[] = 'missing runtime topology';
                $runtimeConsumed = false;
            } else {
                if ($runtime->app_server_key === '') {
                    $issues[] = 'missing app_server_key';
                    $runtimeConsumed = false;
                }
                if (!$runtime->appServer || $runtime->appServer->key !== $runtime->app_server_key) {
                    $issues[] = 'app_server_key mismatch';
                    $runtimeConsumed = false;
                }
                if ($runtime->queue_cluster === '') {
                    $issues[] = 'missing queue_cluster';
                    $runtimeConsumed = false;
                }
                if ($runtime->realtime_cluster === '') {
                    $issues[] = 'missing realtime_cluster';
                    $runtimeConsumed = false;
                }
                if ($runtime->scheduler_cluster === '') {
                    $issues[] = 'missing scheduler_cluster';
                    $runtimeConsumed = false;
                }
            }

            if ($tenantStorageTopologies->isEmpty()) {
                $issues[] = 'missing storage topology';
                $storageConsumed = false;
            } else {
                $defaultPublic = $tenantStorageTopologies->first(fn ($item) => $item->visibility === 'public' && $item->is_default);
                $defaultPrivate = $tenantStorageTopologies->first(fn ($item) => $item->visibility === 'private' && $item->is_default);

                if (!$defaultPublic) {
                    $issues[] = 'missing default public storage';
                    $storageConsumed = false;
                }

                if (!$defaultPrivate) {
                    $issues[] = 'missing default private storage';
                    $storageConsumed = false;
                }

                foreach ($tenantStorageTopologies as $storageTopology) {
                    if ($storageTopology->storage_server_key === '') {
                        $issues[] = 'missing storage_server_key';
                        $storageConsumed = false;
                    }
                    if ($storageTopology->storage_bucket_key === '') {
                        $issues[] = 'missing storage_bucket_key';
                        $storageConsumed = false;
                    }
                    if (!$storageTopology->storageBucket || $storageTopology->storageBucket->key !== $storageTopology->storage_bucket_key) {
                        $issues[] = 'storage_bucket_key mismatch';
                        $storageConsumed = false;
                    }
                    if (!$storageTopology->storageServer || $storageTopology->storageServer->key !== $storageTopology->storage_server_key) {
                        $issues[] = 'storage_server_key mismatch';
                        $storageConsumed = false;
                    }
                }
            }

            if ((new StorageProfile())->getConnectionName() !== 'central') {
                $issues[] = 'storage_profiles control-plane is not central';
                $storageConsumed = false;
            }

            if (!$storageTopologyResolver->resolveForTenant($tenant, 'public') || !$storageTopologyResolver->resolveForTenant($tenant, 'private')) {
                $issues[] = 'storage routing not fully consumable';
                $storageConsumed = false;
            }

            if (!$runtimeTopologies->resolveForTenant($tenant)) {
                $issues[] = 'runtime topology not consumable';
                $runtimeConsumed = false;
            }

            if ($fingerprint->combined($tenant) === '') {
                $issues[] = 'missing topology fingerprint';
                $runtimeConsumed = false;
            }

            $moveReady = $registryValid && $runtimeConsumed && $storageConsumed;

            $rows[] = [
                $tenant->id,
                $tenant->slug,
                $registryValid ? 'ok' : 'fail',
                $runtimeConsumed ? 'ok' : 'fail',
                $storageConsumed ? 'ok' : 'fail',
                $moveReady ? 'ready' : 'blocked',
                $issues === [] ? 'ok' : implode('; ', $issues),
            ];

            $failed = $failed || $issues !== [];
        }

        $this->table(['ID', 'Slug', 'Registry', 'Runtime', 'Storage', 'Move Ready', 'Health'], $rows);

        $readiness = $audit->audit();
        $queryAuditFailed = false;

        foreach ($readiness as $section => $issues) {
            $issues = array_values(array_unique(array_filter($issues)));

            if ($issues === []) {
                $this->info('query-readiness ' . $section . ': ok');
                continue;
            }

            $queryAuditFailed = true;
            $this->warn('query-readiness ' . $section . ':');

            foreach ($issues as $issue) {
                $this->line('- ' . $issue);
            }
        }

        return ($failed || $queryAuditFailed) ? self::FAILURE : self::SUCCESS;
    }
}
