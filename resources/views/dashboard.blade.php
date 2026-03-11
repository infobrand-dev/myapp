@extends('layouts.admin')

@section('content')
@php($hooks = app(\App\Support\HookManager::class))
<style>
    .dashboard-shell {
        --db-ink: #17324a;
        --db-muted: #667789;
        --db-line: rgba(52, 86, 123, 0.12);
        --db-soft: rgba(15, 84, 168, 0.08);
        --db-soft-strong: rgba(15, 84, 168, 0.14);
    }
    .dashboard-hero {
        position: relative;
        overflow: hidden;
        border: 1px solid var(--db-line);
        border-radius: 1.35rem;
        background:
            radial-gradient(circle at top left, rgba(32, 107, 196, 0.18), transparent 32rem),
            linear-gradient(135deg, #ffffff 0%, #f4f8fc 45%, #eef5fb 100%);
    }
    .dashboard-hero::after {
        content: "";
        position: absolute;
        inset: auto -4rem -5rem auto;
        width: 16rem;
        height: 16rem;
        border-radius: 999px;
        background: radial-gradient(circle, rgba(47, 179, 68, 0.14), transparent 65%);
        pointer-events: none;
    }
    .dashboard-kpi {
        border: 1px solid var(--db-line);
        border-radius: 1.1rem;
        background: #fff;
        box-shadow: 0 0.75rem 1.75rem rgba(23, 50, 74, 0.05);
    }
    .dashboard-panel {
        border: 1px solid var(--db-line);
        border-radius: 1.1rem;
        background: #fff;
        box-shadow: 0 0.75rem 1.75rem rgba(23, 50, 74, 0.05);
    }
    .dashboard-chip {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .35rem .7rem;
        border-radius: 999px;
        background: var(--db-soft);
        color: #21507d;
        font-size: .78rem;
        font-weight: 600;
    }
    .dashboard-timeline-item + .dashboard-timeline-item {
        border-top: 1px solid var(--db-line);
    }
    .dashboard-mini-stat {
        padding: .85rem 1rem;
        border-radius: .95rem;
        background: linear-gradient(180deg, rgba(255,255,255,0.96), rgba(244,248,252,0.95));
        border: 1px solid rgba(52, 86, 123, 0.08);
    }
    .dashboard-dot {
        width: .65rem;
        height: .65rem;
        border-radius: 999px;
        display: inline-block;
        background: currentColor;
    }
</style>

<div class="dashboard-shell">
    <div class="row g-3">
        <div class="col-12">
            <div class="dashboard-hero p-4 p-lg-5">
                <div class="row align-items-center g-4">
                    <div class="col-lg-8">
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <span class="dashboard-chip">Control Center</span>
                            <span class="dashboard-chip">{{ now()->translatedFormat('l, d F Y') }}</span>
                        </div>
                        <div class="text-secondary text-uppercase small fw-bold mb-2">Operational Snapshot</div>
                        <h1 class="mb-2" style="font-size: clamp(1.9rem, 3vw, 2.7rem); color: var(--db-ink);">
                            {{ auth()->user()->name }}, sistem siap dipakai hari ini.
                        </h1>
                        <p class="mb-0" style="max-width: 46rem; color: var(--db-muted); font-size: 1rem;">
                            @if($isPrivileged)
                                Ringkasan ini menampilkan kondisi inti aplikasi, modul yang sedang aktif, dan widget tambahan dari module yang memang terpasang. Core dashboard tetap bersih; data module masuk lewat inject widget.
                            @else
                                Ringkasan ini fokus ke workspace Anda sendiri: akses fitur yang tersedia, status akun, dan widget tambahan dari module aktif yang relevan untuk user saat ini.
                            @endif
                        </p>
                    </div>
                    <div class="col-lg-4">
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="dashboard-mini-stat">
                                    <div class="text-secondary small">Your Roles</div>
                                    <div class="fw-bold mt-1">{{ auth()->user()->getRoleNames()->join(', ') ?: 'None' }}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="dashboard-mini-stat">
                                    <div class="text-secondary small">Module Coverage</div>
                                    <div class="fw-bold mt-1">{{ $activeModules->count() }}/{{ $totalModules }}</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="dashboard-mini-stat">
                                    <div class="d-flex justify-content-between align-items-center gap-3">
                                        <div>
                                            <div class="text-secondary small">Session Status</div>
                                            <div class="fw-semibold mt-1">Authenticated and verified</div>
                                        </div>
                                        <span class="badge bg-green-lt text-green">Online</span>
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
                        <div class="text-secondary text-uppercase small fw-bold">{{ $isPrivileged ? 'Recent Users' : 'Your Account' }}</div>
                        <h3 class="mb-0">{{ $isPrivileged ? 'Latest activity in core accounts' : 'Personal workspace summary' }}</h3>
                    </div>
                    <span class="dashboard-chip">{{ $isPrivileged ? $recentUsers->count() . ' latest records' : 'Private overview' }}</span>
                </div>

                <div class="d-grid gap-2">
                    @forelse($recentUsers as $recentUser)
                        <div class="dashboard-timeline-item py-2">
                            <div class="d-flex align-items-center justify-content-between gap-3">
                                <div class="d-flex align-items-center gap-3 min-w-0">
                                    <span class="avatar avatar-md" style="background: rgba(32, 107, 196, 0.12); color: #1f5b95;">
                                        {{ strtoupper(substr($recentUser->name, 0, 1)) }}
                                    </span>
                                    <div class="min-w-0">
                                        <div class="fw-semibold text-truncate">{{ $recentUser->name }}</div>
                                        <div class="text-muted small text-truncate">{{ $recentUser->email }}</div>
                                    </div>
                                </div>
                                <div class="text-muted small text-nowrap">
                                    {{ $isPrivileged ? optional($recentUser->created_at)->diffForHumans() : (auth()->user()->email_verified_at ? 'Verified' : 'Verification pending') }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-muted">{{ $isPrivileged ? 'Belum ada data user.' : 'Data akun belum tersedia.' }}</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="dashboard-panel p-3 p-lg-4 h-100">
                <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                    <div>
                        <div class="text-secondary text-uppercase small fw-bold">{{ $isPrivileged ? 'Active Modules' : 'Available Modules' }}</div>
                        <h3 class="mb-0">{{ $isPrivileged ? 'Live product surface' : 'Features available in your workspace' }}</h3>
                    </div>
                    <span class="dashboard-chip">{{ $moduleHighlights->count() }} visible</span>
                </div>

                <div class="d-grid gap-2">
                    @forelse($moduleHighlights as $module)
                        <div class="dashboard-mini-stat">
                            <div class="d-flex align-items-center justify-content-between gap-3">
                                <div>
                                    <div class="fw-semibold">{{ $module['name'] }}</div>
                                    <div class="text-muted small mt-1">{{ $module['description'] }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold">{{ $module['items'] }}</div>
                                    <div class="text-muted small">menu item</div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-muted">
                            {{ $isPrivileged
                                ? 'Belum ada module aktif. Aktifkan module lewat menu Modules untuk menampilkan widget tambahan.'
                                : 'Belum ada module aktif yang tersedia untuk akun ini.' }}
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @foreach($hooks->render('dashboard.sections', ['user' => auth()->user()]) as $hookedSection)
        {!! $hookedSection !!}
    @endforeach
</div>
@endsection
