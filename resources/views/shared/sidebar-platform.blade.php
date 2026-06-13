@php
    $currentRouteName = optional(request()->route())->getName();
    $showModulesLink  = (auth()->user()?->hasRole('Super-admin') ?? false);
@endphp

<aside class="navbar navbar-vertical navbar-expand-lg border-end sidebar-platform-shell" role="navigation" aria-label="Platform navigation">
    <div class="container-fluid">
        <div class="sidebar-brand-wrap d-flex align-items-center w-100 px-2 py-3 border-bottom">
            <button type="button" class="sidebar-close-btn d-lg-none me-2" id="sidebar-close-btn" aria-label="Tutup menu">
                <i class="ti ti-x" style="font-size:1.1rem;" aria-hidden="true"></i>
            </button>
            <a href="{{ route('platform.dashboard') }}" class="navbar-brand sidebar-brand mb-0 text-decoration-none d-inline-flex align-items-center" aria-label="{{ config('app.name') }}">
                <x-app-logo variant="default" :height="36" class="sidebar-brand-logo" />
                <img src="{{ asset('brand/logo-icon.png') }}" alt="{{ config('app.name') }}" height="32" class="sidebar-brand-icon" />
            </a>
        </div>

        <div class="navbar-collapse" id="sidebar-menu">
            <ul class="navbar-nav pt-lg-3">
                @include('shared.sidebar-menu-platform')
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
