<?php

namespace App\Console\Commands;

use App\Multitenancy\QueryReadinessAuditService;
use App\Models\Tenant;
use Illuminate\Console\Command;

class TenantEnableSchemaModeCommand extends Command
{
    protected $signature = 'tenant:enable-schema-mode {tenant : Tenant ID or slug} {--schema=} {--database-key=main}';

    protected $description = 'Mark a tenant topology as schema-isolated and prepare its target schema mapping.';

    public function handle(QueryReadinessAuditService $audit): int
    {
        $readiness = collect($audit->audit())
            ->flatMap(static fn (array $issues) => $issues)
            ->filter()
            ->values();

        if ($readiness->isNotEmpty()) {
            $this->error('Schema mode cannot be enabled while query readiness audit still has findings.');
            foreach ($readiness as $issue) {
                $this->line('- ' . $issue);
            }

            return self::FAILURE;
        }

        $value = (string) $this->argument('tenant');
        $tenant = ctype_digit($value)
            ? Tenant::query()->with(['topology', 'topology.database'])->find((int) $value)
            : Tenant::query()->with(['topology', 'topology.database'])->where('slug', $value)->first();

        if (!$tenant || !$tenant->topology) {
            $this->error('Tenant topology not found.');

            return self::FAILURE;
        }

        $schema = (string) ($this->option('schema') ?: app(\App\Multitenancy\TenantRegistry::class)->generateSafeSchemaName(
            $tenant->slug,
            optional($tenant->topology->database)->schema_prefix
        ));

        $tenant->topology->forceFill([
            'database_key' => (string) $this->option('database-key'),
            'schema_name' => $schema,
            'isolation_mode' => 'schema',
            'status' => 'ready',
            'meta' => array_merge((array) $tenant->topology->meta, [
                'ready_for_schema_mode' => true,
            ]),
        ])->save();

        $tenant->forceFill([
            'schema_name' => $schema,
        ])->save();

        $this->info("Tenant [{$tenant->slug}] is marked for schema mode with schema [{$schema}].");

        return self::SUCCESS;
    }
}
