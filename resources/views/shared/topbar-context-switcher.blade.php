@php
    $selectorId = $selectorId ?? 'topbar-context';
    $canSwitchContext = auth()->user()?->can('settings.view') ?? false;
    $hasCompanies = $topbarCompanies->isNotEmpty();
    $hasBranches = $topbarCurrentCompany && ($topbarBranches->isNotEmpty() || $topbarCurrentBranch);
    $switcherWidth = '11.5rem';
@endphp
@if($hasCompanies || $hasBranches)
<div class="d-flex align-items-center gap-1 flex-wrap">

    @if($hasCompanies)
    <div class="ctx-switcher-group" style="min-width:{{ $switcherWidth }};">
        <div class="ctx-switcher-label">Company</div>
        @if($canSwitchContext)
            <div class="dropdown" style="width:100%;">
                <button type="button"
                        class="ctx-switcher-btn"
                        style="min-width:{{ $switcherWidth }};"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        title="{{ optional($topbarCurrentCompany)->name ?? 'Pilih company' }}">
                    <span class="text-truncate d-inline-block" style="max-width:calc(100% - 1.25rem); vertical-align:bottom;">
                        {{ optional($topbarCurrentCompany)->name ?? 'Pilih company' }}
                    </span>
                    <i class="ti ti-chevron-down ctx-chevron"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-start shadow-sm ctx-switcher-dropdown">
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
        @else
            <div class="ctx-switcher-btn pe-none" style="width:100%; min-width:{{ $switcherWidth }};" title="{{ optional($topbarCurrentCompany)->name ?? 'Belum ada company aktif' }}">
                <span class="text-truncate d-inline-block" style="max-width:calc(100% - 1.25rem); vertical-align:bottom;">
                    {{ optional($topbarCurrentCompany)->name ?? 'Belum ada company aktif' }}
                </span>
            </div>
        @endif
    </div>
    @endif

    @if($hasCompanies && $hasBranches)
    <div class="ctx-switcher-divider d-none d-md-block"></div>
    @endif

    @if($hasBranches)
    <div class="ctx-switcher-group" style="min-width:{{ $switcherWidth }};">
        <div class="ctx-switcher-label">Branch</div>
        @if($canSwitchContext)
            <div class="dropdown" style="width:100%;">
                <button type="button"
                        class="ctx-switcher-btn"
                        style="min-width:{{ $switcherWidth }};"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        title="{{ optional($topbarCurrentBranch)->name ?? 'Semua branch' }}">
                    <span class="text-truncate d-inline-block" style="max-width:calc(100% - 1.25rem); vertical-align:bottom;">
                        {{ optional($topbarCurrentBranch)->name ?? 'Semua branch' }}
                    </span>
                    <i class="ti ti-chevron-down ctx-chevron"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-start shadow-sm ctx-switcher-dropdown">
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
        @else
            <div class="ctx-switcher-btn pe-none" style="width:100%; min-width:{{ $switcherWidth }};" title="{{ optional($topbarCurrentBranch)->name ?? 'Semua branch' }}">
                <span class="text-truncate d-inline-block" style="max-width:calc(100% - 1.25rem); vertical-align:bottom;">
                    {{ optional($topbarCurrentBranch)->name ?? 'Semua branch' }}
                </span>
            </div>
        @endif
    </div>
    @endif

</div>
@endif
