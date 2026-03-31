@extends('layouts.admin')

@section('content')
@php
    $hooks = app(\App\Support\HookManager::class);
    $money = app(\App\Support\MoneyFormatter::class);

    $hour = (int) now()->format('H');
    if ($hour >= 5 && $hour < 11) {
        $timeSlot    = 'pagi';
        $greeting    = 'Selamat pagi';
        $timeLabel   = 'Pagi';
    } elseif ($hour >= 11 && $hour < 15) {
        $timeSlot    = 'siang';
        $greeting    = 'Selamat siang';
        $timeLabel   = 'Siang';
    } elseif ($hour >= 15 && $hour < 19) {
        $timeSlot    = 'sore';
        $greeting    = 'Selamat sore';
        $timeLabel   = 'Sore';
    } else {
        $timeSlot    = 'malam';
        $greeting    = 'Selamat malam';
        $timeLabel   = 'Malam';
    }
@endphp

<div class="dashboard-shell">
    <div class="row g-3">

        {{-- ── Hero ── --}}
        <div class="col-12">
            <div class="dashboard-hero dashboard-hero--{{ $timeSlot }} p-4 p-lg-5">
                <div class="row align-items-center g-4">
                    <div class="col-lg-7">
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <span class="dashboard-chip"><i class="ti ti-calendar"></i> {{ now()->translatedFormat('l, d F Y') }}</span>
                            <span class="dashboard-chip dashboard-chip--time">
                                <span class="dashboard-time-icon" aria-hidden="true">
                                    @if($timeSlot === 'pagi')
                                        {{-- Sunrise with cloud --}}
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M12 3v2M4.22 6.22l1.42 1.42M2 14h2M20 14h2M19.78 6.22l-1.42 1.42" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                            <path d="M5 14a7 7 0 0 1 14 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                            <path d="M3 17h18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                            <path d="M7 20h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                        </svg>
                                    @elseif($timeSlot === 'siang')
                                        {{-- Bright sun --}}
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.8"/>
                                            <path d="M12 2v2M12 20v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M2 12h2M20 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                        </svg>
                                    @elseif($timeSlot === 'sore')
                                        {{-- Sun setting --}}
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M17 12a5 5 0 1 1-10 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                            <path d="M12 2v2M4.22 4.22l1.42 1.42M2 12h2M20 12h2M19.78 4.22l-1.42 1.42" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                            <path d="M3 17h18M7 20h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                        </svg>
                                    @else
                                        {{-- Moon and stars --}}
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M19 3l.5 1.5L21 5l-1.5.5L19 7l-.5-1.5L17 5l1.5-.5L19 3zM3 8l.4 1.2 1.2.4-1.2.4L3 11.2l-.4-1.2L1.4 9.6l1.2-.4L3 8z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    @endif
                                </span>
                                {{ $timeLabel }}
                            </span>
                        </div>
                        <h1 class="dashboard-hero-title mb-2">
                            {{ $greeting }}, {{ auth()->user()->name }}.
                        </h1>
                        <p class="mb-0 dashboard-hero-sub">
                            @if($isPrivileged)
                                Pantau kondisi workspace, modul yang berjalan, dan aktivitas tim Anda dari sini.
                            @else
                                Akses fitur yang tersedia untuk akun Anda dan mulai bekerja dari sini.
                            @endif
                        </p>
                    </div>
                    <div class="col-lg-5 d-flex justify-content-lg-end">
                        <div class="dashboard-hero-illustration" aria-hidden="true">
                            @if($timeSlot === 'pagi')
                                <svg viewBox="0 0 220 160" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <ellipse cx="110" cy="145" rx="90" ry="12" fill="currentColor" opacity=".06"/>
                                    <!-- Sun rising -->
                                    <circle cx="110" cy="90" r="38" fill="url(#sun-pagi)" opacity=".9"/>
                                    <!-- Sun rays -->
                                    <g stroke="#FBBF24" stroke-width="3" stroke-linecap="round" opacity=".7">
                                        <line x1="110" y1="38" x2="110" y2="28"/>
                                        <line x1="143" y1="51" x2="150" y2="44"/>
                                        <line x1="156" y1="84" x2="166" y2="84"/>
                                        <line x1="77" y1="51" x2="70" y2="44"/>
                                        <line x1="64" y1="84" x2="54" y2="84"/>
                                    </g>
                                    <!-- Cloud left -->
                                    <rect x="28" y="85" width="54" height="22" rx="11" fill="white" opacity=".85"/>
                                    <circle cx="45" cy="85" r="15" fill="white" opacity=".85"/>
                                    <circle cx="64" cy="82" r="18" fill="white" opacity=".85"/>
                                    <!-- Cloud right small -->
                                    <rect x="150" y="60" width="44" height="18" rx="9" fill="white" opacity=".7"/>
                                    <circle cx="163" cy="60" r="12" fill="white" opacity=".7"/>
                                    <circle cx="178" cy="58" r="14" fill="white" opacity=".7"/>
                                    <!-- Ground line -->
                                    <line x1="20" y1="128" x2="200" y2="128" stroke="#F59E0B" stroke-width="2" stroke-linecap="round" opacity=".3"/>
                                    <defs>
                                        <radialGradient id="sun-pagi" cx="50%" cy="50%" r="50%">
                                            <stop offset="0%" stop-color="#FEF08A"/>
                                            <stop offset="100%" stop-color="#F59E0B"/>
                                        </radialGradient>
                                    </defs>
                                </svg>
                            @elseif($timeSlot === 'siang')
                                <svg viewBox="0 0 220 160" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <ellipse cx="110" cy="148" rx="88" ry="10" fill="currentColor" opacity=".06"/>
                                    <!-- Bright sun -->
                                    <circle cx="110" cy="78" r="44" fill="url(#sun-siang)" opacity=".95"/>
                                    <!-- Glow -->
                                    <circle cx="110" cy="78" r="58" fill="#FEF08A" opacity=".15"/>
                                    <!-- Rays all around -->
                                    <g stroke="#F59E0B" stroke-width="3.5" stroke-linecap="round">
                                        <line x1="110" y1="20" x2="110" y2="8"/>
                                        <line x1="148" y1="32" x2="156" y2="24"/>
                                        <line x1="166" y1="70" x2="178" y2="70"/>
                                        <line x1="148" y1="108" x2="156" y2="116"/>
                                        <line x1="110" y1="126" x2="110" y2="138"/>
                                        <line x1="72" y1="108" x2="64" y2="116"/>
                                        <line x1="54" y1="70" x2="42" y2="70"/>
                                        <line x1="72" y1="32" x2="64" y2="24"/>
                                    </g>
                                    <defs>
                                        <radialGradient id="sun-siang" cx="45%" cy="40%" r="60%">
                                            <stop offset="0%" stop-color="#FFF7C0"/>
                                            <stop offset="100%" stop-color="#FBBF24"/>
                                        </radialGradient>
                                    </defs>
                                </svg>
                            @elseif($timeSlot === 'sore')
                                <svg viewBox="0 0 220 160" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <ellipse cx="110" cy="148" rx="88" ry="10" fill="currentColor" opacity=".07"/>
                                    <!-- Horizon -->
                                    <rect x="0" y="118" width="220" height="30" rx="6" fill="url(#horizon-grad)" opacity=".35"/>
                                    <!-- Half sun setting -->
                                    <clipPath id="clip-half">
                                        <rect x="0" y="0" width="220" height="118"/>
                                    </clipPath>
                                    <circle cx="110" cy="120" r="52" fill="url(#sun-sore)" opacity=".9" clip-path="url(#clip-half)"/>
                                    <!-- Glow on horizon -->
                                    <ellipse cx="110" cy="118" rx="70" ry="18" fill="#FB923C" opacity=".25"/>
                                    <!-- Rays (upper only) -->
                                    <g stroke="#F97316" stroke-width="3" stroke-linecap="round" opacity=".6">
                                        <line x1="110" y1="52" x2="110" y2="42"/>
                                        <line x1="142" y1="64" x2="150" y2="56"/>
                                        <line x1="78" y1="64" x2="70" y2="56"/>
                                        <line x1="155" y1="95" x2="165" y2="92"/>
                                        <line x1="65" y1="95" x2="55" y2="92"/>
                                    </g>
                                    <!-- Cloud silhouette -->
                                    <rect x="22" y="76" width="58" height="20" rx="10" fill="#7C3AED" opacity=".18"/>
                                    <circle cx="40" cy="76" r="14" fill="#7C3AED" opacity=".18"/>
                                    <circle cx="58" cy="73" r="17" fill="#7C3AED" opacity=".18"/>
                                    <defs>
                                        <radialGradient id="sun-sore" cx="50%" cy="20%" r="70%">
                                            <stop offset="0%" stop-color="#FDBA74"/>
                                            <stop offset="100%" stop-color="#EA580C"/>
                                        </radialGradient>
                                        <linearGradient id="horizon-grad" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="0%" stop-color="#FB923C"/>
                                            <stop offset="100%" stop-color="#9333EA"/>
                                        </linearGradient>
                                    </defs>
                                </svg>
                            @else
                                <svg viewBox="0 0 220 160" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <ellipse cx="110" cy="148" rx="88" ry="10" fill="currentColor" opacity=".08"/>
                                    <!-- Stars -->
                                    <g fill="#E0E7FF" opacity=".8">
                                        <circle cx="30" cy="25" r="1.5"/>
                                        <circle cx="58" cy="15" r="2"/>
                                        <circle cx="90" cy="30" r="1.5"/>
                                        <circle cx="145" cy="12" r="2.5"/>
                                        <circle cx="170" cy="28" r="1.5"/>
                                        <circle cx="195" cy="18" r="2"/>
                                        <circle cx="48" cy="50" r="1.2"/>
                                        <circle cx="130" cy="40" r="1.8"/>
                                        <circle cx="185" cy="55" r="1.2"/>
                                        <circle cx="20" cy="70" r="1.5"/>
                                    </g>
                                    <!-- Star sparkles -->
                                    <g stroke="#A5B4FC" stroke-width="1.5" stroke-linecap="round" opacity=".7">
                                        <line x1="145" y1="8" x2="145" y2="16"/>
                                        <line x1="141" y1="12" x2="149" y2="12"/>
                                        <line x1="58" y1="11" x2="58" y2="19"/>
                                        <line x1="54" y1="15" x2="62" y2="15"/>
                                    </g>
                                    <!-- Moon crescent -->
                                    <circle cx="110" cy="80" r="42" fill="url(#moon-full)" opacity=".9"/>
                                    <circle cx="126" cy="66" r="34" fill="var(--db-moon-cutout, #1e3a5f)" />
                                    <!-- Moon glow -->
                                    <circle cx="110" cy="80" r="54" fill="#6366F1" opacity=".08"/>
                                    <!-- Small cloud silhouette dark -->
                                    <rect x="148" y="108" width="52" height="18" rx="9" fill="#312E81" opacity=".35"/>
                                    <circle cx="164" cy="108" r="12" fill="#312E81" opacity=".35"/>
                                    <circle cx="180" cy="106" r="14" fill="#312E81" opacity=".35"/>
                                    <defs>
                                        <radialGradient id="moon-full" cx="40%" cy="35%" r="65%">
                                            <stop offset="0%" stop-color="#E0E7FF"/>
                                            <stop offset="100%" stop-color="#A5B4FC"/>
                                        </radialGradient>
                                    </defs>
                                </svg>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

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
                    <div class="text-secondary text-uppercase small fw-bold">AI Credits</div>
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
