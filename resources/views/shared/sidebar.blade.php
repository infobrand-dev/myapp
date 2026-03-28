@php
    $moduleManager = app(\App\Support\ModuleManager::class);
    $planManager = app(\App\Support\TenantPlanManager::class);
    $currentRouteName = optional(request()->route())->getName();
    $moduleNavBadges = $moduleNavBadges ?? [];
    $platformAdminHost = request()->attributes->get('platform_admin_host');

    $moduleMenus = collect($moduleManager->all())
        ->filter(function ($module) use ($planManager, $platformAdminHost) {
            if (!$module['installed'] || !$module['active']) {
                return false;
            }

            if ($platformAdminHost) {
                return true;
            }

            $feature = \App\Support\PlanFeature::moduleFeatureForSlug((string) ($module['slug'] ?? ''));

            return $feature ? $planManager->hasFeature($feature) : true;
        })
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
                'icon' => $module['icon'] ?? null,
                '_dir' => $module['_dir'] ?? null,
                'name' => $module['name'],
                'items' => $items,
            ];
        })
        ->filter(fn ($module) => $module['items']->isNotEmpty())
        ->values();
@endphp

<aside class="navbar navbar-vertical navbar-expand-lg border-end" role="navigation" aria-label="Main navigation">
    <div class="container-fluid">
        <div class="sidebar-brand-wrap d-none d-lg-flex align-items-center justify-content-between w-100 px-1 py-3 border-bottom">
            <a href="{{ route('dashboard') }}" class="navbar-brand sidebar-brand mb-0 text-decoration-none">{{ config('app.name') }}</a>
        </div>
        {{--
            Use "collapse navbar-collapse" (NOT "d-block") so Bootstrap Collapse
            can properly hide/show on mobile. On desktop (≥992px) theme.css forces
            display:block regardless of Bootstrap collapse state.
        --}}
        <div class="collapse navbar-collapse" id="sidebar-menu">
            <ul class="navbar-nav pt-lg-3">
                @php $moduleBadgeRendered = false; @endphp
                @include('shared.sidebar-menu')
            </ul>
        </div>
    </div>
</aside>
