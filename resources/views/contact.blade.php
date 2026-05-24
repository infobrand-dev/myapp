@extends('layouts.landing')

@section('head_title', 'Contact Us - ' . config('app.name'))
@section('head_description', 'Hubungi tim Meetra untuk mendiskusikan kebutuhan bisnis dan product line yang paling relevan.')

@section('content')
<section class="py-5 py-lg-6" style="background:linear-gradient(135deg,#f8fafc 0%,#eef1ff 100%);border-bottom:1px solid var(--landing-line);">
    <div class="container py-lg-3">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <div class="landing-badge mb-4 mx-auto" style="display:inline-flex;">
                    <i class="ti ti-phone-call"></i> Contact Us
                </div>
                <h1 class="landing-headline mb-4">Mari diskusikan kebutuhan bisnis Anda.</h1>
                <p class="landing-subtext mx-auto">Jika Anda sedang mempertimbangkan product line atau skenario implementasi yang paling tepat, tim kami siap membantu mengarahkan langkah awalnya.</p>
            </div>
        </div>
    </div>
</section>

<section class="py-5 py-lg-6">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="landing-panel rounded-4 p-4 p-lg-5 h-100">
                    <div class="landing-eyebrow mb-2">Konsultasi Langsung</div>
                    <h2 class="landing-section-title mb-3">WhatsApp adalah jalur tercepat untuk memulai percakapan.</h2>
                    <p class="landing-subtext mb-4">Sampaikan konteks singkat mengenai bisnis Anda, kebutuhan utama, dan area yang ingin dirapikan. Tim kami akan membantu mengarahkan product line yang paling relevan.</p>
                    <div class="landing-checklist mb-4">
                        <div><i class="ti ti-check text-success"></i> Diskusi kebutuhan bisnis dan alur operasional</div>
                        <div><i class="ti ti-check text-success"></i> Rekomendasi product line yang paling sesuai</div>
                        <div><i class="ti ti-check text-success"></i> Arahan awal untuk langkah implementasi</div>
                    </div>
                    <a href="https://wa.me/6281222229815" target="_blank" rel="noopener" class="btn btn-dark btn-lg">Chat via WhatsApp</a>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="row g-3">
                    <div class="col-12">
                        <div class="landing-panel rounded-4 p-4 h-100">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <i class="ti ti-brand-whatsapp" style="font-size:1.5rem;color:#16a34a;"></i>
                                <div class="fw-semibold">WhatsApp</div>
                            </div>
                            <div class="small text-muted mb-3">+62 812-222-9815</div>
                            <a href="https://wa.me/6281222229815" target="_blank" rel="noopener" class="btn btn-outline-dark btn-sm">Buka WhatsApp</a>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="landing-panel rounded-4 p-4 h-100">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <i class="ti ti-mail" style="font-size:1.5rem;color:var(--landing-blue);"></i>
                                <div class="fw-semibold">Email</div>
                            </div>
                            <div class="small text-muted mb-3">support@meetra.id</div>
                            <a href="mailto:support@meetra.id" class="btn btn-outline-dark btn-sm">Kirim Email</a>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="landing-panel rounded-4 p-4 h-100">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <i class="ti ti-clock" style="font-size:1.5rem;color:var(--landing-teal);"></i>
                                <div class="fw-semibold">Jam Dukungan</div>
                            </div>
                            <div class="small text-muted">Senin-Sabtu, 08.00-21.00 WIB.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
