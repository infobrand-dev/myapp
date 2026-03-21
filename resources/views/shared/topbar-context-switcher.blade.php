@can('settings.view')
    @php
        $selectorId = $selectorId ?? 'topbar-context';
        $tenantName = optional($topbarTenant)->name ?? 'Tenant';
    @endphp
    <div class="d-flex align-items-center flex-wrap gap-2">
        <span class="badge bg-blue-lt text-blue">{{ $tenantName }}</span>

        @if($topbarCompanies->isNotEmpty())
            <form method="POST" action="{{ route('settings.company.switch', optional($topbarCurrentCompany)->id ?: $topbarCompanies->first()->id) }}" id="{{ $selectorId }}-company-form">
                @csrf
                <select
                    class="form-select form-select-sm"
                    aria-label="Switch company"
                    onchange="this.form.action='{{ url('/settings/company/switch') }}/' + this.value; this.form.submit();"
                >
                    @foreach($topbarCompanies as $company)
                        <option value="{{ $company->id }}" @selected(optional($topbarCurrentCompany)->id === $company->id)>
                            {{ $company->name }}{{ $company->code ? ' (' . $company->code . ')' : '' }}
                        </option>
                    @endforeach
                </select>
            </form>
        @endif

        @if($topbarCurrentCompany && $topbarBranches->isNotEmpty())
            <form method="POST" action="{{ route('settings.branch.switch', optional($topbarCurrentBranch)->id ?: $topbarBranches->first()->id) }}" id="{{ $selectorId }}-branch-form">
                @csrf
                <select
                    class="form-select form-select-sm"
                    aria-label="Switch branch"
                    onchange="
                        if (this.value === '') {
                            this.form.action='{{ route('settings.branch.clear') }}';
                        } else {
                            this.form.action='{{ url('/settings/branch/switch') }}/' + this.value;
                        }
                        this.form.submit();
                    "
                >
                    <option value="">All company branches</option>
                    @foreach($topbarBranches as $branch)
                        <option value="{{ $branch->id }}" @selected(optional($topbarCurrentBranch)->id === $branch->id)>
                            {{ $branch->name }}{{ $branch->code ? ' (' . $branch->code . ')' : '' }}
                        </option>
                    @endforeach
                </select>
            </form>
        @endif
    </div>
@endcan
