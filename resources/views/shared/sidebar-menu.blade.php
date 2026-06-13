@php
    $showUsersLink = auth()->user()?->can('users.view') ?? false;
    $showSettingsLink = auth()->user()?->can('settings.view') ?? false;
    $showTenantAccountHeading = $showUsersLink || $showSettingsLink;
    $showTenantSettingsShortcuts = $showSettingsLink && Route::has('settings.subscription');
    $pendingBillingCount = (int) data_get($topbarPendingBilling ?? [], 'count', 0);
    $currentRouteName = optional(request()->route())->getName();

    $moduleCategoryLabels = [
        'commerce'      => 'Penjualan',
        'communication' => 'Komunikasi',
        'support'       => 'Dukungan',
        'automation'    => 'Otomasi',
        'reporting'     => 'Laporan',
        'finance'       => 'Keuangan',
        'other'         => 'Fitur',
    ];
@endphp

{{-- Dashboard --}}
<li class="nav-item">
    <a class="nav-link d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ request()->routeIs('dashboard') ? 'active bg-primary-lt text-primary' : 'bg-body' }}"
       href="{{ route('dashboard') }}" data-sidebar-label="Dashboard">
        <span class="nav-link-icon"><i class="ti ti-home-2"></i></span>
        <span class="nav-link-title">Dashboard</span>
    </a>
</li>

{{-- Module menus grouped by category --}}
@foreach($moduleMenusByCategory as $moduleCategory => $categoryMenus)
<li class="nav-item mt-2">
    <div class="text-uppercase text-secondary fw-bold small px-3">{{ $moduleCategoryLabels[$moduleCategory] ?? ucfirst($moduleCategory) }}</div>
</li>
@foreach($categoryMenus as $menu)
    @php
        $routes = $menu['items']->pluck('route')->all();
        $isOpen = in_array($currentRouteName, $routes, true);
        $single = $menu['items']->count() === 1;
        $moduleBadgeKey   = $menu['items']->pluck('badge')->filter()->first();
        $moduleBadgeCount = $moduleBadgeKey ? (int) ($moduleNavBadges[$moduleBadgeKey] ?? 0) : 0;
    @endphp
    @if($single)
        @php
            $item      = $menu['items']->first();
            $itemLabel = $item['label'] ?? $menu['name'];
            $badgeKey  = $item['badge'] ?? null;
            $badgeCount = $badgeKey ? (int) ($moduleNavBadges[$badgeKey] ?? 0) : 0;
        @endphp
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ $isOpen ? 'active bg-primary-lt text-primary' : 'bg-body' }}"
               href="{{ route($item['route']) }}" data-sidebar-label="{{ $itemLabel }}">
                <span class="nav-link-icon">
                    @include('shared.module-icon', ['module' => $menu, 'size' => 22])
                </span>
                <span class="nav-link-title">{{ $itemLabel }}</span>
                @if($badgeKey)
                    @php $useBadgeId = !$moduleBadgeRendered; @endphp
                    @php $moduleBadgeRendered = $moduleBadgeRendered || $useBadgeId; @endphp
                    <span
                        @if($useBadgeId) id="sidebar-module-badge-{{ $badgeKey }}" @endif
                        data-count="{{ $badgeCount }}"
                        class="badge bg-red-lt text-red ms-auto {{ $badgeCount > 0 ? '' : 'd-none' }}">
                        {{ $badgeCount }}
                    </span>
                @endif
            </a>
        </li>
    @else
        <li class="nav-item sidebar-dropdown">
            <a class="nav-link sidebar-dropdown-toggle d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ $isOpen ? 'active bg-primary-lt text-primary' : 'bg-body' }}"
               href="#" data-sidebar-label="{{ $menu['name'] }}">
                <span class="nav-link-icon">
                    @include('shared.module-icon', ['module' => $menu, 'size' => 22])
                </span>
                <span class="nav-link-title">{{ $menu['name'] }}</span>
                <i class="ti ti-chevron-right sidebar-dropdown-arrow ms-auto" aria-hidden="true"></i>
                @if($moduleBadgeKey)
                    @php $useBadgeId = !$moduleBadgeRendered; @endphp
                    @php $moduleBadgeRendered = $moduleBadgeRendered || $useBadgeId; @endphp
                    <span
                        @if($useBadgeId) id="sidebar-module-badge-{{ $moduleBadgeKey }}" @endif
                        data-count="{{ $moduleBadgeCount }}"
                        class="badge bg-red-lt text-red {{ $moduleBadgeCount > 0 ? '' : 'd-none' }}">
                        {{ $moduleBadgeCount }}
                    </span>
                @endif
            </a>
            <div class="sidebar-dropdown-menu px-0 py-1 ms-4 {{ $isOpen ? 'open' : '' }}">
                @foreach($menu['items'] as $item)
                    <a class="sidebar-dropdown-item px-3 {{ $currentRouteName === $item['route'] ? 'active' : '' }}"
                       href="{{ route($item['route']) }}">{{ $item['label'] }}</a>
                @endforeach
            </div>
            <div class="sidebar-flyout" data-module="{{ $menu['name'] }}">
                <div class="sidebar-flyout-label">{{ $menu['name'] }}</div>
                @foreach($menu['items'] as $item)
                    <a class="sidebar-flyout-item {{ $currentRouteName === $item['route'] ? 'active' : '' }}"
                       href="{{ route($item['route']) }}">{{ $item['label'] }}</a>
                @endforeach
            </div>
        </li>
    @endif
@endforeach {{-- end @foreach($categoryMenus as $menu) --}}
@endforeach {{-- end @foreach($moduleMenusByCategory as $moduleCategory => $categoryMenus) --}}

{{-- Akun & Settings --}}
@if($showTenantAccountHeading)
<li class="nav-item mt-2">
    <div class="text-uppercase text-secondary fw-bold small px-3">Akun & Settings</div>
</li>
@endif

@if($showUsersLink)
<li class="nav-item">
    <a class="nav-link d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ request()->routeIs('users.*') ? 'active bg-primary-lt text-primary' : 'bg-body' }}"
       href="{{ route('users.index') }}" data-sidebar-label="Users">
        <span class="nav-link-icon"><i class="ti ti-users"></i></span>
        <span class="nav-link-title">Users</span>
    </a>
</li>
@endif

@if($showTenantSettingsShortcuts)
<li class="nav-item">
    <a class="nav-link d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ request()->routeIs('settings.subscription') || request()->routeIs('settings.addons') ? 'active bg-primary-lt text-primary' : 'bg-body' }}"
       href="{{ route('settings.subscription') }}" data-sidebar-label="Subscription & Billing">
        <span class="nav-link-icon"><i class="ti ti-credit-card"></i></span>
        <span class="nav-link-title">Subscription & Billing</span>
        @if($pendingBillingCount > 0)
            <span class="badge bg-red-lt text-red ms-auto">{{ $pendingBillingCount }}</span>
        @endif
    </a>
</li>
<li class="nav-item sidebar-dropdown">
    <a class="nav-link sidebar-dropdown-toggle d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ request()->routeIs('settings.general') || request()->routeIs('settings.company') || request()->routeIs('settings.branch') || request()->routeIs('settings.documents') || request()->routeIs('settings.access') ? 'active bg-primary-lt text-primary' : 'bg-body' }}"
       href="#" data-sidebar-label="Settings">
        <span class="nav-link-icon"><i class="ti ti-settings"></i></span>
        <span class="nav-link-title">Settings</span>
        <i class="ti ti-chevron-right sidebar-dropdown-arrow ms-auto" aria-hidden="true"></i>
    </a>
    <div class="sidebar-dropdown-menu px-0 py-1 ms-4 {{ request()->routeIs('settings.general') || request()->routeIs('settings.company') || request()->routeIs('settings.branch') || request()->routeIs('settings.documents') || request()->routeIs('settings.access') ? 'open' : '' }}">
        <a class="sidebar-dropdown-item px-3 {{ request()->routeIs('settings.general') ? 'active' : '' }}" href="{{ route('settings.general') }}">General</a>
        <a class="sidebar-dropdown-item px-3 {{ request()->routeIs('settings.documents') ? 'active' : '' }}" href="{{ route('settings.documents') }}">Documents & Invoice</a>
        <a class="sidebar-dropdown-item px-3 {{ request()->routeIs('settings.company') ? 'active' : '' }}" href="{{ route('settings.company') }}">Company</a>
        <a class="sidebar-dropdown-item px-3 {{ request()->routeIs('settings.branch') ? 'active' : '' }}" href="{{ route('settings.branch') }}">Branch</a>
        <a class="sidebar-dropdown-item px-3 {{ request()->routeIs('settings.access') ? 'active' : '' }}" href="{{ route('settings.access') }}">Users & Access</a>
    </div>
    <div class="sidebar-flyout" data-module="Settings">
        <div class="sidebar-flyout-label">Settings</div>
        <a class="sidebar-flyout-item {{ request()->routeIs('settings.general') ? 'active' : '' }}" href="{{ route('settings.general') }}">General</a>
        <a class="sidebar-flyout-item {{ request()->routeIs('settings.documents') ? 'active' : '' }}" href="{{ route('settings.documents') }}">Documents & Invoice</a>
        <a class="sidebar-flyout-item {{ request()->routeIs('settings.company') ? 'active' : '' }}" href="{{ route('settings.company') }}">Company</a>
        <a class="sidebar-flyout-item {{ request()->routeIs('settings.branch') ? 'active' : '' }}" href="{{ route('settings.branch') }}">Branch</a>
        <a class="sidebar-flyout-item {{ request()->routeIs('settings.access') ? 'active' : '' }}" href="{{ route('settings.access') }}">Users & Access</a>
    </div>
</li>
@elseif($showSettingsLink)
<li class="nav-item">
    <a class="nav-link d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ request()->routeIs('settings.*') ? 'active bg-primary-lt text-primary' : 'bg-body' }}"
       href="{{ route('settings.general') }}" data-sidebar-label="Settings">
        <span class="nav-link-icon"><i class="ti ti-settings"></i></span>
        <span class="nav-link-title">Settings</span>
    </a>
</li>
@endif

<li class="nav-item">
    <a class="nav-link d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ request()->routeIs('profile.edit') ? 'active bg-primary-lt text-primary' : 'bg-body' }}"
       href="{{ route('profile.edit') }}" data-sidebar-label="Profil">
        <span class="nav-link-icon"><i class="ti ti-user-circle"></i></span>
        <span class="nav-link-title">Profil</span>
    </a>
</li>
