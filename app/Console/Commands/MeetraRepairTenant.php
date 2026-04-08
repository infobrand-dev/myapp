<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserBranch;
use App\Models\UserCompany;
use App\Support\TenantContext;
use App\Support\WorkspaceContextProvisioner;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MeetraRepairTenant extends Command
{
    protected $signature = 'meetra:repair-tenant
                            {tenant? : Tenant ID or slug}
                            {--all : Scan or repair all active tenants}
                            {--fix : Apply repairs instead of scan only}';

    protected $description = 'Scan and repair tenant workspace context, including default company, branch, user access, and PostgreSQL sequences.';

    public function handle(WorkspaceContextProvisioner $provisioner): int
    {
        if (!Schema::hasTable('tenants')) {
            $this->error('tenants table does not exist.');

            return self::FAILURE;
        }

        $tenants = $this->resolveTenants();

        if ($tenants === null) {
            return self::FAILURE;
        }

        if ($tenants->isEmpty()) {
            $this->warn('No tenant matched the given criteria.');

            return self::SUCCESS;
        }

        $shouldFix = (bool) $this->option('fix');
        $summary = [];

        foreach ($tenants as $tenant) {
            TenantContext::setCurrentId($tenant->id);

            try {
                $before = $this->inspectTenant($tenant);

                if ($shouldFix) {
                    $this->repairTenant($tenant, $provisioner, $before);
                }

                $after = $shouldFix ? $this->inspectTenant($tenant) : $before;

                $summary[] = [
                    'tenant' => '#'.$tenant->id.' '.$tenant->slug,
                    'scan' => $this->summarizeIssues($before['issues']),
                    'after' => $shouldFix ? $this->summarizeIssues($after['issues']) : '-',
                ];

                $this->line('');
                $this->info('Tenant #'.$tenant->id.' ('.$tenant->name.')');
                $this->line('Scan  : '.$this->summarizeIssues($before['issues']));

                if ($shouldFix) {
                    $this->line('Repair: '.($before['actions'] === [] ? 'nothing to apply' : implode('; ', $before['actions'])));
                    $this->line('After : '.$this->summarizeIssues($after['issues']));
                }
            } finally {
                TenantContext::forget();
            }
        }

        $this->newLine();
        $this->table(['Tenant', 'Scan', 'After'], $summary);

        if ($shouldFix) {
            $this->info('Repair completed.');
        } else {
            $this->info('Scan completed. Run again with --fix to apply repairs.');
        }

        return self::SUCCESS;
    }

    private function resolveTenants(): ?Collection
    {
        $tenantArg = $this->argument('tenant');
        $all = (bool) $this->option('all');

        if ($tenantArg === null && !$all) {
            $this->error('Provide a tenant ID/slug or use --all.');

            return null;
        }

        if ($tenantArg !== null && $all) {
            $this->error('Use either a tenant argument or --all, not both.');

            return null;
        }

        if ($tenantArg !== null) {
            $tenant = ctype_digit((string) $tenantArg)
                ? Tenant::query()->find((int) $tenantArg)
                : Tenant::query()->where('slug', $tenantArg)->first();

            if (!$tenant) {
                $this->error("Tenant '{$tenantArg}' not found.");

                return null;
            }

            return collect([$tenant]);
        }

        return Tenant::query()->active()->orderBy('id')->get();
    }

    private function inspectTenant(Tenant $tenant): array
    {
        $issues = [];
        $actions = [];

        $companies = Company::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('id')
            ->get();

        $activeCompany = $companies->first(fn (Company $company) => (bool) $company->is_active);

        if ($companies->isEmpty()) {
            $issues[] = 'missing company';
            $actions[] = 'create/activate default company';
        } elseif (!$activeCompany) {
            $issues[] = 'no active company';
            $actions[] = 'activate first company';
            $activeCompany = $companies->first();
        }

        $branches = collect();
        $activeBranch = null;

        if ($activeCompany) {
            $branches = Branch::query()
                ->where('tenant_id', $tenant->id)
                ->where('company_id', $activeCompany->id)
                ->orderBy('id')
                ->get();

            $activeBranch = $branches->first(fn (Branch $branch) => (bool) $branch->is_active);

            if ($branches->isEmpty()) {
                $issues[] = 'missing branch';
                $actions[] = 'create/activate default branch';
            } elseif (!$activeBranch) {
                $issues[] = 'no active branch';
                $actions[] = 'activate first branch';
                $activeBranch = $branches->first();
            }
        }

        foreach (['companies', 'branches', 'user_companies', 'user_branches'] as $table) {
            $sequenceState = $this->inspectSequenceState($table);

            if ($sequenceState && $sequenceState['out_of_sync']) {
                $issues[] = 'sequence '.$table.' out of sync';
                $actions[] = 'sync '.$table.' sequence';
            }
        }

        if (Schema::hasTable('users')) {
            $users = User::query()
                ->where('tenant_id', $tenant->id)
                ->orderBy('id')
                ->get();

            foreach ($users as $user) {
                $userIssues = $this->inspectUserAccess($user);

                if ($userIssues !== []) {
                    $issues[] = 'user#'.$user->id.' '.implode(', ', $userIssues);
                    $actions[] = 'repair access user#'.$user->id;
                }
            }
        }

        return [
            'issues' => array_values(array_unique($issues)),
            'actions' => array_values(array_unique($actions)),
            'active_company_id' => $activeCompany?->id,
            'active_branch_id' => $activeBranch?->id,
        ];
    }

    private function inspectUserAccess(User $user): array
    {
        $issues = [];

        if (Schema::hasTable('user_companies')) {
            $companyRows = UserCompany::query()
                ->where('tenant_id', (int) $user->tenant_id)
                ->where('user_id', $user->id)
                ->orderBy('id')
                ->get();

            if ($companyRows->isEmpty()) {
                $issues[] = 'missing company access';
            } elseif (!$companyRows->contains(fn (UserCompany $row) => (bool) $row->is_default)) {
                $issues[] = 'missing default company';
            }
        }

        if (Schema::hasTable('user_branches')) {
            $branchRows = UserBranch::query()
                ->where('tenant_id', (int) $user->tenant_id)
                ->where('user_id', $user->id)
                ->orderBy('id')
                ->get();

            if ($branchRows->isEmpty()) {
                $issues[] = 'missing branch access';
            } elseif (!$branchRows->contains(fn (UserBranch $row) => (bool) $row->is_default)) {
                $issues[] = 'missing default branch';
            }
        }

        return $issues;
    }

    private function repairTenant(Tenant $tenant, WorkspaceContextProvisioner $provisioner, array $scan): void
    {
        foreach (['companies', 'branches', 'user_companies', 'user_branches'] as $table) {
            $this->syncPrimaryKeySequence($table);
        }

        [$company, $branch] = $provisioner->ensureForTenant($tenant->id);

        if (!Schema::hasTable('users')) {
            return;
        }

        $users = User::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('id')
            ->get();

        foreach ($users as $user) {
            $this->repairUserAccess($user, $company, $branch);
        }
    }

    private function repairUserAccess(User $user, Company $fallbackCompany, Branch $fallbackBranch): void
    {
        DB::transaction(function () use ($user, $fallbackCompany, $fallbackBranch): void {
            if (Schema::hasTable('user_companies')) {
                $companyRows = UserCompany::query()
                    ->where('tenant_id', (int) $user->tenant_id)
                    ->where('user_id', $user->id)
                    ->orderBy('id')
                    ->get();

                if ($companyRows->isEmpty()) {
                    UserCompany::query()->create([
                        'tenant_id' => (int) $user->tenant_id,
                        'user_id' => $user->id,
                        'company_id' => $fallbackCompany->id,
                        'is_default' => true,
                    ]);
                } elseif (!$companyRows->contains(fn (UserCompany $row) => (bool) $row->is_default)) {
                    UserCompany::query()
                        ->where('tenant_id', (int) $user->tenant_id)
                        ->where('user_id', $user->id)
                        ->update(['is_default' => false]);

                    UserCompany::query()
                        ->whereKey($companyRows->first()->id)
                        ->update(['is_default' => true]);
                }
            }

            if (!Schema::hasTable('user_branches')) {
                return;
            }

            $branchRows = UserBranch::query()
                ->where('tenant_id', (int) $user->tenant_id)
                ->where('user_id', $user->id)
                ->orderBy('id')
                ->get();

            if ($branchRows->isEmpty()) {
                $companyId = $this->resolvePreferredCompanyIdForUser($user, $fallbackCompany->id);
                $branch = $this->resolvePreferredBranchForCompany((int) $user->tenant_id, $companyId, $fallbackBranch);

                $this->ensureUserCompanyRow($user, $branch->company_id);

                UserBranch::query()->create([
                    'tenant_id' => (int) $user->tenant_id,
                    'user_id' => $user->id,
                    'company_id' => $branch->company_id,
                    'branch_id' => $branch->id,
                    'is_default' => true,
                ]);

                return;
            }

            if (!$branchRows->contains(fn (UserBranch $row) => (bool) $row->is_default)) {
                UserBranch::query()
                    ->where('tenant_id', (int) $user->tenant_id)
                    ->where('user_id', $user->id)
                    ->update(['is_default' => false]);

                UserBranch::query()
                    ->whereKey($branchRows->first()->id)
                    ->update(['is_default' => true]);
            }

            $defaultBranchRow = UserBranch::query()
                ->where('tenant_id', (int) $user->tenant_id)
                ->where('user_id', $user->id)
                ->where('is_default', true)
                ->orderBy('id')
                ->first();

            if ($defaultBranchRow) {
                $this->ensureUserCompanyRow($user, (int) $defaultBranchRow->company_id);
            }
        });
    }

    private function ensureUserCompanyRow(User $user, int $companyId): void
    {
        if (!Schema::hasTable('user_companies')) {
            return;
        }

        $row = UserCompany::query()
            ->where('tenant_id', (int) $user->tenant_id)
            ->where('user_id', $user->id)
            ->where('company_id', $companyId)
            ->first();

        if ($row) {
            return;
        }

        UserCompany::query()->create([
            'tenant_id' => (int) $user->tenant_id,
            'user_id' => $user->id,
            'company_id' => $companyId,
            'is_default' => false,
        ]);
    }

    private function resolvePreferredCompanyIdForUser(User $user, int $fallbackCompanyId): int
    {
        if (!Schema::hasTable('user_companies')) {
            return $fallbackCompanyId;
        }

        $companyRow = UserCompany::query()
            ->where('tenant_id', (int) $user->tenant_id)
            ->where('user_id', $user->id)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();

        return $companyRow ? (int) $companyRow->company_id : $fallbackCompanyId;
    }

    private function resolvePreferredBranchForCompany(int $tenantId, int $companyId, Branch $fallbackBranch): Branch
    {
        return Branch::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->orderByDesc('is_active')
            ->orderBy('id')
            ->first() ?: $fallbackBranch;
    }

    private function inspectSequenceState(string $table): ?array
    {
        if (!Schema::hasTable($table) || DB::connection()->getDriverName() !== 'pgsql') {
            return null;
        }

        $sequenceName = DB::scalar("SELECT pg_get_serial_sequence('{$table}', 'id')");

        if (!$sequenceName) {
            return null;
        }

        $quotedSequence = $this->quoteQualifiedName((string) $sequenceName);
        $sequence = DB::selectOne("SELECT last_value, is_called FROM {$quotedSequence}");
        $maxId = (int) DB::table($table)->max('id');
        $nextValue = (int) $sequence->last_value + ((bool) $sequence->is_called ? 1 : 0);

        return [
            'out_of_sync' => $maxId > 0 && $nextValue <= $maxId,
            'max_id' => $maxId,
            'next_value' => $nextValue,
        ];
    }

    private function syncPrimaryKeySequence(string $table): void
    {
        if (!Schema::hasTable($table) || DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        $quotedTable = $this->quoteQualifiedName($table);

        DB::statement(
            "SELECT setval(pg_get_serial_sequence('{$table}', 'id'), COALESCE((SELECT MAX(id) FROM {$quotedTable}), 1), true)"
        );
    }

    private function quoteQualifiedName(string $name): string
    {
        return collect(explode('.', $name))
            ->map(fn (string $part) => '"'.str_replace('"', '""', $part).'"')
            ->implode('.');
    }

    private function summarizeIssues(array $issues): string
    {
        return $issues === [] ? 'clean' : implode('; ', $issues);
    }
}
