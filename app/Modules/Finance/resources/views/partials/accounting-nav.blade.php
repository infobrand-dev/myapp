@php
    $isAdvancedMode = ($accountingUiMode ?? 'standard') === 'advanced';
@endphp

<div class="mb-3">
    <div class="nav nav-pills gap-2 flex-wrap">
        @can('finance.view')
            <a href="{{ route('finance.transactions.index') }}" class="btn {{ request()->routeIs('finance.transactions.*') ? 'btn-primary' : 'btn-outline-primary' }}">Transactions</a>
        @endcan
        @can('finance.manage-categories')
            <a href="{{ route('finance.accounts.index') }}" class="btn {{ request()->routeIs('finance.accounts.*') ? 'btn-primary' : 'btn-outline-primary' }}">Finance Accounts</a>
            <a href="{{ route('finance.categories.index') }}" class="btn {{ request()->routeIs('finance.categories.*') ? 'btn-primary' : 'btn-outline-primary' }}">Categories</a>
        @endcan
        @can('finance.manage-coa')
            <a href="{{ route('finance.chart-accounts.index') }}" class="btn {{ request()->routeIs('finance.chart-accounts.*') ? 'btn-primary' : 'btn-outline-primary' }}">Chart of Accounts</a>
        @endcan
        @can('finance.manage-tax')
            <a href="{{ route('finance.taxes.index') }}" class="btn {{ request()->routeIs('finance.taxes.*') ? 'btn-primary' : 'btn-outline-primary' }}">Taxes</a>
        @endcan
        @can('finance.view-journal')
            <a href="{{ route('finance.journals.index') }}" class="btn {{ request()->routeIs('finance.journals.*') ? 'btn-primary' : 'btn-outline-primary' }}">Journals</a>
        @endcan
        @can('finance.approve-sensitive-transactions')
            <a href="{{ route('finance.approvals.index') }}" class="btn {{ request()->routeIs('finance.approvals.*') ? 'btn-primary' : 'btn-outline-primary' }}">Approvals</a>
        @endcan
        @can('finance.manage-period-locks')
            <a href="{{ route('finance.period-locks.index') }}" class="btn {{ request()->routeIs('finance.period-locks.*') ? 'btn-primary' : 'btn-outline-primary' }}">Period Locks</a>
        @endcan
        @can('reports.finance')
            <a href="{{ route('reports.finance') }}" class="btn {{ request()->routeIs('reports.finance') ? 'btn-primary' : 'btn-outline-primary' }}">
                Finance Reports
                <span class="ms-1 badge {{ $isAdvancedMode ? 'bg-blue-lt text-blue' : 'bg-secondary-lt text-secondary' }}">
                    {{ $isAdvancedMode ? 'Advanced' : 'Standard' }}
                </span>
            </a>
        @endcan
    </div>
</div>
