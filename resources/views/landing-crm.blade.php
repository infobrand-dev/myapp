@extends('layouts.landing')

@section('head_title', config('app.name') . ' CRM — Pipeline, Follow-Up, dan Customer 360 untuk Tim Sales Indonesia')
@section('head_description', 'Lead masuk tapi follow-up sering lupa? CRM Meetra rapikan pipeline penjualan, antrian follow-up harian, dan histori lengkap setiap customer — dari desktop maupun mobile.')

@push('head')
<style>
    .crm-hero-title {
        font-size: clamp(2.35rem, 5vw, 4.2rem);
        line-height: 1.07;
        letter-spacing: -0.04em;
    }
    .crm-hero-subtitle {
        font-size: clamp(1.05rem, 1.8vw, 1.28rem);
        line-height: 1.75;
        color: #475569;
        max-width: 44rem;
    }
    .crm-lead {
        font-size: 1.075rem;
        line-height: 1.8;
        color: #475569;
    }
    .crm-card-text {
        font-size: 1rem;
        line-height: 1.75;
        color: #475569;
    }
    .crm-icon-badge {
        width: 3rem;
        height: 3rem;
        border-radius: 1rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        background: linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%);
        color: #2563eb;
    }
    .crm-stat-number {
        font-size: 2.25rem;
        font-weight: 800;
        letter-spacing: -0.04em;
        line-height: 1;
        color: #2563eb;
    }
    .crm-img-placeholder {
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 60%, #bfdbfe 100%);
        border: 2px dashed #3b82f6;
        border-radius: 1.25rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #2563eb;
        text-align: center;
        gap: 0.5rem;
        font-size: 0.85rem;
        font-weight: 600;
        padding: 1.5rem;
    }
    .crm-module-badge {
        width: 2.75rem;
        height: 2.75rem;
        border-radius: 0.9rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        background: #eff6ff;
        color: #2563eb;
        overflow: hidden;
    }
    .crm-module-badge svg {
        width: 1.85rem;
        height: 1.85rem;
        display: block;
    }
    .crm-testimonial-avatar {
        width: 3rem;
        height: 3rem;
        border-radius: 999px;
        overflow: hidden;
        background: #dbeafe;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #2563eb;
        flex-shrink: 0;
    }
    .crm-faq-item {
        border-radius: 1rem;
        border: 1px solid var(--landing-line);
        background: rgba(255,255,255,0.82);
    }
    .crm-faq-item summary {
        list-style: none;
        cursor: pointer;
        padding: 1.1rem 1.25rem;
        font-weight: 600;
        font-size: 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }
    .crm-faq-item summary::-webkit-details-marker { display: none; }
    .crm-faq-item summary::after {
        content: '\ea6e';
        font-family: 'tabler-icons';
        font-size: 1.15rem;
        color: #2563eb;
        transition: transform 0.2s;
        flex-shrink: 0;
    }
    .crm-faq-item[open] summary::after { transform: rotate(180deg); }
    .crm-faq-body {
        padding: 0 1.25rem 1.1rem;
        color: #475569;
        font-size: 0.95rem;
        line-height: 1.75;
    }
</style>
@endpush

@section('content')
@php($money = app(\App\Support\MoneyFormatter::class))

{{-- SECTION 1: HERO --}}
<section class="landing-hero py-5 py-lg-6" style="background:radial-gradient(circle at top right,rgba(37,99,235,.15),transparent 40%), linear-gradient(160deg,#f8fafc 0%,#eef6ff 55%,#f0f9ff 100%); border-bottom:1px solid var(--landing-line);">
    <div class="container py-lg-4">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <div class="landing-badge mb-4" style="background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe;">
                    <i class="ti ti-users-group"></i> CRM untuk Tim Sales Indonesia
                </div>
                <h1 class="landing-headline crm-hero-title mb-4">
                    Lead sudah masuk.<br><span style="color:#2563eb;">Tapi siapa yang follow up?</span>
                </h1>
                <p class="landing-subtext crm-hero-subtitle mb-4">
                    CRM Meetra merapikan pipeline penjualan, mengingatkan follow-up yang terlewat, dan memberi Anda gambaran lengkap setiap customer — sehingga tidak ada satu pun lead yang hilang di tengah jalan.
                </p>
                <div class="d-flex flex-wrap gap-3 mb-4">
                    <a href="{{ route('onboarding.create', ['product_line' => 'crm']) }}" class="btn btn-lg btn-dark">
                        Mulai Pakai CRM
                    </a>
                    <a href="#cara-kerja" class="btn btn-lg btn-outline-dark">Lihat Cara Kerjanya</a>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <span class="landing-pill"><i class="ti ti-check"></i> Pipeline visual & kanban</span>
                    <span class="landing-pill"><i class="ti ti-check"></i> Follow-up queue harian</span>
                    <span class="landing-pill"><i class="ti ti-check"></i> Customer 360</span>
                    <span class="landing-pill"><i class="ti ti-check"></i> Mobile-friendly</span>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="landing-panel p-4 p-lg-5" style="border:1px solid rgba(15,23,42,.08); box-shadow:0 28px 60px rgba(15,23,42,.08);">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 rounded-4 h-100" style="background:#fef2f2; border:1px solid rgba(239,68,68,.12);">
                                <div class="fw-semibold mb-1 small text-danger">❌ Tanpa CRM</div>
                                <ul class="small text-muted mb-0 ps-3 mt-2">
                                    <li>Lead dicatat di WhatsApp grup atau spreadsheet berbeda</li>
                                    <li>Follow-up sering terlupa atau terlambat</li>
                                    <li>Owner tidak tahu progress setiap sales</li>
                                    <li>Deal hilang karena tidak ada yang track</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded-4 h-100" style="background:#f0fdf4; border:1px solid rgba(22,163,74,.14);">
                                <div class="fw-semibold mb-1 small text-success">✅ Dengan CRM Meetra</div>
                                <ul class="small text-muted mb-0 ps-3 mt-2">
                                    <li>Semua lead & deal terpantau dari satu pipeline</li>
                                    <li>Queue follow-up harian selalu tahu giliran siapa</li>
                                    <li>Owner/manager lihat progress tim real-time</li>
                                    <li>Histori lengkap tiap customer tersimpan rapi</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-12">
                            {{-- Image Placeholder 1 --}}
                            <div class="crm-img-placeholder" style="min-height:155px;">
                                <i class="ti ti-layout-kanban" style="font-size:2.5rem; opacity:0.6;"></i>
                                <div><strong>[IMAGE 1]</strong> — Mockup dashboard CRM: tampilan pipeline kanban dengan deal cards di beberapa stage (New Lead, Contacted, Proposal, Closing, Won), setiap card punya nama, nilai deal, dan avatar sales</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- SECTION 2: TRUST STRIP --}}
<section class="py-4" style="border-bottom:1px solid var(--landing-line); background:#fff;">
    <div class="container">
        <div class="landing-trust-strip">
            <div class="landing-trust-item"><i class="ti ti-target-arrow"></i> Pipeline visual</div>
            <div class="landing-trust-sep"></div>
            <div class="landing-trust-item"><i class="ti ti-bell-ringing"></i> Follow-up queue</div>
            <div class="landing-trust-sep"></div>
            <div class="landing-trust-item"><i class="ti ti-user-circle"></i> Customer 360</div>
            <div class="landing-trust-sep"></div>
            <div class="landing-trust-item"><i class="ti ti-chart-bar"></i> Source tracking</div>
            <div class="landing-trust-sep"></div>
            <div class="landing-trust-item"><i class="ti ti-device-mobile"></i> Mobile-ready</div>
            <div class="landing-trust-sep"></div>
            <div class="landing-trust-item"><i class="ti ti-server-2"></i> Server Indonesia</div>
        </div>
    </div>
</section>

{{-- SECTION 3: PAIN POINTS --}}
<section id="masalah" class="py-5 py-lg-6">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <div class="landing-eyebrow mb-2">Kenali Masalahnya</div>
                <h2 class="landing-section-title mb-3">Tim sales Anda mungkin mengalami ini setiap hari — tanpa sadar bisnis Anda kehilangan deal.</h2>
                <p class="crm-lead mb-4">Bukan karena tim tidak kerja keras. Tapi karena tidak ada sistem yang menangkap semua yang perlu di-follow-up.</p>

                <div class="d-flex flex-column gap-3">
                    @foreach($painPoints as $pain)
                    <div class="d-flex align-items-start gap-3 p-3 rounded-4" style="background:#eff6ff; border:1px solid rgba(37,99,235,.12);">
                        <span class="crm-icon-badge"><i class="ti {{ $pain['icon'] }}" style="font-size:1.2rem;"></i></span>
                        <div class="crm-card-text">{{ $pain['text'] }}</div>
                    </div>
                    @endforeach
                </div>

                <div class="mt-4 p-4 rounded-4" style="background:#eff6ff; border:2px solid rgba(37,99,235,.2);">
                    <div class="fw-bold mb-1">Ini bukan masalah kerja keras.</div>
                    <div class="crm-card-text">Tim sales Anda sudah bekerja. Yang kurang adalah sistem yang menangkap semua lead, mengingatkan setiap follow-up, dan memberi visibilitas ke seluruh pipeline.</div>
                </div>
            </div>

            <div class="col-lg-6">
                {{-- Image Placeholder 2 --}}
                <div class="crm-img-placeholder" style="min-height:480px;">
                    <i class="ti ti-notes-off" style="font-size:3rem; opacity:0.5;"></i>
                    <div><strong>[IMAGE 2]</strong> — Ilustrasi seorang sales rep (pria muda, 25-32 tahun, memegang ponsel) dengan tampilan visual yang menggambarkan chaos: notifikasi dari berbagai aplikasi di sekitar kepalanya, sticky notes di mana-mana, ekspresi kebingungan. Gaya: semi-realistic editorial illustration, palet biru muda dan putih, pencahayaan terang profesional. BUKAN kartun, BUKAN vektor flat.</div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- SECTION 4: FITUR UTAMA --}}
<section id="fitur" class="py-5 py-lg-6" style="background:#f8fafc; border-top:1px solid var(--landing-line); border-bottom:1px solid var(--landing-line);">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Apa yang Ada di CRM Meetra</div>
            <h2 class="landing-section-title mb-3">Satu workspace. Semua yang dibutuhkan tim sales Anda untuk closing lebih banyak deal.</h2>
            <p class="landing-subtext mx-auto" style="max-width:640px;">Setiap fitur CRM Meetra dirancang untuk menjawab masalah tim sales Indonesia yang nyata — bukan fitur untuk fitur.</p>
        </div>

        <div class="row g-4">
            @foreach($featureCards as $feature)
            <div class="col-md-6 col-xl-4">
                <div class="landing-feature-card p-4 h-100">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="crm-module-badge">
                            <i class="ti {{ $feature['icon'] }}" style="font-size:1.5rem;"></i>
                        </div>
                        <div>
                            <div class="landing-eyebrow mb-1">{{ $feature['label'] }}</div>
                            <div class="h5 mb-0">{{ $feature['title'] }}</div>
                        </div>
                    </div>
                    <p class="crm-card-text mb-3">{{ $feature['desc'] }}</p>
                    <div class="small d-flex flex-column gap-1">
                        @foreach($feature['points'] as $point)
                            <div class="d-flex align-items-start gap-2">
                                <i class="ti ti-check text-success flex-shrink-0 mt-1" style="font-size:0.9rem;"></i>
                                <span class="text-muted">{{ $point }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-5">
            {{-- Image Placeholder 3 --}}
            <div class="crm-img-placeholder" style="min-height:200px;">
                <i class="ti ti-sitemap" style="font-size:2.5rem; opacity:0.5;"></i>
                <div><strong>[IMAGE 3]</strong> — Diagram alur CRM: Lead Masuk → Dicatat di Pipeline → Follow-Up Queue → Customer 360 → Deal Closing → Won. Gaya diagram horizontal modern dengan icon tiap tahap, warna biru ke hijau, background putih bersih.</div>
            </div>
        </div>
    </div>
</section>

{{-- SECTION 5: CARA KERJA --}}
<section id="cara-kerja" class="py-5 py-lg-6">
    <div class="container">
        <div class="row g-5 align-items-start">
            <div class="col-lg-4">
                <div class="landing-eyebrow mb-2">Cara Kerja CRM</div>
                <h2 class="landing-section-title mb-3">Dari lead pertama masuk sampai deal ditutup — semua terpantau di satu tempat.</h2>
                <p class="crm-lead mb-4">Tim sales Anda tidak perlu belajar alat baru yang rumit. CRM Meetra mengikuti alur kerja yang sudah biasa dipakai tim sales — hanya jauh lebih rapi.</p>
                <a href="{{ route('onboarding.create', ['product_line' => 'crm']) }}" class="btn btn-dark">
                    Mulai — Gratis 14 Hari
                </a>
            </div>
            <div class="col-lg-8">
                <div class="row g-3">
                    @foreach($workflowSteps as $step)
                    <div class="col-md-6">
                        <div class="landing-panel p-4 h-100" style="border-left:4px solid {{ $step['color'] }};">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <div style="width:2rem;height:2rem;border-radius:999px;background:{{ $step['color'] }};color:#fff;font-size:0.8rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;">{{ $step['no'] }}</div>
                                <i class="ti {{ $step['icon'] }}" style="font-size:1.25rem; color:{{ $step['color'] }};"></i>
                            </div>
                            <h3 class="h5 mb-2">{{ $step['title'] }}</h3>
                            <p class="crm-card-text mb-0">{{ $step['text'] }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="mt-3">
                    {{-- Image Placeholder 4 --}}
                    <div class="crm-img-placeholder" style="min-height:175px;">
                        <i class="ti ti-layout-list" style="font-size:2.2rem; opacity:0.5;"></i>
                        <div><strong>[IMAGE 4]</strong> — Mockup UI tampilan follow-up queue CRM: daftar dengan tab "Hari Ini (5)", "Overdue (2)", "Upcoming (8)". Setiap item punya nama customer, nama sales, waktu jadwal, dan tombol aksi. Badge merah untuk overdue, oranye untuk hari ini, abu untuk upcoming. Clean SaaS UI.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- SECTION 6: DAMPAK / STATS --}}
<section class="py-5 py-lg-6" style="background: linear-gradient(135deg,#f8fafc 0%,#eef6ff 55%,#f0f9ff 100%); border-top:1px solid var(--landing-line); border-bottom:1px solid var(--landing-line);">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-5">
                {{-- Image Placeholder 5 --}}
                <div class="crm-img-placeholder" style="min-height:380px;">
                    <i class="ti ti-device-laptop" style="font-size:3rem; opacity:0.5;"></i>
                    <div><strong>[IMAGE 5]</strong> — Foto seorang sales manager atau owner bisnis Indonesia (pria/wanita, 30-42 tahun, berpakaian smart casual) sedang tersenyum puas melihat laptop yang menampilkan dashboard CRM dengan pipeline deal. Background: kantor modern Indonesia, pencahayaan alami dari jendela. Ekspresi: tenang dan in-control. Fotografis realistis, bukan ilustrasi.</div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="landing-eyebrow mb-2">Dampak Nyata</div>
                <h2 class="landing-section-title mb-4">Tim yang punya sistem closing lebih banyak. Bukan tim yang lebih besar.</h2>
                <div class="row g-3 mb-4">
                    @foreach($impactStats as $win)
                    <div class="col-sm-6">
                        <div class="landing-panel p-3 h-100">
                            <div class="crm-stat-number mb-1">{{ $win['stat'] }}</div>
                            <div class="fw-semibold small mb-1">{{ $win['label'] }}</div>
                            <div class="text-muted" style="font-size:0.82rem;">{{ $win['desc'] }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="d-flex flex-wrap gap-3">
                    <a href="{{ route('onboarding.create', ['product_line' => 'crm']) }}" class="btn btn-dark">Mulai Pakai CRM</a>
                    <a href="#pricing" class="btn btn-outline-dark">Lihat Harga</a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- SECTION 7: TESTIMONIALS --}}
<section class="py-5 py-lg-6">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Kata Mereka</div>
            <h2 class="landing-section-title">Tim sales yang sudah berhenti kehilangan lead.</h2>
        </div>
        <div class="row g-4">
            @foreach($testimonials as $t)
            <div class="col-lg-4">
                <div class="landing-panel p-4 h-100">
                    <div class="mb-3" style="color:#2563eb; font-size:1.5rem;">❝</div>
                    <p class="crm-card-text mb-4">{{ $t['quote'] }}</p>
                    <div class="d-flex align-items-center gap-3">
                        <div class="crm-testimonial-avatar">
                            {{-- Image Placeholder 6a/6b/6c --}}
                            <i class="ti ti-user" style="font-size:1.3rem;"></i>
                        </div>
                        <div>
                            <div class="fw-semibold small">{{ $t['name'] }}</div>
                            <div class="text-muted" style="font-size:0.8rem;">{{ $t['role'] }}</div>
                        </div>
                    </div>
                    <div class="mt-3 text-muted" style="font-size:0.7rem; border-top:1px solid var(--landing-line); padding-top:0.75rem;">
                        [IMAGE {{ $t['img_no'] }}] — Headshot profesional {{ $t['name'] }}: wajah ramah, background blur netral, pencahayaan natural. Fotografis realistis, bukan ilustrasi.
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- SECTION 8: PRICING --}}
<section id="pricing" class="py-5 py-lg-6" style="background:#f8fafc; border-top:1px solid var(--landing-line); border-bottom:1px solid var(--landing-line);">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Harga CRM</div>
            <h2 class="landing-section-title mb-3">Mulai kecil, scale sesuai pertumbuhan tim sales Anda.</h2>
            <p class="landing-subtext mx-auto" style="max-width:600px;">Pilih paket, buat workspace, dan tim sales Anda langsung bisa pakai CRM hari ini. Tidak ada biaya setup, tidak ada kontrak jangka panjang.</p>
        </div>

        <div class="row g-4 justify-content-center">
            @forelse($publicPlans as $plan)
                @php
                    $sales      = (array) ($plan->sales_meta ?? []);
                    $limits     = (array) ($plan->limits ?? []);
                    $features   = (array) ($plan->features ?? []);
                    $price      = $money->format((float) ($sales['price'] ?? 0), strtoupper((string) ($sales['currency'] ?? 'IDR')));
                    $isFeatured = !empty($sales['recommended']);
                @endphp
                <div class="col-lg-4 col-md-6">
                    <div class="landing-plan-card {{ $isFeatured ? 'featured' : '' }} p-4 h-100">
                        @if($isFeatured)
                            <div class="landing-plan-popular">Paling Populer</div>
                        @endif
                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                            <div>
                                <div class="h4 mb-1">CRM {{ $plan->name }}</div>
                                <div class="text-muted small">{{ $sales['tagline'] ?? $plan->display_name }}</div>
                            </div>
                        </div>
                        <div class="landing-price mb-1">{{ $price }}</div>
                        <p class="text-muted small mb-4">{{ $sales['description'] ?? '' }}</p>

                        <div class="landing-limit-table mb-4">
                            <div class="landing-limit-row">
                                <span class="text-muted small">Users</span>
                                <span class="fw-semibold small">{{ (int) ($limits[\App\Support\PlanLimit::USERS] ?? 0) }}</span>
                            </div>
                            <div class="landing-limit-row">
                                <span class="text-muted small">Contacts</span>
                                <span class="fw-semibold small">{{ number_format((int) ($limits[\App\Support\PlanLimit::CONTACTS] ?? 0), 0, ',', '.') }}</span>
                            </div>
                            <div class="landing-limit-row">
                                <span class="text-muted small">Pipelines</span>
                                <span class="fw-semibold small">{{ (int) ($limits[\App\Support\PlanLimit::CRM_PIPELINES] ?? 0) }}</span>
                            </div>
                            <div class="landing-limit-row">
                                <span class="text-muted small">Active Deals</span>
                                <span class="fw-semibold small">{{ number_format((int) ($limits[\App\Support\PlanLimit::CRM_ACTIVE_DEALS] ?? 0), 0, ',', '.') }}</span>
                            </div>
                            <div class="landing-limit-row">
                                <span class="text-muted small">Export data</span>
                                <span class="fw-semibold small">{{ !empty($features[\App\Support\PlanFeature::CRM_EXPORTS]) ? '✓ Termasuk' : '—' }}</span>
                            </div>
                            <div class="landing-limit-row">
                                <span class="text-muted small">Manager visibility</span>
                                <span class="fw-semibold small">{{ !empty($features[\App\Support\PlanFeature::CRM_MANAGER_VISIBILITY]) ? '✓ Termasuk' : '—' }}</span>
                            </div>
                        </div>

                        @if(!empty($sales['highlights']))
                        <div class="small d-flex flex-column gap-1 mb-4">
                            @foreach((array) $sales['highlights'] as $hl)
                                <div class="d-flex align-items-start gap-2">
                                    <i class="ti ti-check text-success flex-shrink-0" style="font-size:0.9rem; margin-top:0.15rem;"></i>
                                    <span class="text-muted">{{ $hl }}</span>
                                </div>
                            @endforeach
                        </div>
                        @endif

                        <a href="{{ route('onboarding.create', ['product_line' => 'crm', 'plan' => $plan->code]) }}"
                           class="btn {{ $isFeatured ? 'btn-dark' : 'btn-outline-dark' }} w-100">
                            Pilih Paket Ini
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-5">
                    <div class="text-muted">Informasi paket sedang diperbarui. Hubungi kami untuk detail harga.</div>
                    <a href="https://wa.me/6281222229815" target="_blank" rel="noopener" class="btn btn-dark mt-3">Chat WhatsApp</a>
                </div>
            @endforelse
        </div>

        <div class="text-center mt-5">
            <div class="landing-panel p-4 d-inline-block text-start" style="max-width:640px;">
                <div class="d-flex align-items-start gap-3">
                    <i class="ti ti-help-circle flex-shrink-0" style="font-size:1.5rem; color:#2563eb; margin-top:0.1rem;"></i>
                    <div>
                        <div class="fw-semibold mb-1">Tidak yakin paket mana yang cocok untuk tim Anda?</div>
                        <div class="crm-card-text mb-2">Ceritakan ukuran tim dan volume lead per bulan ke kami — kami bantu rekomendasikan paket yang tepat.</div>
                        <a href="https://wa.me/6281222229815?text={{ urlencode('Halo, saya ingin konsultasi paket CRM yang tepat untuk tim sales saya.') }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-dark">
                            <i class="ti ti-brand-whatsapp me-1"></i>Chat WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- SECTION 9: FAQ --}}
<section id="faq" class="py-5 py-lg-6">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-5">
                    <div class="landing-eyebrow mb-2">FAQ</div>
                    <h2 class="landing-section-title">Pertanyaan yang sering ditanyakan.</h2>
                </div>
                <div class="d-flex flex-column gap-3">
                    @foreach($faqs as $faq)
                    <details class="crm-faq-item">
                        <summary>{{ $faq['q'] }}</summary>
                        <div class="crm-faq-body">{{ $faq['a'] }}</div>
                    </details>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

{{-- SECTION 10: FINAL CTA BAND --}}
<section class="py-5 py-lg-6" style="background:#f8fafc; border-top:1px solid var(--landing-line);">
    <div class="container">
        <div class="landing-cta-band p-5 p-lg-6">
            <div class="row g-5 align-items-center">
                <div class="col-lg-7">
                    <div class="landing-eyebrow mb-3" style="color:rgba(255,255,255,0.6);">Siap rapikan pipeline Anda?</div>
                    <h2 class="mb-3" style="font-size:clamp(1.8rem,3.5vw,3rem); font-weight:800; letter-spacing:-0.03em; color:#fff; line-height:1.1;">
                        Lead berikutnya tidak boleh hilang lagi.
                    </h2>
                    <p class="mb-4" style="font-size:1.1rem; color:rgba(255,255,255,0.8); max-width:500px; line-height:1.7;">
                        Setiap lead yang tidak ter-follow-up adalah revenue yang hilang. Buat workspace CRM Anda sekarang dan mulai track setiap deal dari hari ini.
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="{{ route('onboarding.create', ['product_line' => 'crm']) }}" class="btn btn-lg" style="background:#fff; color:#0f172a; font-weight:700; border-radius:999px;">
                            <i class="ti ti-arrow-right me-1"></i>Buat Workspace CRM
                        </a>
                        <a href="https://wa.me/6281222229815?text={{ urlencode('Halo, saya ingin tahu lebih lanjut tentang CRM Meetra sebelum daftar.') }}" target="_blank" rel="noopener"
                           class="btn btn-lg btn-outline-light" style="border-radius:999px;">
                            <i class="ti ti-brand-whatsapp me-1"></i>Tanya Dulu via WA
                        </a>
                    </div>
                    <div class="mt-3 small" style="color:rgba(255,255,255,0.55);">
                        Trial 14 hari. Tidak perlu kartu kredit. Batalkan kapan saja.
                    </div>
                </div>
                <div class="col-lg-5 d-none d-lg-block">
                    {{-- Image Placeholder 7 --}}
                    <div class="rounded-4 d-flex flex-column align-items-center justify-content-center gap-2 text-center"
                         style="min-height:220px; background:rgba(255,255,255,0.1); border:2px dashed rgba(255,255,255,0.3); color:rgba(255,255,255,0.7); font-size:0.82rem; font-weight:600; padding:1.5rem;">
                        <i class="ti ti-chart-arrows-vertical" style="font-size:2.5rem; opacity:0.5;"></i>
                        <div><strong>[IMAGE 7]</strong> — Ilustrasi grafik pipeline sales yang naik: deal cards bergerak dari kiri ke kanan menuju "Won", angka revenue bertumbuh. Gaya flat illustration modern, warna putih dan biru cerah di atas background gelap. Mood: growth, optimistis, winning.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
