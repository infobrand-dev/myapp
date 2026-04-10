@php
    $moduleManager = app(\App\Support\ModuleManager::class);
    $planManager   = app(\App\Support\TenantPlanManager::class);
    $isPlatformAdminHost = (bool) request()->attributes->get('platform_admin_host');

    $appMenuModules = collect($moduleManager->all())
        ->filter(function ($module) use ($planManager, $isPlatformAdminHost) {
            if (!$module['installed'] || !$module['active']) {
                return false;
            }

            if ($isPlatformAdminHost) {
                return true;
            }

            $requirement = \App\Support\PlanFeature::moduleFeatureRequirement((string) ($module['slug'] ?? ''));
            $allFeatures = (array) ($requirement['all'] ?? []);
            $anyFeatures = (array) ($requirement['any'] ?? []);

            if ($allFeatures !== []) {
                return collect($allFeatures)->every(fn ($feature) => $planManager->hasFeature((string) $feature));
            }

            if ($anyFeatures !== []) {
                return collect($anyFeatures)->contains(fn ($feature) => $planManager->hasFeature((string) $feature));
            }

            return true;
        })
        ->map(function ($module) {
            $items = collect($module['navigation'] ?? [])
                ->filter(function ($item) {
                    if (empty($item['route']) || !\Illuminate\Support\Facades\Route::has($item['route'])) {
                        return false;
                    }

                    $role = $item['role'] ?? null;
                    if (!$role) {
                        return true;
                    }

                    return auth()->check() && auth()->user()->hasRole($role);
                })
                ->values();

            return [
                'slug'  => $module['slug'],
                'icon'  => $module['icon'] ?? null,
                '_dir'  => $module['_dir'] ?? null,
                'name'  => $module['name'],
                'items' => $items,
            ];
        })
        ->filter(fn ($module) => $module['items']->isNotEmpty())
        ->values();
@endphp

@if($appMenuModules->isNotEmpty())
<div class="dropdown">
    <button
        type="button"
        class="topbar-apps-btn"
        data-bs-toggle="dropdown"
        data-bs-auto-close="outside"
        aria-expanded="false"
        aria-label="My Apps"
        title="My Apps"
    >
        <i class="ti ti-layout-grid" aria-hidden="true"></i>
    </button>

    <div class="dropdown-menu dropdown-menu-end topbar-apps-dropdown shadow-sm p-2">
        <div class="px-1 pb-2 mb-1 border-bottom">
            <span class="fw-semibold small text-secondary">Modul Aktif</span>
        </div>
        <div class="row row-cols-3 g-1 mx-0 mt-1">
            @foreach($appMenuModules as $appModule)
                @php $firstRoute = $appModule['items']->first()['route'] ?? null; @endphp
                @if($firstRoute)
                <div class="col px-1">
                    <a href="{{ route($firstRoute) }}"
                       class="topbar-apps-item d-flex flex-column align-items-center text-center text-decoration-none rounded p-2"
                       title="{{ $appModule['name'] }}">
                        <div class="topbar-apps-icon d-flex align-items-center justify-content-center rounded mb-1">
                            @include('shared.module-icon', ['module' => $appModule, 'size' => 26])
                        </div>
                        <span class="topbar-apps-label">{{ $appModule['name'] }}</span>
                    </a>
                </div>
                @endif
            @endforeach
        </div>
    </div>
</div>
@endif
