@extends('layouts.admin')

@section('content')
@php($hooks = app(\App\Support\HookManager::class))

<div class="dashboard-shell">
    <div class="row g-3">
        <div class="col-12">
            <div class="dashboard-hero p-4 p-lg-5">
                <div class="row align-items-center g-4">
                    <div class="col-lg-8">
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <span class="dashboard-chip"><i class="ti ti-layout-dashboard"></i> Control Center</span>
                            <span class="dashboard-chip"><i class="ti ti-calendar"></i> {{ now()->translatedFormat('l, d F Y') }}</span>
                        </div>
                        <div class="text-secondary text-uppercase small fw-bold mb-2">Ringkasan Operasional</div>
                        <h1 class="mb-2" style="font-size: clamp(1.9rem, 3vw, 2.7rem); color: var(--db-ink);">
                            Selamat datang, {{ auth()->user()->name }}.
                        </h1>
                        <p class="mb-0" style="max-width: 46rem; color: var(--db-muted); font-size: 1rem;">
                            @if($isPrivileged)
                                Pantau kondisi workspace, modul yang aktif, dan status tim Anda dari sini. Modul tambahan akan menampilkan data masing-masing di bawah.
                            @else
                                Akses fitur yang tersedia untuk akun Anda, pantau status workspace, dan mulai bekerja dari sini.
                            @endif
                        </p>
                    </div>
                    <div class="col-lg-4">
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="dashboard-mini-stat">
                                    <div class="text-secondary small">Role Anda</div>
                                    <div class="fw-bold mt-1">{{ auth()->user()->getRoleNames()->join(', ') ?: 'Belum ada role' }}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="dashboard-mini-stat">
                                    <div class="text-secondary small">Modul Aktif</div>
                                    <div class="fw-bold mt-1">{{ $activeModules->count() }}/{{ $totalModules }}</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="dashboard-mini-stat">
                                    <div class="d-flex justify-content-between align-items-center gap-3">
                                        <div>
                                            <div class="text-secondary small">Status Sesi</div>
                                            <div class="fw-semibold mt-1">Terautentikasi & terverifikasi</div>
                                        </div>
                                        <span class="badge bg-green-lt text-green">Online</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="dashboard-mini-stat">
                                    <div class="d-flex justify-content-between align-items-center gap-3">
                                        <div>
                                            <div class="text-secondary small">Status Akun</div>
                                            <div class="fw-semibold mt-1">{{ auth()->user()->email }}</div>
                                        </div>
                                        <span class="badge {{ ($aiCredits['enabled'] ?? false) ? 'bg-azure-lt text-azure' : 'bg-secondary-lt text-secondary' }}">
                                            {{ ($aiCredits['enabled'] ?? false) ? 'AI Aktif' : 'AI Nonaktif' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @foreach($stats as $stat)
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="dashboard-kpi p-3 p-lg-4 h-100">
                    <div class="d-flex align-items-start justify-content-between gap-3">
                        <div>
                            <div class="text-secondary text-uppercase small fw-bold">{{ $stat['label'] }}</div>
                            <div class="mt-2 fw-bold" style="font-size: 2rem; line-height: 1; color: var(--db-ink);">{{ $stat['value'] }}</div>
                            <div class="text-muted small mt-2">{{ $stat['meta'] }}</div>
                        </div>
                        <span class="badge bg-{{ $stat['tone'] }}-lt text-{{ $stat['tone'] }}">{{ ucfirst($stat['tone']) }}</span>
                    </div>
                </div>
            </div>
        @endforeach

        @foreach($hooks->render('dashboard.overview.cards', ['user' => auth()->user()]) as $hookedCard)
            {!! $hookedCard !!}
        @endforeach
    </div>

    <div class="row g-3 mt-1">
        <div class="col-12 col-xl-7">
            <div class="dashboard-panel p-3 p-lg-4 h-100">
                <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                    <div>
                        <div class="text-secondary text-uppercase small fw-bold">{{ $isPrivileged ? 'User Terbaru' : 'Akun Anda' }}</div>
                        <h3 class="mb-0">{{ $isPrivileged ? 'Aktivitas terkini' : 'Ringkasan workspace pribadi' }}</h3>
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
                                <div class="text-muted small text-nowrap">
                                    {{ $isPrivileged ? optional($recentUser->created_at)->diffForHumans() : (auth()->user()->email_verified_at ? 'Terverifikasi' : 'Belum verifikasi') }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <i class="ti ti-users text-muted" style="font-size: 2rem;"></i>
                            <div class="text-muted mt-2">{{ $isPrivileged ? 'Belum ada data user.' : 'Data akun belum tersedia.' }}</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="dashboard-panel p-3 p-lg-4 h-100">
                <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                    <div>
                        <div class="text-secondary text-uppercase small fw-bold">{{ $isPrivileged ? 'Modul Aktif' : 'Modul Tersedia' }}</div>
                        <h3 class="mb-0">{{ $isPrivileged ? 'Fitur yang sedang berjalan' : 'Fitur yang bisa Anda gunakan' }}</h3>
                    </div>
                    <span class="dashboard-chip">{{ $moduleHighlights->count() }} modul</span>
                </div>

                <div class="d-grid gap-2">
                    @forelse($moduleHighlights as $module)
                        <div class="dashboard-mini-stat">
                            <div class="d-flex align-items-center justify-content-between gap-3">
                                <div>
                                    <div class="fw-semibold">{{ $module['name'] }}</div>
                                    <div class="text-muted small mt-1">{{ $module['description'] }}</div>
                                </div>
                                <div class="text-end flex-shrink-0">
                                    <div class="fw-bold">{{ $module['items'] }}</div>
                                    <div class="text-muted small">menu</div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <i class="ti ti-apps text-muted" style="font-size: 2rem;"></i>
                            <div class="text-muted mt-2">
                                {{ $isPrivileged
                                    ? 'Belum ada modul aktif. Aktifkan modul lewat menu Modules.'
                                    : 'Belum ada modul aktif untuk akun ini.' }}
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="dashboard-panel p-3 p-lg-4">
                <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                    <div>
                        <div class="text-secondary text-uppercase small fw-bold">AI Credits</div>
                        <h3 class="mb-0">Pemakaian AI bulan ini</h3>
                    </div>
                    <span class="dashboard-chip">
                        @if(($aiCredits['available'] ?? null) === null)
                            Unlimited
                        @else
                            {{ number_format($aiCredits['used'] ?? 0) }}/{{ number_format($aiCredits['available'] ?? 0) }}
                        @endif
                    </span>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="dashboard-mini-stat">
                            <div class="text-secondary small">Dipakai Bulan Ini</div>
                            <div class="fw-bold mt-1">{{ number_format($aiCredits['used'] ?? 0) }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="dashboard-mini-stat">
                            <div class="text-secondary small">Limit Paket</div>
                            <div class="fw-bold mt-1">{{ ($aiCredits['included'] ?? null) === null ? 'Unlimited' : number_format($aiCredits['included']) }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="dashboard-mini-stat">
                            <div class="text-secondary small">Top Up / Tersisa</div>
                            <div class="fw-bold mt-1">
                                +{{ number_format($aiCredits['top_up'] ?? 0) }}
                                @if(($aiCredits['remaining'] ?? null) !== null)
                                    · {{ number_format($aiCredits['remaining']) }}
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @foreach($hooks->render('dashboard.sections', ['user' => auth()->user()]) as $hookedSection)
        {!! $hookedSection !!}
    @endforeach
</div>
@endsection
