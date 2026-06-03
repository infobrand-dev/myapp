<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class TenantStatusCommand extends Command
{
    protected $signature = 'tenant:status {tenant? : Tenant ID or slug}';

    protected $description = 'Show tenant registry status and topology mapping.';

    public function handle(): int
    {
        $query = Tenant::query()->with(['topology.database.server', 'runtimeTopology.appServer', 'storageTopologies.storageBucket.server', 'domains'])->orderBy('id');
        $value = $this->argument('tenant');

        if ($value !== null) {
            $query->where(ctype_digit((string) $value) ? 'id' : 'slug', ctype_digit((string) $value) ? (int) $value : (string) $value);
        }

        $rows = $query->get()->map(fn (Tenant $tenant) => [
            $tenant->id,
            $tenant->slug,
            $tenant->status,
            optional($tenant->topology)->isolation_mode,
            optional($tenant->topology)->schema_name,
            optional($tenant->topology)->database_key,
            optional(optional($tenant->topology)->database)->database_name,
            optional(optional(optional($tenant->topology)->database)->server)->host,
            optional($tenant->topology)->server_key,
            optional($tenant->runtimeTopology)->app_server_key,
            optional($tenant->runtimeTopology)->queue_cluster,
            implode(', ', $tenant->storageTopologies->map(fn ($topology) => (string) $topology->storage_bucket_key . ':' . (string) $topology->visibility)->all()),
            optional($tenant->domains->firstWhere('is_primary', true))->domain,
        ])->all();

        $this->table(['ID', 'Slug', 'Status', 'Isolation', 'Schema', 'DB Key', 'Database', 'Host', 'Server Key', 'App Server', 'Queue', 'Storage', 'Primary Domain'], $rows);

        return self::SUCCESS;
    }
}
