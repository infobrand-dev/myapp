@php
    $moduleManager = app(\App\Support\ModuleManager::class);
    $planManager = app(\App\Support\TenantPlanManager::class);
    $currentRouteName = optional(request()->route())->getName();
    $moduleNavBadges = $moduleNavBadges ?? [];
    $platformAdminHost = request()->attributes->get('platform_admin_host');
    $brandHomeRoute = $platformAdminHost ? 'platform.dashboard' : 'dashboard';

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
        <div class="sidebar-brand-wrap d-flex align-items-center w-100 px-1 py-3 border-bottom">
            {{-- Mobile: close button on the left --}}
            <button type="button" class="sidebar-close-btn d-lg-none me-2" id="sidebar-close-btn" aria-label="Tutup menu">
                <i class="ti ti-x" style="font-size:1.1rem;" aria-hidden="true"></i>
            </button>
            <a href="{{ route($brandHomeRoute) }}" class="navbar-brand sidebar-brand mb-0 text-decoration-none d-inline-flex align-items-center" aria-label="{{ config('app.name') }}">
                {{-- Full logo — hidden in mini mode --}}
                <x-app-logo variant="default" :height="34" class="sidebar-brand-logo" />
                {{-- Icon-only logo — shown in mini mode --}}
                <img src="{{ asset('brand/logo-icon.png') }}" alt="{{ config('app.name') }}" height="32" class="sidebar-brand-icon" />
            </a>
        </div>
        <div class="navbar-collapse" id="sidebar-menu">
            @can('settings.view')
            @if($topbarCompanies->isNotEmpty())
            {{-- Mobile context switcher — only visible when sidebar is open on mobile --}}
            <div class="d-lg-none sidebar-ctx-panel">
                @if($topbarCompanies->count() > 1 || $topbarCompanies->isNotEmpty())
                <div class="sidebar-ctx-section">
                    <div class="sidebar-ctx-label">Company</div>
                    <div class="sidebar-ctx-chips">
                        @foreach($topbarCompanies as $company)
                        <form method="POST"
                              action="{{ route('settings.company.switch', $company->id) }}"
                              class="d-contents">
                            @csrf
                            <button type="submit"
                                    class="sidebar-ctx-chip {{ optional($topbarCurrentCompany)->id === $company->id ? 'active' : '' }}">
                                {{ $company->name }}
                            </button>
                        </form>
                        @endforeach
                    </div>
                </div>
                @endif

                @if($topbarCurrentCompany && $topbarBranches->isNotEmpty())
                <div class="sidebar-ctx-section">
                    <div class="sidebar-ctx-label">Branch</div>
                    <div class="sidebar-ctx-chips">
                        <form method="POST" action="{{ route('settings.branch.clear') }}" class="d-contents">
                            @csrf
                            <button type="submit"
                                    class="sidebar-ctx-chip {{ !$topbarCurrentBranch ? 'active' : '' }}">
                                Semua
                            </button>
                        </form>
                        @foreach($topbarBranches as $branch)
                        <form method="POST"
                              action="{{ route('settings.branch.switch', $branch->id) }}"
                              class="d-contents">
                            @csrf
                            <button type="submit"
                                    class="sidebar-ctx-chip {{ optional($topbarCurrentBranch)->id === $branch->id ? 'active' : '' }}">
                                {{ $branch->name }}
                            </button>
                        </form>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
            @endif
            @endcan

            <ul class="navbar-nav pt-lg-3">
                @php $moduleBadgeRendered = false; @endphp
                @include('shared.sidebar-menu')
            </ul>

            {{-- Desktop: collapse toggle — pinned at the bottom of the sidebar --}}
            <div class="sidebar-footer d-none d-lg-flex">
                <button type="button" class="sidebar-collapse-btn" id="sidebar-collapse-toggle" aria-label="Kecilkan sidebar">
                    <i class="ti ti-layout-sidebar-left-collapse" style="font-size:1rem;" aria-hidden="true"></i>
                    <span class="sidebar-collapse-label">Kecilkan</span>
                </button>
            </div>
        </div>
    </div>
</aside>
