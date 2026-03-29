@php
    $isPlatformAdminHost = (bool) request()->attributes->get('platform_admin_host');
    $homeRoute = $isPlatformAdminHost ? 'platform.dashboard' : 'dashboard';
    $homeLabel = $isPlatformAdminHost ? 'Platform Dashboard' : 'Dashboard';
@endphp
<li class="nav-item">
    <a class="nav-link d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ request()->routeIs('dashboard') || request()->routeIs('platform.dashboard') ? 'active bg-primary-lt text-primary' : 'bg-body' }}" href="{{ route($homeRoute) }}">
        <span class="nav-link-icon"><i class="ti ti-home-2"></i></span>
        <span class="nav-link-title">{{ $homeLabel }}</span>
    </a>
</li>
@if($isPlatformAdminHost)
<li class="nav-item">
    <a class="nav-link d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ request()->routeIs('platform.tenants.*') ? 'active bg-primary-lt text-primary' : 'bg-body' }}" href="{{ route('platform.tenants.index') }}">
        <span class="nav-link-icon"><i class="ti ti-buildings"></i></span>
        <span class="nav-link-title">Platform Tenants</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ request()->routeIs('platform.plans.*') ? 'active bg-primary-lt text-primary' : 'bg-body' }}" href="{{ route('platform.plans.index') }}">
        <span class="nav-link-icon"><i class="ti ti-badge-dollar-sign"></i></span>
        <span class="nav-link-title">Platform Plans</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ request()->routeIs('platform.orders.*') ? 'active bg-primary-lt text-primary' : 'bg-body' }}" href="{{ route('platform.orders.index') }}">
        <span class="nav-link-icon"><i class="ti ti-receipt-2"></i></span>
        <span class="nav-link-title">Platform Orders</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ request()->routeIs('platform.golive') ? 'active bg-primary-lt text-primary' : 'bg-body' }}" href="{{ route('platform.golive') }}">
        <span class="nav-link-icon"><i class="ti ti-rocket"></i></span>
        <span class="nav-link-title">Go-Live Audit</span>
    </a>
</li>
@endif

@canany(['users.view', 'roles.view', 'modules.view', 'settings.view'])
<li class="nav-item mt-2">
    <div class="text-uppercase text-secondary fw-bold small px-3">Administrasi</div>
</li>
@endcanany

<li class="nav-item">
    <a class="nav-link d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ request()->routeIs('profile.edit') ? 'active bg-primary-lt text-primary' : 'bg-body' }}" href="{{ route('profile.edit') }}">
        <span class="nav-link-icon"><i class="ti ti-user-circle"></i></span>
        <span class="nav-link-title">Profil</span>
    </a>
</li>
@can('settings.view')
    <li class="nav-item">
        <a class="nav-link d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ request()->routeIs('settings.*') ? 'active bg-primary-lt text-primary' : 'bg-body' }}" href="{{ route('settings.general') }}">
            <span class="nav-link-icon"><i class="ti ti-settings"></i></span>
            <span class="nav-link-title">Settings</span>
        </a>
    </li>
@endcan

@can('users.view')
<li class="nav-item">
    <a class="nav-link d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ request()->routeIs('users.*') ? 'active bg-primary-lt text-primary' : 'bg-body' }}" href="{{ route('users.index') }}">
        <span class="nav-link-icon"><i class="ti ti-users"></i></span>
        <span class="nav-link-title">Users</span>
    </a>
</li>
@endcan
@can('roles.view')
<li class="nav-item">
    <a class="nav-link d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ request()->routeIs('roles.*') ? 'active bg-primary-lt text-primary' : 'bg-body' }}" href="{{ route('roles.index') }}">
        <span class="nav-link-icon"><i class="ti ti-shield-check"></i></span>
        <span class="nav-link-title">Roles</span>
    </a>
</li>
@endcan
@can('modules.view')
<li class="nav-item">
    <a class="nav-link d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ request()->routeIs('modules.*') ? 'active bg-primary-lt text-primary' : 'bg-body' }}" href="{{ route('modules.index') }}">
        <span class="nav-link-icon"><i class="ti ti-apps"></i></span>
        <span class="nav-link-title">Modules</span>
    </a>
</li>
@endcan

@if($moduleMenus->isNotEmpty())
<li class="nav-item mt-2">
    <div class="text-uppercase text-secondary fw-bold small px-3">Fitur Aktif</div>
</li>
@endif

@foreach($moduleMenus as $menu)
    @php
        $routes = $menu['items']->pluck('route')->all();
        $isOpen = in_array($currentRouteName, $routes, true);
        $single = $menu['items']->count() === 1;
        $moduleBadgeKey = $menu['items']->pluck('badge')->filter()->first();
        $moduleBadgeCount = $moduleBadgeKey ? (int) ($moduleNavBadges[$moduleBadgeKey] ?? 0) : 0;
    @endphp
    @if($single)
        @php
            $item = $menu['items']->first();
            $badgeKey = $item['badge'] ?? null;
            $badgeCount = $badgeKey ? (int) ($moduleNavBadges[$badgeKey] ?? 0) : 0;
        @endphp
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ $isOpen ? 'active bg-primary-lt text-primary' : 'bg-body' }}" href="{{ route($item['route']) }}">
                <span class="nav-link-icon">
                    @include('shared.module-icon', ['module' => $menu, 'size' => 22])
                </span>
                <span class="nav-link-title">{{ $menu['name'] }}</span>
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
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ $isOpen ? 'active bg-primary-lt text-primary' : 'bg-body' }}" href="#" data-bs-toggle="dropdown" aria-expanded="{{ $isOpen ? 'true' : 'false' }}">
                <span class="nav-link-icon">
                    @include('shared.module-icon', ['module' => $menu, 'size' => 22])
                </span>
                <span class="nav-link-title">{{ $menu['name'] }}</span>
                @if($moduleBadgeKey)
                    @php $useBadgeId = !$moduleBadgeRendered; @endphp
                    @php $moduleBadgeRendered = $moduleBadgeRendered || $useBadgeId; @endphp
                    <span
                        @if($useBadgeId) id="sidebar-module-badge-{{ $moduleBadgeKey }}" @endif
                        data-count="{{ $moduleBadgeCount }}"
                        class="badge bg-red-lt text-red ms-auto {{ $moduleBadgeCount > 0 ? '' : 'd-none' }}">
                        {{ $moduleBadgeCount }}
                    </span>
                @endif
            </a>
            <div class="dropdown-menu position-static border-0 shadow-none px-0 py-1 ms-4 {{ $isOpen ? 'show' : '' }}">
                @foreach($menu['items'] as $item)
                    <a class="dropdown-item px-3 {{ $currentRouteName === $item['route'] ? 'active' : '' }}" href="{{ route($item['route']) }}">{{ $item['label'] }}</a>
                @endforeach
            </div>
        </li>
    @endif
@endforeach
