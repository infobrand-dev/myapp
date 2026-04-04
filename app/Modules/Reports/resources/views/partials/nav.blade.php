<div class="mb-3">
    <div class="nav nav-pills gap-2">
        @php
            $planManager = app(\App\Support\TenantPlanManager::class);
            $hasAdvancedReports = $planManager->hasFeature(\App\Support\PlanFeature::ADVANCED_REPORTS);
        @endphp
        <a href="{{ route('reports.dashboard') }}" class="btn {{ request()->routeIs('reports.dashboard') ? 'btn-primary' : 'btn-outline-primary' }}">Dashboard</a>
        @can('reports.sales')
            @if($hasAdvancedReports)
            <a href="{{ route('reports.sales') }}" class="btn {{ request()->routeIs('reports.sales') ? 'btn-primary' : 'btn-outline-primary' }}">Sales</a>
            @endif
        @endcan
        @can('reports.payments')
            @if($hasAdvancedReports)
            <a href="{{ route('reports.payments') }}" class="btn {{ request()->routeIs('reports.payments') ? 'btn-primary' : 'btn-outline-primary' }}">Payments</a>
            @endif
        @endcan
        @can('reports.inventory')
            @if($hasAdvancedReports && $planManager->hasFeature(\App\Support\PlanFeature::INVENTORY))
            <a href="{{ route('reports.inventory') }}" class="btn {{ request()->routeIs('reports.inventory') ? 'btn-primary' : 'btn-outline-primary' }}">Inventory</a>
            @endif
        @endcan
        @can('reports.purchases')
            @if($hasAdvancedReports && $planManager->hasFeature(\App\Support\PlanFeature::PURCHASES))
            <a href="{{ route('reports.purchases') }}" class="btn {{ request()->routeIs('reports.purchases') ? 'btn-primary' : 'btn-outline-primary' }}">Purchases</a>
            @endif
        @endcan
        @can('reports.finance')
            @if($hasAdvancedReports)
            <a href="{{ route('reports.finance') }}" class="btn {{ request()->routeIs('reports.finance') ? 'btn-primary' : 'btn-outline-primary' }}">Finance</a>
            @endif
        @endcan
        @can('reports.pos')
            @if($hasAdvancedReports && $planManager->hasFeature(\App\Support\PlanFeature::POINT_OF_SALE))
            <a href="{{ route('reports.pos') }}" class="btn {{ request()->routeIs('reports.pos') ? 'btn-primary' : 'btn-outline-primary' }}">POS</a>
            @endif
        @endcan
    </div>
</div>
