@php
    $currentRouteName = optional(request()->route())->getName();
    $showModulesLink  = (auth()->user()?->hasRole('Super-admin') ?? false);
@endphp

{{-- Dashboard --}}
<li class="nav-item">
    <a class="nav-link d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ request()->routeIs('platform.dashboard') ? 'active bg-primary-lt text-primary' : 'bg-body' }}"
       href="{{ route('platform.dashboard') }}" data-sidebar-label="Dashboard">
        <span class="nav-link-icon"><i class="ti ti-home-2"></i></span>
        <span class="nav-link-title">Dashboard</span>
    </a>
</li>

{{-- Platform --}}
<li class="nav-item mt-2">
    <div class="text-uppercase text-secondary fw-bold small px-3">Platform</div>
</li>
<li class="nav-item">
    <a class="nav-link d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ request()->routeIs('platform.tenants.*') ? 'active bg-primary-lt text-primary' : 'bg-body' }}"
       href="{{ route('platform.tenants.index') }}" data-sidebar-label="Tenants">
        <span class="nav-link-icon"><i class="ti ti-buildings"></i></span>
        <span class="nav-link-title">Tenants</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ request()->routeIs('platform.plans.*') ? 'active bg-primary-lt text-primary' : 'bg-body' }}"
       href="{{ route('platform.plans.index') }}" data-sidebar-label="Plans">
        <span class="nav-link-icon"><i class="ti ti-badge-dollar-sign"></i></span>
        <span class="nav-link-title">Plans</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ request()->routeIs('platform.orders.*') ? 'active bg-primary-lt text-primary' : 'bg-body' }}"
       href="{{ route('platform.orders.index') }}" data-sidebar-label="Orders">
        <span class="nav-link-icon"><i class="ti ti-receipt-2"></i></span>
        <span class="nav-link-title">Orders</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ request()->routeIs('platform.affiliates.index') ? 'active bg-primary-lt text-primary' : 'bg-body' }}"
       href="{{ route('platform.affiliates.index') }}" data-sidebar-label="Affiliates">
        <span class="nav-link-icon"><i class="ti ti-share-2"></i></span>
        <span class="nav-link-title">Affiliates</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ request()->routeIs('platform.affiliates.payouts') ? 'active bg-primary-lt text-primary' : 'bg-body' }}"
       href="{{ route('platform.affiliates.payouts') }}" data-sidebar-label="Payouts">
        <span class="nav-link-icon"><i class="ti ti-cash-banknote"></i></span>
        <span class="nav-link-title">Payouts</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ request()->routeIs('platform.promos.*') ? 'active bg-primary-lt text-primary' : 'bg-body' }}"
       href="{{ route('platform.promos.index') }}" data-sidebar-label="Promos">
        <span class="nav-link-icon"><i class="ti ti-ticket"></i></span>
        <span class="nav-link-title">Promos</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ request()->routeIs('platform.golive') ? 'active bg-primary-lt text-primary' : 'bg-body' }}"
       href="{{ route('platform.golive') }}" data-sidebar-label="Go-Live">
        <span class="nav-link-icon"><i class="ti ti-rocket"></i></span>
        <span class="nav-link-title">Go-Live</span>
    </a>
</li>

{{-- Infrastruktur --}}
<li class="nav-item mt-2">
    <div class="text-uppercase text-secondary fw-bold small px-3">Infrastruktur</div>
</li>
<li class="nav-item">
    <a class="nav-link d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ request()->routeIs('platform.domains.*') ? 'active bg-primary-lt text-primary' : 'bg-body' }}"
       href="{{ route('platform.domains.index') }}" data-sidebar-label="Domains">
        <span class="nav-link-icon"><i class="ti ti-world"></i></span>
        <span class="nav-link-title">Domains</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ request()->routeIs('platform.storage.*') ? 'active bg-primary-lt text-primary' : 'bg-body' }}"
       href="{{ route('platform.storage.index') }}" data-sidebar-label="Storage">
        <span class="nav-link-icon"><i class="ti ti-database"></i></span>
        <span class="nav-link-title">Storage</span>
    </a>
</li>
@if($showModulesLink)
<li class="nav-item">
    <a class="nav-link d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ request()->routeIs('modules.*') ? 'active bg-primary-lt text-primary' : 'bg-body' }}"
       href="{{ route('modules.index') }}" data-sidebar-label="Modules">
        <span class="nav-link-icon"><i class="ti ti-package"></i></span>
        <span class="nav-link-title">Modules</span>
    </a>
</li>
@endif

{{-- Akun --}}
<li class="nav-item mt-2">
    <div class="text-uppercase text-secondary fw-bold small px-3">Akun</div>
</li>
<li class="nav-item">
    <a class="nav-link d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ request()->routeIs('profile.edit') ? 'active bg-primary-lt text-primary' : 'bg-body' }}"
       href="{{ route('profile.edit') }}" data-sidebar-label="Profil">
        <span class="nav-link-icon"><i class="ti ti-user-circle"></i></span>
        <span class="nav-link-title">Profil</span>
    </a>
</li>
