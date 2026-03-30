@can('settings.view')
@php
    $selectorId = $selectorId ?? 'topbar-context';
    $hasCompanies = $topbarCompanies->isNotEmpty();
    $hasBranches = $topbarCurrentCompany && $topbarBranches->isNotEmpty();
@endphp
@if($hasCompanies || $hasBranches)
<div class="d-flex align-items-center gap-1 flex-wrap">

    @if($hasCompanies)
    {{-- Company switcher --}}
    <div class="ctx-switcher-group">
        <div class="ctx-switcher-label">Company</div>
        <div class="dropdown">
            <button type="button"
                    class="ctx-switcher-btn"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    title="{{ optional($topbarCurrentCompany)->name ?? 'Pilih company' }}">
                {{ optional($topbarCurrentCompany)->name ?? 'Pilih company' }}
                <i class="ti ti-chevron-down ctx-chevron"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-start shadow-sm ctx-switcher-dropdown" style="min-width:13rem;">
                <li><span class="dropdown-header">Pilih Company</span></li>
                @foreach($topbarCompanies as $company)
                <li>
                    <form method="POST"
                          action="{{ route('settings.company.switch', $company->id) }}"
                          class="d-contents">
                        @csrf
                        <button type="submit"
                                class="dropdown-item {{ optional($topbarCurrentCompany)->id === $company->id ? 'active' : '' }}">
                            {{ $company->name }}
                            @if($company->code)
                            <span class="text-muted ms-1" style="font-size:.72rem;">({{ $company->code }})</span>
                            @endif
                        </button>
                    </form>
                </li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    @if($hasCompanies && $hasBranches)
    <div class="ctx-switcher-divider d-none d-md-block"></div>
    @endif

    @if($hasBranches)
    {{-- Branch switcher --}}
    <div class="ctx-switcher-group">
        <div class="ctx-switcher-label">Branch</div>
        <div class="dropdown">
            <button type="button"
                    class="ctx-switcher-btn"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    title="{{ optional($topbarCurrentBranch)->name ?? 'Semua branch' }}">
                {{ optional($topbarCurrentBranch)->name ?? 'Semua branch' }}
                <i class="ti ti-chevron-down ctx-chevron"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-start shadow-sm ctx-switcher-dropdown" style="min-width:13rem;">
                <li><span class="dropdown-header">Pilih Branch</span></li>
                <li>
                    <form method="POST" action="{{ route('settings.branch.clear') }}" class="d-contents">
                        @csrf
                        <button type="submit"
                                class="dropdown-item {{ !$topbarCurrentBranch ? 'active' : '' }}">
                            <i class="ti ti-building me-1 opacity-50"></i>Semua branch
                        </button>
                    </form>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                @foreach($topbarBranches as $branch)
                <li>
                    <form method="POST"
                          action="{{ route('settings.branch.switch', $branch->id) }}"
                          class="d-contents">
                        @csrf
                        <button type="submit"
                                class="dropdown-item {{ optional($topbarCurrentBranch)->id === $branch->id ? 'active' : '' }}">
                            {{ $branch->name }}
                            @if($branch->code)
                            <span class="text-muted ms-1" style="font-size:.72rem;">({{ $branch->code }})</span>
                            @endif
                        </button>
                    </form>
                </li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

</div>
@endif
@endcan
