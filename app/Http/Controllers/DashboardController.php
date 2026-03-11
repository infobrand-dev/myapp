<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ModuleManager;
use Illuminate\Contracts\View\View;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function __invoke(ModuleManager $modules): View
    {
        $user = auth()->user();
        $allModules = collect($modules->all());
        $activeModules = $allModules->filter(fn ($module) => $module['installed'] && $module['active']);
        $installedModules = $allModules->filter(fn ($module) => $module['installed']);

        $stats = [
            [
                'label' => 'Active Modules',
                'value' => $activeModules->count(),
                'meta' => $allModules->count() . ' available',
                'tone' => 'primary',
            ],
            [
                'label' => 'Installed Modules',
                'value' => $installedModules->count(),
                'meta' => max($allModules->count() - $installedModules->count(), 0) . ' pending',
                'tone' => 'azure',
            ],
            [
                'label' => 'Users',
                'value' => User::query()->count(),
                'meta' => User::query()->whereDate('created_at', today())->count() . ' joined today',
                'tone' => 'green',
            ],
            [
                'label' => 'Roles',
                'value' => Role::query()->count(),
                'meta' => $user->getRoleNames()->join(', ') ?: 'No role',
                'tone' => 'orange',
            ],
        ];

        $recentUsers = User::query()
            ->latest()
            ->limit(6)
            ->get(['id', 'name', 'email', 'created_at', 'avatar']);

        $moduleHighlights = $activeModules
            ->map(fn ($module) => [
                'name' => $module['name'],
                'items' => count($module['navigation'] ?? []),
                'description' => $module['description'] ?: 'Module active',
            ])
            ->take(5)
            ->values();

        return view('dashboard', [
            'stats' => $stats,
            'recentUsers' => $recentUsers,
            'moduleHighlights' => $moduleHighlights,
            'activeModules' => $activeModules,
            'totalModules' => $allModules->count(),
        ]);
    }
}
