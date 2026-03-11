@php
    $moduleManager = app(\App\Support\ModuleManager::class);
    $currentRouteName = optional(request()->route())->getName();
    $moduleNavBadges = $moduleNavBadges ?? [];

    $moduleMenus = collect($moduleManager->all())
        ->filter(fn ($module) => $module['installed'] && $module['active'])
        ->map(function ($module) {
            $items = collect($module['navigation'] ?? [])
                ->filter(function ($item) {
                    if (empty($item['route']) || !Route::has($item['route'])) {
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
                'slug' => $module['slug'],
                'name' => $module['name'],
                'items' => $items,
            ];
        })
        ->filter(fn ($module) => $module['items']->isNotEmpty())
        ->values();
@endphp

<aside class="navbar navbar-vertical navbar-expand-lg border-end">
    <div class="container-fluid">
        <div class="sidebar-brand-wrap d-none d-lg-flex align-items-center justify-content-between w-100 px-1 py-3 border-bottom">
            <a href="{{ route('dashboard') }}" class="navbar-brand sidebar-brand mb-0 text-decoration-none">MyApp</a>
        </div>
        <div class="collapse navbar-collapse" id="sidebar-menu">
            <ul class="navbar-nav pt-lg-3">
                @php $moduleBadgeRendered = false; @endphp
                @include('shared.sidebar-menu')
            </ul>
        </div>
    </div>
</aside>
