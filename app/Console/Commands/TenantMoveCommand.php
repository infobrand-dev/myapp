<?php

namespace App\Console\Commands;

use App\Multitenancy\QueryReadinessAuditService;
use App\Multitenancy\TenantTopologyValidator;
use App\Models\AppServer;
use App\Models\StorageBucket;
use App\Models\Tenant;
use App\Models\TenantDatabase;
use App\Models\TenantTopology;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TenantMoveCommand extends Command
{
    protected $signature = 'tenant:move
        {tenant : Tenant ID or slug}
        {database_key : Target tenant_databases.key}
        {server_key? : Target tenant_servers.key}
        {--target-schema=}
        {--isolation-mode=}
        {--app-server-key=}
        {--public-storage-bucket-key=}
        {--private-storage-bucket-key=}';

    protected $description = 'Update tenant topology mapping after external schema/data move is completed.';

    public function handle(QueryReadinessAuditService $audit, TenantTopologyValidator $validator): int
    {
        $readiness = collect($audit->audit())->flatMap(static fn (array $issues) => $issues)->filter()->values();
        if ($readiness->isNotEmpty()) {
            $this->error('Tenant move is blocked until query readiness audit is clean.');
            foreach ($readiness as $issue) {
                $this->line('- ' . $issue);
            }

            return self::FAILURE;
        }

        $value = (string) $this->argument('tenant');
        $tenant = ctype_digit($value)
            ? Tenant::query()->with(['topology', 'runtimeTopology', 'storageTopologies'])->find((int) $value)
            : Tenant::query()->with(['topology', 'runtimeTopology', 'storageTopologies'])->where('slug', $value)->first();

        $database = TenantDatabase::query()
            ->with('server')
            ->where('key', (string) $this->argument('database_key'))
            ->first();

        if (!$tenant || !$database) {
            $this->error('Tenant or target database not found.');

            return self::FAILURE;
        }

        $isolationMode = (string) ($this->option('isolation-mode') ?: $tenant->topology?->isolation_mode ?: 'tenant_id');
        $targetSchema = (string) ($this->option('target-schema') ?: $tenant->topology?->schema_name ?: 'public');

        if (in_array($isolationMode, ['schema', 'database'], true) && trim($targetSchema) === '') {
            $this->error('Target schema is required for schema/database isolation moves.');

            return self::FAILURE;
        }

        $appServer = null;
        if ($this->option('app-server-key')) {
            $appServer = AppServer::query()->where('key', (string) $this->option('app-server-key'))->where('status', 'active')->first();

            if (!$appServer) {
                $this->error('Target app server not found or inactive.');

                return self::FAILURE;
            }
        }

        $storageOverrides = [
            'public' => $this->option('public-storage-bucket-key'),
            'private' => $this->option('private-storage-bucket-key'),
        ];

        foreach ($storageOverrides as $visibility => $bucketKey) {
            if (!$bucketKey) {
                continue;
            }

            $bucket = StorageBucket::query()->with('server')->where('key', (string) $bucketKey)->where('status', 'active')->first();

            if (!$bucket || !$bucket->server || $bucket->server->status !== 'active') {
                $this->error('Target storage bucket/server for ' . $visibility . ' is not active.');

                return self::FAILURE;
            }

            $storageOverrides[$visibility] = $bucket;
        }

        DB::transaction(function () use ($tenant, $database, $targetSchema, $isolationMode, $appServer, $storageOverrides): void {
            $topology = $tenant->topology ?: new TenantTopology(['tenant_id' => $tenant->id]);
            $topology->fill([
                'tenant_server_id' => $database->server_id,
                'tenant_database_id' => $database->id,
                'server_key' => $this->argument('server_key') ?: optional($database->server)->key ?: 'primary',
                'database_key' => $database->key,
                'schema_name' => $targetSchema,
                'isolation_mode' => $isolationMode,
                'status' => 'active',
                'meta' => array_merge((array) $topology->meta, [
                    'last_move' => [
                        'previous' => [
                            'server_key' => $tenant->topology?->server_key,
                            'database_key' => $tenant->topology?->database_key,
                            'schema_name' => $tenant->topology?->schema_name,
                            'isolation_mode' => $tenant->topology?->isolation_mode,
                        ],
                        'target' => [
                            'server_key' => $this->argument('server_key') ?: optional($database->server)->key ?: 'primary',
                            'database_key' => $database->key,
                            'schema_name' => $targetSchema,
                            'isolation_mode' => $isolationMode,
                        ],
                        'moved_at' => now()->toIso8601String(),
                        'moved_by' => 'artisan:tenant:move',
                        'status' => 'registry_switched',
                    ],
                ]),
            ]);
            $topology->tenant_id = $tenant->id;
            $topology->save();

            $tenant->forceFill([
                'database_id' => $database->id,
                'server_id' => $database->server_id,
                'schema_name' => $topology->schema_name,
                'status' => 'active',
            ])->save();

            if ($appServer) {
                $tenant->runtimeTopology?->forceFill([
                    'app_server_id' => $appServer->id,
                    'app_server_key' => $appServer->key,
                    'queue_cluster' => $appServer->queue_cluster,
                    'realtime_cluster' => $appServer->realtime_cluster,
                    'scheduler_cluster' => $appServer->scheduler_cluster,
                    'status' => 'active',
                ])->save();
            }

            foreach ($storageOverrides as $visibility => $bucket) {
                if (!$bucket instanceof StorageBucket) {
                    continue;
                }

                $tenant->storageTopologies
                    ->firstWhere('visibility', $visibility)
                    ?->forceFill([
                        'storage_server_id' => $bucket->storage_server_id,
                        'storage_bucket_id' => $bucket->id,
                        'storage_server_key' => $bucket->server->key,
                        'storage_bucket_key' => $bucket->key,
                        'disk' => $bucket->disk,
                        'status' => 'active',
                    ])->save();
            }
        });

        $issues = $validator->validateTenant($tenant->fresh(['topology.database.server', 'runtimeTopology.appServer', 'storageTopologies.storageBucket.server']));
        if ($issues !== []) {
            $this->error('Tenant move left topology in an invalid state.');
            foreach ($issues as $issue) {
                $this->line('- ' . $issue);
            }

            return self::FAILURE;
        }

        $this->warn('Registry updated. Ensure data copy/verification was completed before this command is run in production.');

        return self::SUCCESS;
    }
}
