<div class="card mb-4">
    <div class="card-body p-2">
        <div class="d-flex flex-nowrap gap-2 overflow-auto">
            <a href="{{ route('crm.dashboard') }}" class="btn {{ request()->routeIs('crm.dashboard') ? 'btn-primary' : 'btn-outline-secondary' }}">Dashboard</a>
            <a href="{{ route('crm.index') }}" class="btn {{ request()->routeIs('crm.index', 'crm.show', 'crm.create', 'crm.edit') ? 'btn-primary' : 'btn-outline-secondary' }}">Leads/Deals</a>
            <a href="{{ route('crm.follow-ups') }}" class="btn {{ request()->routeIs('crm.follow-ups*') ? 'btn-primary' : 'btn-outline-secondary' }}">Follow-Up</a>
            <a href="{{ route('crm.customers') }}" class="btn {{ request()->routeIs('crm.customers*') ? 'btn-primary' : 'btn-outline-secondary' }}">Customers</a>
            <a href="{{ route('crm.pipelines') }}" class="btn {{ request()->routeIs('crm.pipelines*') ? 'btn-primary' : 'btn-outline-secondary' }}">Pipelines</a>
            <a href="{{ route('crm.settings') }}" class="btn {{ request()->routeIs('crm.settings') ? 'btn-primary' : 'btn-outline-secondary' }}">Settings</a>
        </div>
    </div>
</div>
