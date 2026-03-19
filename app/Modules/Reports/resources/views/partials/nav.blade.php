<div class="mb-3">
    <div class="nav nav-pills gap-2">
        <a href="{{ route('reports.dashboard') }}" class="btn {{ request()->routeIs('reports.dashboard') ? 'btn-primary' : 'btn-outline-primary' }}">Dashboard</a>
        @can('reports.sales')
            <a href="{{ route('reports.sales') }}" class="btn {{ request()->routeIs('reports.sales') ? 'btn-primary' : 'btn-outline-primary' }}">Sales</a>
        @endcan
        @can('reports.payments')
            <a href="{{ route('reports.payments') }}" class="btn {{ request()->routeIs('reports.payments') ? 'btn-primary' : 'btn-outline-primary' }}">Payments</a>
        @endcan
        @can('reports.inventory')
            <a href="{{ route('reports.inventory') }}" class="btn {{ request()->routeIs('reports.inventory') ? 'btn-primary' : 'btn-outline-primary' }}">Inventory</a>
        @endcan
        @can('reports.purchases')
            <a href="{{ route('reports.purchases') }}" class="btn {{ request()->routeIs('reports.purchases') ? 'btn-primary' : 'btn-outline-primary' }}">Purchases</a>
        @endcan
        @can('reports.finance')
            <a href="{{ route('reports.finance') }}" class="btn {{ request()->routeIs('reports.finance') ? 'btn-primary' : 'btn-outline-primary' }}">Finance</a>
        @endcan
        @can('reports.pos')
            <a href="{{ route('reports.pos') }}" class="btn {{ request()->routeIs('reports.pos') ? 'btn-primary' : 'btn-outline-primary' }}">POS</a>
        @endcan
    </div>
</div>
