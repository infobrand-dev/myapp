@php
    $moduleManager = app(\App\Support\ModuleManager::class);
    $planManager   = app(\App\Support\TenantPlanManager::class);
    $currentRouteName = optional(request()->route())->getName();
    $moduleNavBadges  = $moduleNavBadges ?? [];

    $moduleMenus = collect($moduleManager->all())
        ->filter(function ($module) {
            return $module['installed'] && $module['active'];
        })
        ->filter(function ($module) use ($planManager) {
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
        ->map(function ($module) use ($planManager) {
            $items = collect($module['navigation'] ?? [])
                ->filter(function ($item) use ($planManager) {
                    if (empty($item['route']) || !Route::has($item['route'])) {
                        return false;
                    }

                    if (!empty($item['feature']) && !$planManager->hasFeature((string) $item['feature'])) {
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
                'slug'     => $module['slug'],
                'icon'     => $module['icon'] ?? null,
                '_dir'     => $module['_dir'] ?? null,
                'name'     => $module['name'],
                'category' => $module['category'] ?? 'other',
                'items'    => $items,
            ];
        })
        ->filter(fn ($module) => $module['items']->isNotEmpty())
        ->values();

    $moduleMenusByCategory = $moduleMenus->groupBy('category');
@endphp

<aside class="navbar navbar-vertical navbar-expand-lg border-end" role="navigation" aria-label="Main navigation">
    <div class="container-fluid">
        <div class="sidebar-brand-wrap d-flex align-items-center w-100 px-2 py-3 border-bottom">
            <button type="button" class="sidebar-close-btn d-lg-none me-2" id="sidebar-close-btn" aria-label="Tutup menu">
                <i class="ti ti-x" style="font-size:1.1rem;" aria-hidden="true"></i>
            </button>
            <a href="{{ route('dashboard') }}" class="navbar-brand sidebar-brand mb-0 text-decoration-none d-inline-flex align-items-center" aria-label="{{ config('app.name') }}">
                <x-app-logo variant="default" :height="36" class="sidebar-brand-logo" />
                <img src="{{ asset('brand/logo-icon.png') }}" alt="{{ config('app.name') }}" height="32" class="sidebar-brand-icon" />
            </a>
        </div>
        <div class="navbar-collapse" id="sidebar-menu">
            @can('settings.view')
            @if($topbarCompanies->isNotEmpty() || auth()->check())
            <div class="d-lg-none sidebar-ctx-panel">
                @if($topbarCompanies->count() > 1 || $topbarCompanies->isNotEmpty())
                <div class="sidebar-ctx-section">
                    <div class="sidebar-ctx-label">Company</div>
                    <div class="sidebar-ctx-chips">
                        @foreach($topbarCompanies as $company)
                        <form method="POST" action="{{ route('settings.company.switch', $company->id) }}" class="d-contents">
                            @csrf
                            <button type="submit" class="sidebar-ctx-chip {{ optional($topbarCurrentCompany)->id === $company->id ? 'active' : '' }}">
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
                            <button type="submit" class="sidebar-ctx-chip {{ !$topbarCurrentBranch ? 'active' : '' }}">Semua</button>
                        </form>
                        @foreach($topbarBranches as $branch)
                        <form method="POST" action="{{ route('settings.branch.switch', $branch->id) }}" class="d-contents">
                            @csrf
                            <button type="submit" class="sidebar-ctx-chip {{ optional($topbarCurrentBranch)->id === $branch->id ? 'active' : '' }}">
                                {{ $branch->name }}
                            </button>
                        </form>
                        @endforeach
                    </div>
                </div>
                @endif

                <div class="sidebar-ctx-section">
                    <div class="sidebar-ctx-label">Accounting UI</div>
                    <div class="sidebar-ctx-chips">
                        <form method="POST" action="{{ route('settings.accounting-ui-mode') }}" class="d-contents">
                            @csrf
                            <input type="hidden" name="mode" value="standard">
                            <button type="submit" class="sidebar-ctx-chip {{ ($accountingUiMode ?? 'standard') === 'standard' ? 'active' : '' }}">Standard</button>
                        </form>
                        @if($accountingUiModeCanUseAdvanced ?? false)
                            <form method="POST" action="{{ route('settings.accounting-ui-mode') }}" class="d-contents">
                                @csrf
                                <input type="hidden" name="mode" value="advanced">
                                <button type="submit" class="sidebar-ctx-chip {{ ($accountingUiMode ?? 'standard') === 'advanced' ? 'active' : '' }}">Advanced</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
            @endif
            @endcan

            <ul class="navbar-nav pt-lg-3">
                @php $moduleBadgeRendered = false; @endphp
                @include('shared.sidebar-menu')
            </ul>

            <div class="sidebar-footer d-none d-lg-flex">
                <button type="button" class="sidebar-collapse-btn" id="sidebar-collapse-toggle" aria-label="Kecilkan sidebar">
                    <i class="ti ti-layout-sidebar-left-collapse" style="font-size:1rem;" aria-hidden="true"></i>
                    <span class="sidebar-collapse-label">Kecilkan</span>
                </button>
            </div>
        </div>
    </div>
</aside>
