@extends('layouts.landing')

@section('head_title', 'CRM Self-Serve - ' . config('app.name'))
@section('head_description', 'CRM self-serve untuk bisnis Indonesia: pipeline, follow-up queue, dan Customer 360 yang siap dipakai tim sales dari desktop maupun mobile.')

@section('content')
@php($money = app(\App\Support\MoneyFormatter::class))
<section class="py-5 py-lg-6" style="background:linear-gradient(135deg,#f8fafc 0%,#eef6ff 100%);border-bottom:1px solid var(--landing-line);">
    <div class="container py-lg-4">
        <div class="row align-items-center g-4">
            <div class="col-lg-6">
                <div class="landing-badge mb-4"><i class="ti ti-address-book"></i> CRM Self-Serve</div>
                <h1 class="landing-headline mb-3">CRM yang siap dipakai tim sales, bukan cuma board lead sederhana.</h1>
                <p class="landing-subtext mb-4">Bangun pipeline penjualan, follow-up queue, dan Customer 360 untuk bisnis Indonesia. Launch awal fokus kuat sebagai CRM standalone, tetapi arsitekturnya sudah siap menerima bridge Accounting dan Omnichannel nanti.</p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('onboarding.create', ['product_line' => 'crm']) }}" class="btn btn-dark btn-lg">Mulai CRM</a>
                    <a href="#pricing" class="btn btn-outline-dark btn-lg">Lihat Pricing</a>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="landing-panel p-4 p-lg-5">
                    <div class="row g-3">
                        <div class="col-6"><div class="border rounded-4 p-3 h-100"><div class="small text-muted text-uppercase">Customer 360</div><div class="fw-semibold mt-1">Deal, follow-up, dan timeline internal dalam satu layar.</div></div></div>
                        <div class="col-6"><div class="border rounded-4 p-3 h-100"><div class="small text-muted text-uppercase">Follow-Up Queue</div><div class="fw-semibold mt-1">Today, overdue, upcoming, completed, dan mine.</div></div></div>
                        <div class="col-6"><div class="border rounded-4 p-3 h-100"><div class="small text-muted text-uppercase">Pipelines</div><div class="fw-semibold mt-1">Stage tidak hardcoded dan siap diatur per tenant.</div></div></div>
                        <div class="col-6"><div class="border rounded-4 p-3 h-100"><div class="small text-muted text-uppercase">WhatsApp-ready</div><div class="fw-semibold mt-1">Siap menerima activity bridge tanpa redesign besar.</div></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 py-lg-6">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="landing-panel p-4 h-100">
                    <div class="landing-eyebrow mb-2">Masalah Umum</div>
                    <h2 class="landing-section-title mb-3">Lead masuk, tapi follow-up tercecer.</h2>
                    <p class="landing-subtext mb-0">Banyak tim sales UKM dan growing business di Indonesia masih mengandalkan spreadsheet, chat pribadi, atau board seadanya. Akibatnya owner sulit melihat bottleneck, lead stale, atau siapa yang belum follow up.</p>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="row g-3">
                    <div class="col-md-6"><div class="landing-panel p-4 h-100"><div class="fw-semibold mb-2">Customer 360</div><div class="text-muted small">Lihat profil customer, open deals, pending follow-up, dan timeline internal CRM dari satu halaman yang nyaman dibuka di mobile.</div></div></div>
                    <div class="col-md-6"><div class="landing-panel p-4 h-100"><div class="fw-semibold mb-2">Pipeline + kanban + list</div><div class="text-muted small">Tim bisa triage deal lewat list atau geser stage di kanban tanpa kehilangan fallback saat JavaScript gagal.</div></div></div>
                    <div class="col-md-6"><div class="landing-panel p-4 h-100"><div class="fw-semibold mb-2">Source tracking</div><div class="text-muted small">Pantau source mana yang paling banyak masuk, paling banyak menang, dan stage mana yang paling macet.</div></div></div>
                    <div class="col-md-6"><div class="landing-panel p-4 h-100"><div class="fw-semibold mb-2">Automation-ready</div><div class="text-muted small">Release awal kuat sebagai standalone suite, tetapi hook timeline dan placeholder bridge sudah siap untuk Omnichannel dan Accounting.</div></div></div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="pricing" class="py-5 py-lg-6" style="background:#f8fafc;border-top:1px solid var(--landing-line);border-bottom:1px solid var(--landing-line);">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Pricing</div>
            <h2 class="landing-section-title mb-3">Starter, Growth, Scale</h2>
            <p class="landing-subtext mx-auto" style="max-width:760px;">Semua tier CRM memakai alur self-serve yang sama: pilih plan, buat workspace, checkout, aktif, lalu langsung masuk onboarding wizard CRM.</p>
        </div>
        <div class="row g-4">
            @foreach($publicPlans as $plan)
                @php($sales = (array) ($plan->sales_meta ?? []))
                <div class="col-lg-4">
                    <div class="landing-panel p-4 h-100 {{ !empty($sales['recommended']) ? 'border-dark' : '' }}">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                            <div>
                                <div class="fw-semibold fs-4">{{ $plan->name }}</div>
                                <div class="text-muted small">{{ $sales['tagline'] ?? $plan->display_name }}</div>
                            </div>
                            @if(!empty($sales['recommended']))
                                <span class="badge bg-dark text-white">Recommended</span>
                            @endif
                        </div>
                        <div class="mb-3">
                            <div class="display-6 fw-bold">{{ $money->format((float) ($sales['price'] ?? 0), strtoupper((string) ($sales['currency'] ?? 'IDR'))) }}</div>
                            <div class="text-muted small">per workspace / bulan</div>
                        </div>
                        <div class="small text-muted mb-3">{{ $sales['description'] ?? '' }}</div>
                        <div class="small mb-3">
                            <div>- {{ (int) (($plan->limits ?? [])[\App\Support\PlanLimit::USERS] ?? 0) }} users</div>
                            <div>- {{ number_format((int) (($plan->limits ?? [])[\App\Support\PlanLimit::CONTACTS] ?? 0), 0, ',', '.') }} contacts</div>
                            <div>- {{ (int) (($plan->limits ?? [])[\App\Support\PlanLimit::CRM_PIPELINES] ?? 0) }} pipelines</div>
                            <div>- {{ number_format((int) (($plan->limits ?? [])[\App\Support\PlanLimit::CRM_ACTIVE_DEALS] ?? 0), 0, ',', '.') }} active deals</div>
                            <div>- {{ !empty(($plan->features ?? [])[\App\Support\PlanFeature::CRM_EXPORTS]) ? 'Export included' : 'No export yet' }}</div>
                            <div>- {{ !empty(($plan->features ?? [])[\App\Support\PlanFeature::CRM_MANAGER_VISIBILITY]) ? 'Manager visibility included' : 'Personal/team visibility only' }}</div>
                        </div>
                        @if(!empty($sales['highlights']))
                            <div class="small text-muted mb-4">
                                @foreach($sales['highlights'] as $highlight)
                                    <div>- {{ $highlight }}</div>
                                @endforeach
                            </div>
                        @endif
                        <a href="{{ route('onboarding.create', ['product_line' => 'crm', 'plan' => $plan->code]) }}" class="btn btn-dark w-100">Pilih {{ $plan->name }}</a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="py-5 py-lg-6">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="landing-panel p-4 h-100">
                    <div class="landing-eyebrow mb-2">FAQ</div>
                    <div class="fw-semibold mb-2">Apakah CRM ini sudah siap dipakai tanpa Accounting atau Omnichannel?</div>
                    <div class="text-muted small">Ya. Launch milestone ini memang dibuat supaya CRM kuat sebagai standalone suite. Timeline bridge ke suite lain hanya tampil sebagai placeholder yang fail-closed saat suite lain belum aktif.</div>
                    <div class="fw-semibold mt-4 mb-2">Apakah mobile friendly?</div>
                    <div class="text-muted small">Ya. Halaman inti memakai stacked cards, sticky action, filter yang lebih ringkas, dan queue yang tetap nyaman untuk touch device.</div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="landing-panel p-4 h-100">
                    <div class="fw-semibold mb-2">Kapan bridge WhatsApp atau invoice masuk ke Customer 360?</div>
                    <div class="text-muted small mb-4">Struktur service dan timeline event sudah disiapkan sekarang. Bridge penuh untuk Omnichannel dan Accounting bisa menyusul tanpa membongkar ulang layout CRM.</div>
                    <a href="{{ route('onboarding.create', ['product_line' => 'crm']) }}" class="btn btn-dark btn-lg">Mulai Self-Serve CRM</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
