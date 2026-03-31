@extends('layouts.admin')

@section('content')
@php($hooks = app(\App\Support\HookManager::class))
@php($money = app(\App\Support\MoneyFormatter::class))

<div class="dashboard-shell">
    <div class="row g-3">

        {{-- ── Hero ── --}}
        <div class="col-12">
            <div class="dashboard-hero p-4 p-lg-5">
                <div class="row align-items-center g-4">
                    <div class="col-lg-8">
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <span class="dashboard-chip"><i class="ti ti-calendar"></i> {{ now()->translatedFormat('l, d F Y') }}</span>
                            <span class="dashboard-chip"><i class="ti ti-clock"></i> {{ now()->translatedFormat('H:i') }}</span>
                        </div>
                        <h1 class="mb-2" style="font-size: clamp(1.8rem, 3vw, 2.5rem); color: var(--db-ink);">
                            Selamat datang, {{ auth()->user()->name }}.
                        </h1>
                        <p class="mb-0" style="max-width: 44rem; color: var(--db-muted); font-size: 1rem;">
                            @if($isPrivileged)
                                Pantau kondisi workspace, modul yang berjalan, dan aktivitas tim Anda dari sini.
                            @else
                                Akses fitur yang tersedia untuk akun Anda dan mulai bekerja dari sini.
                            @endif
                        </p>
                    </div>
                    <div class="col-lg-4">
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="dashboard-mini-stat">
                                    <div class="text-secondary small">Role Anda</div>
                                    <div class="fw-bold mt-1 text-truncate">{{ auth()->user()->getRoleNames()->first() ?: '—' }}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="dashboard-mini-stat">
                                    <div class="text-secondary small">Modul Aktif</div>
                                    <div class="fw-bold mt-1">{{ $activeModules->count() }}</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="dashboard-mini-stat">
                                    <div class="d-flex justify-content-between align-items-center gap-3">
                                        <div class="min-w-0">
                                            <div class="text-secondary small">Sesi aktif</div>
                                            <div class="fw-semibold mt-1 text-truncate">{{ auth()->user()->email }}</div>
                                        </div>
                                        <span class="badge bg-green-lt text-green flex-shrink-0">Online</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Stats dari controller ── --}}
        @foreach($stats as $stat)
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="dashboard-kpi p-3 p-lg-4 h-100">
                    <div class="d-flex align-items-start justify-content-between gap-3">
                        <div class="min-w-0">
                            <div class="text-secondary text-uppercase small fw-bold">{{ $stat['label'] }}</div>
                            <div class="mt-2 fw-bold text-truncate" style="font-size: 1.9rem; line-height: 1; color: var(--db-ink);">{{ $stat['value'] }}</div>
                            <div class="text-muted small mt-2">{{ $stat['meta'] }}</div>
                        </div>
                        <span class="badge bg-{{ $stat['tone'] }}-lt text-{{ $stat['tone'] }} flex-shrink-0">{{ ucfirst($stat['tone']) }}</span>
                    </div>
                </div>
            </div>
        @endforeach

        {{-- ── Card dari modul (injected via hook) ── --}}
        @foreach($hooks->render('dashboard.overview.cards', ['user' => auth()->user()]) as $hookedCard)
            {!! $hookedCard !!}
        @endforeach

        {{-- ── Kredit AI (compact card, hanya jika aktif di paket) ── --}}
        @php
            $aiEnabled  = $aiCredits['enabled'] ?? false;
            $aiStatus   = $aiCredits['status'] ?? 'ok';
            $aiRemain   = $aiCredits['remaining'] ?? null;
            $aiUsed     = $aiCredits['used'] ?? 0;
            $aiAvail    = $aiCredits['available'] ?? null;
            $aiTopUp    = $aiCredits['top_up'] ?? 0;
            $aiPct      = ($aiAvail > 0) ? min(100, (int) round($aiUsed / $aiAvail * 100)) : ($aiAvail === 0 ? 100 : 0);
            if (in_array($aiStatus, ['at_limit', 'over_limit'])) {
                $aiBadgeClass = 'bg-red-lt text-red';
                $aiBadgeLabel = 'Habis';
                $aiBarColor   = 'bg-red';
            } elseif ($aiStatus === 'near_limit') {
                $aiBadgeClass = 'bg-orange-lt text-orange';
                $aiBadgeLabel = 'Hampir habis';
                $aiBarColor   = 'bg-orange';
            } else {
                $aiBadgeClass = 'bg-azure-lt text-azure';
                $aiBadgeLabel = 'Normal';
                $aiBarColor   = $aiPct >= 90 ? 'bg-red' : ($aiPct >= 70 ? 'bg-orange' : 'bg-azure');
            }
        @endphp
        @if($aiEnabled)
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="dashboard-kpi p-3 p-lg-4 h-100 d-flex flex-column">
                <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                    <div class="text-secondary text-uppercase small fw-bold">Kredit AI</div>
                    <span class="badge {{ $aiBadgeClass }} flex-shrink-0">{{ $aiBadgeLabel }}</span>
                </div>

                @if($aiRemain === null)
                    <div class="fw-bold" style="font-size:2rem; line-height:1; color:var(--db-ink);">∞</div>
                    <div class="text-muted small mt-1">Tanpa batas bulan ini</div>
                @else
                    <div class="fw-bold" style="font-size:2rem; line-height:1; color:var(--db-ink);">{{ number_format($aiRemain) }}</div>
                    <div class="text-muted small mt-1">tersisa dari {{ number_format($aiAvail) }}</div>
                    <div class="progress progress-sm my-2">
                        <div class="progress-bar {{ $aiBarColor }}" style="width:{{ $aiPct }}%"></div>
                    </div>
                    <div class="d-flex justify-content-between small text-muted">
                        <span>Terpakai: {{ number_format($aiUsed) }}</span>
                        @if($aiTopUp > 0)
                            <span>Top up: +{{ number_format($aiTopUp) }}</span>
                        @endif
                    </div>
                @endif

                <div class="mt-auto pt-2">
                    @if(!empty($aiCredits['advice']['tenant_cta']))
                        <div class="text-muted small mb-2">{{ $aiCredits['advice']['tenant_cta'] }}</div>
                    @endif
                    @if(Route::has('settings.subscription'))
                        <a href="{{ route('settings.subscription') }}" class="btn btn-sm btn-outline-azure w-100">Kelola Kredit</a>
                    @endif
                </div>
            </div>
        </div>
        @endif

    </div>

    {{-- ── Panel bawah ── --}}
    <div class="row g-3 mt-1">

        {{-- Kiri: Pengguna terbaru / Ringkasan akun --}}
        <div class="col-12 col-xl-7">
            <div class="dashboard-panel p-3 p-lg-4 h-100">
                <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                    <div>
                        <div class="text-secondary text-uppercase small fw-bold">{{ $isPrivileged ? 'Pengguna Terbaru' : 'Akun Anda' }}</div>
                        <h3 class="mb-0">{{ $isPrivileged ? 'Bergabung belakangan ini' : 'Ringkasan akun' }}</h3>
                    </div>
                    <span class="dashboard-chip">{{ $isPrivileged ? $recentUsers->count() . ' data' : 'Pribadi' }}</span>
                </div>

                <div class="d-grid gap-2">
                    @forelse($recentUsers as $recentUser)
                        <div class="dashboard-timeline-item py-2">
                            <div class="d-flex align-items-center justify-content-between gap-3">
                                <div class="d-flex align-items-center gap-3 min-w-0">
                                    <span class="avatar avatar-md" style="background: var(--db-soft); color: var(--tblr-primary);">
                                        {{ strtoupper(substr($recentUser->name, 0, 1)) }}
                                    </span>
                                    <div class="min-w-0">
                                        <div class="fw-semibold text-truncate">{{ $recentUser->name }}</div>
                                        <div class="text-muted small text-truncate">{{ $recentUser->email }}</div>
                                    </div>
                                </div>
                                <div class="text-muted small text-nowrap flex-shrink-0">
                                    @if($isPrivileged)
                                        {{ optional($recentUser->created_at)->diffForHumans() }}
                                    @else
                                        <span class="badge {{ $recentUser->email_verified_at ? 'bg-green-lt text-green' : 'bg-orange-lt text-orange' }}">
                                            {{ $recentUser->email_verified_at ? 'Terverifikasi' : 'Belum verifikasi' }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted">
                            <i class="ti ti-users" style="font-size: 2rem;"></i>
                            <div class="mt-2 small">{{ $isPrivileged ? 'Belum ada data pengguna.' : 'Data akun tidak tersedia.' }}</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Kanan: Daftar modul aktif --}}
        <div class="col-12 col-xl-5">
            <div class="dashboard-panel p-3 p-lg-4 h-100">
                <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                    <div>
                        <div class="text-secondary text-uppercase small fw-bold">{{ $isPrivileged ? 'Modul Berjalan' : 'Fitur Tersedia' }}</div>
                        <h3 class="mb-0">{{ $isPrivileged ? 'Yang aktif di workspace' : 'Fitur yang bisa Anda gunakan' }}</h3>
                    </div>
                    <span class="dashboard-chip">{{ $moduleHighlights->count() }}</span>
                </div>

                <div class="d-grid gap-2">
                    @forelse($moduleHighlights as $module)
                        <div class="dashboard-mini-stat">
                            <div class="d-flex align-items-center justify-content-between gap-3">
                                <div class="min-w-0">
                                    <div class="fw-semibold">{{ $module['name'] }}</div>
                                    <div class="text-muted small mt-1 text-truncate">{{ $module['description'] }}</div>
                                </div>
                                @if(!empty($module['route']) && Route::has($module['route']))
                                    <a href="{{ route($module['route']) }}"
                                       class="btn btn-sm btn-ghost-secondary flex-shrink-0"
                                       title="Buka {{ $module['name'] }}">
                                        <i class="ti ti-arrow-right"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted">
                            <i class="ti ti-apps" style="font-size: 2rem;"></i>
                            <div class="mt-2 small">
                                {{ $isPrivileged ? 'Belum ada modul aktif.' : 'Belum ada fitur aktif untuk akun ini.' }}
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>

    {{-- ── Seksi tambahan dari modul ── --}}
    @foreach($hooks->render('dashboard.sections', ['user' => auth()->user()]) as $hookedSection)
        {!! $hookedSection !!}
    @endforeach
</div>
@endsection
