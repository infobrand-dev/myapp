@extends('layouts.landing')

@section('head_title', config('app.name') . ' Mulai Digital - Pendampingan UMKM dari Manual ke Sistem')
@section('head_description', 'Bantu bisnis Anda pindah dari catatan manual, Excel, dan chat yang tercecer ke workflow digital yang rapi. Ada pendampingan audit proses, migrasi data, onboarding tim, dan baca dashboard awal bersama.')

@php
    $whatsAppNumber = '6281222229815';
    $waLink = function (string $message) use ($whatsAppNumber): string {
        return 'https://wa.me/' . $whatsAppNumber . '?text=' . urlencode($message);
    };

    $problems = [
        [
            'icon' => 'ti-notebook',
            'title' => 'Catatan masih tersebar',
            'text' => 'Sebagian ada di buku, sebagian di Excel, sebagian lagi hanya ada di chat admin. Saat butuh cek cepat, tim harus bongkar banyak tempat.',
        ],
        [
            'icon' => 'ti-user-exclamation',
            'title' => 'Operasional terlalu bergantung ke orang tertentu',
            'text' => 'Kalau admin yang biasa pegang data sedang libur atau resign, tim lain kesulitan melanjutkan karena alurnya belum tertata.',
        ],
        [
            'icon' => 'ti-chart-dots-3',
            'title' => 'Owner susah membaca kondisi harian',
            'text' => 'Penjualan, pembayaran, stok, dan follow up customer berjalan, tapi ringkasannya terlambat atau baru terlihat saat masalah sudah besar.',
        ],
    ];

    $steps = [
        ['no' => '01', 'title' => 'Audit proses yang berjalan sekarang', 'text' => 'Kami pahami dulu cara bisnis Anda bekerja hari ini: siapa input data, dari mana data datang, dan bottleneck apa yang paling sering terjadi.'],
        ['no' => '02', 'title' => 'Mapping data lama yang masih dipakai', 'text' => 'File Excel, daftar customer, produk, supplier, stok awal, sampai transaksi inti dipetakan supaya migrasi tidak asal pindah.'],
        ['no' => '03', 'title' => 'Migrasi dan rapikan data yang penting', 'text' => 'Data yang layak dibawa dibersihkan, disusun, lalu dimasukkan ke workspace digital agar tim tidak mulai dari nol.'],
        ['no' => '04', 'title' => 'Setup alur kerja awal', 'text' => 'Kami bantu menyiapkan struktur kerja yang ringan lebih dulu: master data, transaksi inti, pembayaran, dan ritme input harian.'],
        ['no' => '05', 'title' => 'Onboarding tim yang akan memakai', 'text' => 'Tim diajak pakai sistem untuk aktivitas yang benar-benar dipakai tiap hari, bukan dibebani fitur yang belum relevan.'],
        ['no' => '06', 'title' => 'Review dashboard awal bersama', 'text' => 'Setelah mulai jalan, kami bantu baca dashboard operasional supaya owner dan admin tahu angka mana yang harus dipantau lebih dulu.'],
    ];

    $migrationItems = [
        'Data customer dan kontak lama',
        'Produk atau jasa yang selama ini dijual',
        'Daftar supplier dan relasi penting',
        'Stok awal atau posisi barang saat mulai',
        'Catatan hutang dan piutang yang masih aktif',
        'Transaksi lama inti yang masih perlu dibaca tim',
    ];

    $adoptionPoints = [
        'Mulai dari transaksi dan pembayaran yang paling sering terjadi',
        'Gunakan satu sumber data customer dan produk yang sama',
        'Biasakan update status operasional di hari yang sama',
        'Kurangi ketergantungan pada chat pribadi dan file lokal',
    ];

    $dashboardCards = [
        [
            'title' => 'Dashboard harian',
            'text' => 'Lihat transaksi masuk, pembayaran yang belum selesai, dan aktivitas operasional yang perlu perhatian hari ini.',
            'icon' => 'ti-calendar-stats',
        ],
        [
            'title' => 'Dashboard mingguan',
            'text' => 'Baca pola penjualan, ritme pembayaran, pergerakan stok, dan titik yang mulai menghambat tim.',
            'icon' => 'ti-chart-histogram',
        ],
        [
            'title' => 'Dashboard untuk keputusan owner',
            'text' => 'Owner tidak perlu menunggu rekap manual. Angka penting bisa dibaca lebih cepat untuk ambil keputusan operasional.',
            'icon' => 'ti-layout-dashboard',
        ],
    ];

    $faqs = [
        [
            'q' => 'Apakah data lama bisa dibantu pindahkan?',
            'a' => 'Bisa. Kami bantu petakan dulu data mana yang masih layak dipakai, mana yang perlu dirapikan, lalu dipindahkan ke struktur yang lebih rapi.',
        ],
        [
            'q' => 'Kalau data kami masih berantakan bagaimana?',
            'a' => 'Itu justru kasus yang paling sering terjadi. Fokus awalnya bukan memindahkan semuanya, tetapi memilih data penting yang perlu dipakai agar transisi tetap realistis.',
        ],
        [
            'q' => 'Kalau tim belum terbiasa sistem bagaimana?',
            'a' => 'Implementasi diarahkan ke penggunaan yang sederhana dulu. Tim tidak dipaksa belajar semua hal sekaligus, tetapi dibimbing ke alur harian yang paling penting.',
        ],
        [
            'q' => 'Butuh waktu berapa lama sampai mulai jalan?',
            'a' => 'Tergantung kerapian data dan kompleksitas operasional, tetapi tujuan pendeknya adalah membuat tim mulai memakai sistem lebih cepat, bukan menunggu semuanya sempurna.',
        ],
        [
            'q' => 'Apakah setelah setup masih dibantu baca dashboard awal?',
            'a' => 'Ya. Pendampingan mencakup review dashboard awal agar owner dan admin paham angka mana yang harus dilihat dan bagaimana menindaklanjutinya.',
        ],
    ];

    $heroWa = $waLink('Halo, saya ingin konsultasi mulai digital untuk bisnis saya. Saya masih pakai proses manual dan ingin dibantu audit proses, migrasi data, serta setup awal.');
    $processWa = $waLink('Halo, saya ingin diskusi audit proses bisnis yang sekarang masih manual. Bisa dibantu lihat alur kerja kami?');
    $migrationWa = $waLink('Halo, saya ingin konsultasi migrasi data lama ke sistem digital. Data kami masih tersebar di catatan, Excel, dan chat.');
    $dashboardWa = $waLink('Halo, saya ingin minta contoh dashboard dan alur pendampingan setelah tim mulai memakai sistem.');
    $finalWa = $waLink('Halo, saya ingin jadwalkan diskusi WhatsApp untuk memulai digitalisasi bisnis kami.');
@endphp

@section('topbar')
<header class="landing-topbar sticky-top">
    <div class="container py-3">
        <div class="d-flex align-items-center justify-content-between gap-3">
            <a href="{{ route('landing') }}" class="text-decoration-none d-inline-flex align-items-center gap-2">
                <x-app-logo variant="default" :height="36" />
            </a>
            <nav class="d-none d-lg-flex align-items-center gap-1 landing-nav-shell">
                <a href="#masalah" class="landing-nav-link d-inline-flex align-items-center gap-2"><i class="ti ti-alert-circle"></i><span>Masalah</span></a>
                <a href="#proses" class="landing-nav-link d-inline-flex align-items-center gap-2"><i class="ti ti-route"></i><span>Proses</span></a>
                <a href="#migrasi" class="landing-nav-link d-inline-flex align-items-center gap-2"><i class="ti ti-database-import"></i><span>Migrasi</span></a>
                <a href="#dashboard" class="landing-nav-link d-inline-flex align-items-center gap-2"><i class="ti ti-chart-bar"></i><span>Dashboard</span></a>
                <a href="#faq" class="landing-nav-link d-inline-flex align-items-center gap-2"><i class="ti ti-help-circle"></i><span>FAQ</span></a>
            </nav>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ $heroWa }}" target="_blank" rel="noopener" class="btn btn-outline-dark btn-sm d-none d-md-inline-flex">Konsultasi via WhatsApp</a>
                <a href="{{ $finalWa }}" target="_blank" rel="noopener" class="btn btn-dark btn-sm">Mulai Diskusi Sekarang</a>
            </div>
        </div>
    </div>
</header>
@endsection

@section('content')
<section class="landing-hero py-5 py-lg-6" style="background:linear-gradient(180deg,#fff8ef 0%,#fff 55%,#f8fafc 100%); border-bottom:1px solid var(--landing-line);">
    <div class="container py-lg-4">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <div class="landing-badge mb-4">
                    <i class="ti ti-building-store"></i> Untuk UMKM yang masih berjalan manual
                </div>
                <h1 class="landing-headline mb-4">
                    <span>Pindah ke sistem digital</span> tanpa harus bingung mulai dari mana.
                </h1>
                <p class="landing-subtext mb-4">
                    Kalau bisnis Anda masih mengandalkan catatan manual, Excel, dan chat yang campur aduk, kami bantu transisinya sampai benar-benar jalan: audit proses, rapikan data, migrasi yang penting, onboarding tim, lalu membaca dashboard awal bersama.
                </p>
                <div class="d-flex flex-wrap gap-3 mb-4">
                    <a href="{{ $heroWa }}" target="_blank" rel="noopener" class="btn btn-lg btn-dark">Konsultasi via WhatsApp</a>
                    <a href="#proses" class="btn btn-lg btn-outline-dark">Lihat Alur Pendampingan</a>
                </div>
                <div class="small text-muted mb-4">Bukan sekadar beli software lalu ditinggal. Fokusnya membuat bisnis Anda benar-benar mulai digital dan tim bisa menjalankannya.</div>
                <div class="d-flex flex-wrap gap-2">
                    <span class="landing-pill">Audit proses</span>
                    <span class="landing-pill">Migrasi data</span>
                    <span class="landing-pill">Onboarding tim</span>
                    <span class="landing-pill">Dashboard awal</span>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="landing-panel p-4 p-lg-5" style="border:1px solid rgba(15,23,42,.08); box-shadow:0 28px 60px rgba(15,23,42,.08);">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="small text-uppercase fw-bold text-muted mb-2">Yang biasanya terjadi sebelum mulai digital</div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded-4 h-100" style="background:#fff7ed; border:1px solid rgba(249,115,22,.12);">
                                <div class="fw-semibold mb-1">Data tercecer</div>
                                <div class="small text-muted">Customer, transaksi, stok, dan pembayaran ada di tempat yang berbeda.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded-4 h-100" style="background:#fff7ed; border:1px solid rgba(249,115,22,.12);">
                                <div class="fw-semibold mb-1">Laporan lambat</div>
                                <div class="small text-muted">Owner baru tahu kondisi bisnis setelah direkap manual di belakang.</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="p-4 rounded-4" style="background:#f8fafc; border:1px solid rgba(15,23,42,.08);">
                                <div class="text-uppercase fw-bold small text-muted mb-2">Setelah mulai digital</div>
                                <div class="h4 mb-2">Tim bekerja dari data yang sama</div>
                                <div class="small text-muted mb-0">Input harian lebih tertib, transisi lebih tenang, dan owner bisa membaca dashboard operasional tanpa menunggu semuanya dikirim manual.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="masalah" class="py-5 py-lg-6">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Masalah Utama</div>
            <h2 class="landing-section-title">Banyak bisnis sebenarnya siap tumbuh, tapi datanya belum siap menopang operasional.</h2>
            <p class="landing-subtext mx-auto">Masalahnya sering bukan kurang kerja keras, tetapi proses yang masih manual membuat tim terus bekerja di mode reaktif.</p>
        </div>
        <div class="row g-4">
            @foreach($problems as $problem)
                <div class="col-lg-4">
                    <div class="landing-panel p-4 h-100">
                        <div class="mb-3"><i class="ti {{ $problem['icon'] }}" style="font-size:1.65rem; color:#ea580c;"></i></div>
                        <h3 class="h5 mb-2">{{ $problem['title'] }}</h3>
                        <p class="small text-muted mb-0">{{ $problem['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="text-center mt-4">
            <a href="{{ $processWa }}" target="_blank" rel="noopener" class="btn btn-outline-dark">Tanya Soal Audit Proses</a>
        </div>
    </div>
</section>

<section id="proses" class="py-5 py-lg-6" style="background:#f8fafc; border-top:1px solid var(--landing-line); border-bottom:1px solid var(--landing-line);">
    <div class="container">
        <div class="row g-5 align-items-start">
            <div class="col-lg-4">
                <div class="landing-eyebrow mb-2">Alur Pendampingan</div>
                <h2 class="landing-section-title mb-3">Kami bantu pindah tahap demi tahap, bukan langsung melempar sistem ke tim Anda.</h2>
                <p class="landing-subtext mb-4">Tujuannya bukan sekadar instalasi, tetapi memastikan bisnis Anda punya jalur yang realistis untuk mulai digital dan terus dipakai.</p>
                <a href="{{ $processWa }}" target="_blank" rel="noopener" class="btn btn-dark">Diskusi Audit Proses</a>
            </div>
            <div class="col-lg-8">
                <div class="row g-3">
                    @foreach($steps as $step)
                        <div class="col-md-6">
                            <div class="landing-panel p-4 h-100" style="border-left:4px solid #f59e0b;">
                                <div class="small text-uppercase fw-bold text-muted mb-2">{{ $step['no'] }}</div>
                                <h3 class="h5 mb-2">{{ $step['title'] }}</h3>
                                <p class="small text-muted mb-0">{{ $step['text'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

<section id="migrasi" class="py-5 py-lg-6">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-5">
                <div class="landing-eyebrow mb-2">Migrasi Data</div>
                <h2 class="landing-section-title mb-3">Data lama tidak harus dibuang, tapi harus dipilih dan dirapikan.</h2>
                <p class="landing-subtext mb-4">Banyak bisnis menunda digitalisasi karena merasa data lama terlalu berantakan. Di tahap ini, fokusnya bukan membawa semua file apa adanya, tetapi memilih data yang penting untuk membuat operasional baru bisa jalan lebih cepat.</p>
                <a href="{{ $migrationWa }}" target="_blank" rel="noopener" class="btn btn-outline-dark">Tanya Soal Migrasi Data</a>
            </div>
            <div class="col-lg-7">
                <div class="row g-3">
                    @foreach($migrationItems as $item)
                        <div class="col-md-6">
                            <div class="landing-panel p-3 h-100">
                                <div class="d-flex align-items-start gap-3">
                                    <i class="ti ti-check text-success" style="font-size:1.2rem;"></i>
                                    <div class="small text-muted">{{ $item }}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 py-lg-6" style="background:#fdfaf5; border-top:1px solid var(--landing-line); border-bottom:1px solid var(--landing-line);">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <div class="landing-eyebrow mb-2">Mulai Menggunakan</div>
                <h2 class="landing-section-title mb-3">Tim tidak perlu belajar semuanya sekaligus.</h2>
                <p class="landing-subtext mb-4">Implementasi awal diarahkan ke ritme harian yang paling penting dulu. Begitu tim nyaman dengan alur dasar, disiplin data akan lebih mudah dibangun dan dashboard mulai terasa manfaatnya.</p>
                <div class="landing-checklist">
                    @foreach($adoptionPoints as $point)
                        <div><i class="ti ti-check text-success"></i> {{ $point }}</div>
                    @endforeach
                </div>
            </div>
            <div class="col-lg-6">
                <div class="row g-3">
                    @foreach($modules as $module)
                        <div class="col-md-6">
                            <div class="landing-panel p-3 h-100">
                                <div class="d-flex align-items-start gap-3">
                                    <div style="width:42px;height:42px;border-radius:14px;background:#eff6ff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        {!! $module['icon_svg'] !!}
                                    </div>
                                    <div>
                                        <div class="fw-semibold mb-1">{{ $module['name'] }}</div>
                                        <div class="small text-muted">{{ $module['description'] }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

<section id="dashboard" class="py-5 py-lg-6">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Baca Dashboard</div>
            <h2 class="landing-section-title">Setelah mulai jalan, angka penting harus lebih mudah dibaca oleh owner dan admin.</h2>
            <p class="landing-subtext mx-auto">Dashboard bukan sekadar tampilan. Nilainya ada saat tim tahu apa yang harus dicek hari ini, minggu ini, dan keputusan mana yang harus diambil lebih cepat.</p>
        </div>
        <div class="row g-4">
            @foreach($dashboardCards as $card)
                <div class="col-lg-4">
                    <div class="landing-panel p-4 h-100">
                        <div class="mb-3"><i class="ti {{ $card['icon'] }}" style="font-size:1.6rem; color:#1d4ed8;"></i></div>
                        <h3 class="h5 mb-2">{{ $card['title'] }}</h3>
                        <p class="small text-muted mb-0">{{ $card['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="text-center mt-4">
            <a href="{{ $dashboardWa }}" target="_blank" rel="noopener" class="btn btn-dark">Minta Contoh Dashboard</a>
        </div>
    </div>
</section>

<section id="faq" class="py-5 py-lg-6" style="background:#f8fafc; border-top:1px solid var(--landing-line); border-bottom:1px solid var(--landing-line);">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">FAQ</div>
            <h2 class="landing-section-title">Pertanyaan yang biasanya muncul sebelum bisnis mulai digital.</h2>
        </div>
        <div class="row g-4">
            @foreach($faqs as $faq)
                <div class="col-lg-6">
                    <div class="landing-panel p-4 h-100">
                        <h3 class="h5 mb-2">{{ $faq['q'] }}</h3>
                        <p class="small text-muted mb-0">{{ $faq['a'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="py-5 py-lg-6">
    <div class="container">
        <div class="landing-panel p-4 p-lg-5 text-center" style="background:linear-gradient(135deg,#0f172a 0%,#1d4ed8 100%); color:#fff;">
            <div class="landing-eyebrow mb-2" style="color:rgba(255,255,255,.75);">Mulai Dari Diskusi Nyata</div>
            <h2 class="landing-section-title mb-3" style="color:#fff;">Kalau bisnis Anda siap mulai digital, langkah pertama terbaik adalah ngobrol dulu soal kondisi yang ada sekarang.</h2>
            <p class="landing-subtext mx-auto mb-4" style="max-width:760px; color:rgba(255,255,255,.82);">Kami bantu lihat proses yang berjalan, data yang perlu dibawa, dan jalur paling realistis supaya tim benar-benar mulai memakai sistem, bukan hanya punya akun baru.</p>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <a href="{{ $finalWa }}" target="_blank" rel="noopener" class="btn btn-light btn-lg">Mulai Diskusi Sekarang</a>
                <a href="{{ $migrationWa }}" target="_blank" rel="noopener" class="btn btn-outline-light btn-lg">Tanya Soal Migrasi Data</a>
            </div>
        </div>
    </div>
</section>
@endsection
