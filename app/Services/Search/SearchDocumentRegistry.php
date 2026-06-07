<?php

namespace App\Services\Search;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SearchDocumentRegistry
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function definitions(): array
    {
        return [
            'user' => [
                'model' => User::class,
                'title' => fn (User $user): string => $user->name,
                'subtitle' => fn (User $user): ?string => $user->email,
                'snippet' => fn (User $user): ?string => $user->email,
                'url' => fn (User $user): string => route('users.edit', ['user' => $user->id]),
            ],
            'company' => [
                'model' => Company::class,
                'title' => fn (Company $company): string => $company->name,
                'subtitle' => fn (Company $company): ?string => $company->code,
                'snippet' => fn (Company $company): ?string => $company->slug,
                'url' => fn (): string => route('settings.company'),
            ],
            'branch' => [
                'model' => Branch::class,
                'title' => fn (Branch $branch): string => $branch->name,
                'subtitle' => fn (Branch $branch): ?string => $branch->code,
                'snippet' => fn (Branch $branch): ?string => $branch->slug,
                'url' => fn (): string => route('settings.branch'),
            ],
            'role' => [
                'model' => Role::class,
                'title' => fn (Role $role): string => $role->name,
                'subtitle' => fn (): ?string => 'Role akses tenant',
                'snippet' => fn (Role $role): ?string => 'Guard: ' . $role->guard_name,
                'url' => fn (Role $role): string => route('roles.edit', ['role' => $role->id]),
            ],
            'permission' => [
                'model' => Permission::class,
                'title' => fn (Permission $permission): string => $permission->name,
                'subtitle' => fn (): ?string => 'Permission',
                'snippet' => fn (Permission $permission): ?string => 'Guard: ' . $permission->guard_name,
                'url' => fn (): string => route('roles.index'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function definitionForType(string $type): ?array
    {
        return $this->definitions()[$type] ?? null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function definitionForModel(Model $model): ?array
    {
        foreach ($this->definitions() as $definition) {
            if ($model instanceof $definition['model']) {
                return $definition;
            }
        }

        return null;
    }
}
