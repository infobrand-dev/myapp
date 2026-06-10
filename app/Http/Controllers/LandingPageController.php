<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\PlatformAffiliateService;
use App\Services\TenantOnboardingSalesService;
use App\Support\PublicModuleCatalog;
use App\Support\WorkspaceUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    public function omnichannel(
        Request $request,
        TenantOnboardingSalesService $sales,
        PlatformAffiliateService $affiliateService,
        WorkspaceUrl $workspaceUrl
    ): View|RedirectResponse
    {
        $affiliate = $affiliateService->captureFromRequest($request);

        if ($redirect = $this->landingHostRedirect($request)) {
            return $redirect;
        }

        if (auth()->check()) {
            return redirect()->away($workspaceUrl->forCurrentUser($request));
        }

        return view('landing-omnichannel', [
            'plans' => $sales->publicPlans(),
            'workspaceUrl' => $workspaceUrl->forCurrentUser($request, false),
            'affiliate' => $affiliate,
        ]);
    }

    public function accounting(
        Request $request,
        TenantOnboardingSalesService $sales,
        PublicModuleCatalog $catalog,
        WorkspaceUrl $workspaceUrl
    ): View|RedirectResponse
    {
        if ($redirect = $this->landingHostRedirect($request)) {
            return $redirect;
        }

        if (auth()->check()) {
            return redirect()->away($workspaceUrl->forCurrentUser($request));
        }

        return view('landing-accounting', [
            'publicPlans' => $sales->publicPlans('accounting'),
            'modules' => $catalog->modules($catalog->accountingSlugs()),
            'supportingModules' => $catalog->modules(['purchases', 'inventory', 'discounts']),
            'addonModules' => $catalog->modules(['point-of-sale']),
        ]);
    }

    public function commerce(
        Request $request,
        TenantOnboardingSalesService $sales,
        PublicModuleCatalog $catalog,
        WorkspaceUrl $workspaceUrl
    ): View|RedirectResponse
    {
        if ($redirect = $this->landingHostRedirect($request)) {
            return $redirect;
        }

        if (auth()->check()) {
            return redirect()->away($workspaceUrl->forCurrentUser($request));
        }

        return view('landing-commerce', [
            'publicPlans' => $sales->publicPlans('commerce'),
            'modules' => $catalog->modules($catalog->commerceSlugs()),
            'supportingModules' => $catalog->modules(['products', 'contacts', 'sales', 'payments']),
        ]);
    }

    public function crm(
        Request $request,
        TenantOnboardingSalesService $sales,
        PublicModuleCatalog $catalog,
        WorkspaceUrl $workspaceUrl
    ): View|RedirectResponse
    {
        if ($redirect = $this->landingHostRedirect($request)) {
            return $redirect;
        }

        if (auth()->check()) {
            return redirect()->away($workspaceUrl->forCurrentUser($request));
        }

        return view('landing-crm', [
            'publicPlans' => $sales->publicPlans('crm'),
            'modules' => $catalog->modules($catalog->crmSlugs()),
            'supportingModules' => $catalog->modules(['contacts']),
            'painPoints' => [
                ['icon' => 'ti-messages-off', 'text' => 'Lead masuk dari berbagai kanal — WhatsApp, Instagram, referral — tapi tidak ada yang mencatat siapa yang sudah dihubungi dan siapa yang belum'],
                ['icon' => 'ti-clock-x', 'text' => 'Janji follow-up sering terlupa karena hanya disimpan di kepala atau di catatan yang berbeda-beda'],
                ['icon' => 'ti-eye-off', 'text' => 'Owner atau manajer tidak bisa lihat progress pipeline tim tanpa harus tanya satu per satu setiap hari'],
                ['icon' => 'ti-user-minus', 'text' => 'Saat sales resign, semua data lead dan histori percakapan ikut hilang karena tersimpan di ponsel pribadi'],
                ['icon' => 'ti-chart-off', 'text' => 'Tidak tahu source mana yang paling banyak menghasilkan deal — semua terasa sama padahal tidak'],
                ['icon' => 'ti-circle-dashed', 'text' => '"Deal ini masih proses" tapi tidak ada yang tahu sudah di tahap mana dan kapan terakhir dikerjakan'],
            ],
            'featureCards' => [
                [
                    'icon' => 'ti-layout-kanban',
                    'label' => 'Pipeline & Kanban',
                    'title' => 'Lihat semua deal di satu papan yang jelas',
                    'desc' => 'Pipeline visual dengan tampilan kanban dan list. Geser deal antar stage, lihat nilai total per stage, dan identifikasi bottleneck dalam hitungan detik.',
                    'points' => ['Stage pipeline bisa dikustomisasi sesuai proses sales Anda', 'Tampilan kanban untuk visual, list untuk triage cepat', 'Filter berdasarkan sales, stage, nilai, atau tanggal'],
                ],
                [
                    'icon' => 'ti-bell-ringing-2',
                    'label' => 'Follow-Up Queue',
                    'title' => 'Tidak ada lead yang terlupakan lagi',
                    'desc' => 'Antrian follow-up harian yang otomatis tersusun: hari ini, overdue, upcoming, dan selesai. Tim tahu persis apa yang harus dilakukan tanpa harus tanya ke manajer.',
                    'points' => ['Queue pribadi per sales (tab "Mine") dan queue tim', 'Overdue otomatis naik ke atas antrian', 'Reminder terjadwal yang bisa diset per deal'],
                ],
                [
                    'icon' => 'ti-user-circle',
                    'label' => 'Customer 360',
                    'title' => 'Semua histori customer dalam satu layar',
                    'desc' => 'Buka profil customer dan langsung lihat: semua deal yang pernah ada, catatan interaksi, follow-up yang pending, dan timeline aktivitas lengkap.',
                    'points' => ['Profil lengkap: kontak, perusahaan, sumber lead', 'Timeline internal: semua catatan dan aktivitas tercatat', 'Nyaman dibuka dari mobile saat bertemu customer'],
                ],
                [
                    'icon' => 'ti-chart-dots',
                    'label' => 'Source Tracking',
                    'title' => 'Tahu channel mana yang paling menghasilkan',
                    'desc' => 'Lacak dari mana setiap lead berasal, source mana yang paling sering menang, dan stage mana yang paling banyak macet — data nyata untuk keputusan marketing yang lebih tepat.',
                    'points' => ['Laporan konversi per source (Instagram, referral, web, dll)', 'Identifikasi stage dengan drop-off tertinggi', 'Export data untuk analisis lebih lanjut'],
                ],
                [
                    'icon' => 'ti-device-mobile-code',
                    'label' => 'Mobile-Ready',
                    'title' => 'Sales di lapangan tetap terhubung',
                    'desc' => 'Semua halaman inti CRM dioptimalkan untuk mobile. Update deal, tambah catatan, dan cek queue follow-up langsung dari ponsel — tanpa perlu buka laptop.',
                    'points' => ['Tampilan stacked card yang nyaman untuk layar kecil', 'Filter ringkas untuk akses cepat di mobile', 'Sticky action bar untuk aksi yang paling sering dipakai'],
                ],
                [
                    'icon' => 'ti-eye-check',
                    'label' => 'Manager Visibility',
                    'title' => 'Owner dan manajer punya gambaran penuh',
                    'desc' => 'Tampilan khusus untuk manajer: lihat pipeline seluruh tim, pantau progress per sales, dan identifikasi siapa yang butuh bantuan — tanpa harus tanya satu per satu.',
                    'points' => ['Dashboard pipeline level tim untuk manajer', 'Bandingkan performa antar sales secara objektif', 'Alert otomatis untuk deal yang sudah terlalu lama stagnan'],
                ],
            ],
            'workflowSteps' => [
                ['no' => '1', 'icon' => 'ti-user-plus', 'color' => '#2563eb', 'title' => 'Lead Dicatat ke Pipeline', 'text' => 'Setiap lead baru masuk langsung dicatat ke pipeline — dengan nama, sumber, nilai estimasi, dan assigned sales. Bisa dari form, input manual, atau import. Tidak ada yang terlewat.'],
                ['no' => '2', 'icon' => 'ti-bell-ringing', 'color' => '#3b82f6', 'title' => 'Follow-Up Queue Otomatis Tersusun', 'text' => 'Sistem langsung memasukkan deal ke antrian follow-up sesuai jadwal yang diset. Setiap pagi, tim tahu persis siapa yang harus dihubungi hari ini — dan mana yang sudah overdue.'],
                ['no' => '3', 'icon' => 'ti-notes', 'color' => '#60a5fa', 'title' => 'Setiap Interaksi Tercatat di Customer 360', 'text' => 'Telepon, pertemuan, email, catatan — semua terekam di timeline Customer 360. Siapapun di tim bisa buka dan langsung tahu konteks terbaru, tanpa perlu tanya ke sales yang bersangkutan.'],
                ['no' => '4', 'icon' => 'ti-trophy', 'color' => '#93c5fd', 'title' => 'Deal Ditutup, Data Tersimpan', 'text' => 'Deal menang atau kalah, data tetap tersimpan rapi — dengan alasan, nilai, dan histori lengkap. Jadikan bahan evaluasi untuk meningkatkan win rate tim ke depannya.'],
            ],
            'impactStats' => [
                ['stat' => '0 lead', 'label' => 'yang hilang karena tidak tertrack', 'desc' => 'Semua lead tercatat di pipeline — tidak ada yang tercecer di chat atau catatan pribadi'],
                ['stat' => 'Tiap pagi', 'label' => 'tim tahu persis siapa yang harus dihubungi', 'desc' => 'Follow-up queue menyusun prioritas otomatis berdasarkan jadwal dan overdue'],
                ['stat' => 'Real-time', 'label' => 'visibilitas pipeline untuk owner & manager', 'desc' => 'Pantau progress seluruh tim tanpa harus rapat atau tanya satu per satu'],
                ['stat' => 'Penuh', 'label' => 'histori customer tersimpan — bahkan setelah sales resign', 'desc' => 'Data tidak ikut pergi bersama salesnya. Semua tersimpan di workspace tim'],
            ],
            'testimonials' => [
                ['quote' => '"Dulu follow-up kami 100% andalkan ingatan dan reminder di HP masing-masing. Setelah pakai CRM Meetra, queue langsung kelihatan dan tidak ada lagi alasan lupa. Win rate naik karena tidak ada lead yang dibiarkan dingin."', 'name' => 'Hendra K.', 'role' => 'Sales Manager, distributor FMCG — tim 8 orang', 'img_no' => '6a'],
                ['quote' => '"Sebagai owner, saya akhirnya bisa lihat pipeline tim tanpa harus rapat setiap Senin. Saya tahu deal mana yang macet, siapa yang perlu dibantu, dan berapa estimasi revenue bulan ini — tanpa tanya ke siapa-siapa."', 'name' => 'Kartika R.', 'role' => 'Founder, B2B software house Surabaya', 'img_no' => '6b'],
                ['quote' => '"Yang paling terasa adalah Customer 360-nya. Waktu saya take over lead dari sales yang resign, semua histori sudah ada — kapan terakhir dihubungi, apa yang dibahas, dan langkah berikutnya. Tidak perlu mulai dari nol."', 'name' => 'Fariz M.', 'role' => 'Account Executive, perusahaan konsultan HR', 'img_no' => '6c'],
            ],
            'faqs' => [
                ['q' => 'Apakah CRM ini bisa langsung dipakai tanpa pelatihan panjang?', 'a' => 'Ya. Alur CRM Meetra mengikuti cara kerja tim sales yang sudah familiar: catat lead, set follow-up, update stage, catat interaksi. Tidak ada konsep baru yang perlu dipelajari dari awal. Sebagian besar tim bisa produktif di hari pertama.'],
                ['q' => 'Berapa orang yang bisa pakai dalam satu workspace?', 'a' => 'Tergantung paket. Paket Starter cocok untuk tim kecil, Growth untuk tim yang berkembang, dan Scale untuk operasional yang lebih besar. Semua tim berada dalam satu workspace yang sama sehingga data terpusat.'],
                ['q' => 'Apakah owner bisa melihat aktivitas seluruh tim sales?', 'a' => 'Ya, dengan fitur Manager Visibility (tersedia di paket Growth ke atas). Owner dan manajer bisa melihat pipeline seluruh tim, membandingkan performa antar sales, dan mengidentifikasi deal yang butuh perhatian — tanpa mengganggu alur kerja harian tim.'],
                ['q' => 'Kalau ada sales yang resign, datanya ikut hilang tidak?', 'a' => 'Tidak. Semua data lead, deal, dan histori interaksi tersimpan di workspace perusahaan — bukan di akun pribadi sales yang bersangkutan. Deal bisa di-reassign ke sales lain dalam hitungan detik.'],
                ['q' => 'Apakah ada integrasi WhatsApp?', 'a' => 'CRM Meetra saat ini fokus sebagai standalone CRM yang solid: pipeline, follow-up, dan Customer 360. Integrasi channel seperti WhatsApp direncanakan hadir ke depannya sebagai perluasan natural — tanpa perlu pindah platform atau setup ulang dari nol.'],
                ['q' => 'Apakah ada free trial?', 'a' => 'Ya. Anda bisa mulai dengan trial 14 hari penuh tanpa kartu kredit. Semua fitur sesuai paket yang dipilih aktif sejak hari pertama — tidak ada fitur yang dikunci selama trial.'],
            ],
        ]);
    }

    public function mulaiDigital(
        Request $request,
        PublicModuleCatalog $catalog,
        WorkspaceUrl $workspaceUrl
    ): View|RedirectResponse
    {
        if ($redirect = $this->landingHostRedirect($request)) {
            return $redirect;
        }

        if (auth()->check()) {
            return redirect()->away($workspaceUrl->forCurrentUser($request));
        }

        return view('landing-mulai-digital', [
            'modules' => $catalog->modules([
                'contacts',
                'products',
                'sales',
                'payments',
                'finance',
                'reports',
            ]),
        ]);
    }

    public function websiteApps(
        Request $request,
        PublicModuleCatalog $catalog,
        WorkspaceUrl $workspaceUrl
    ): View|RedirectResponse
    {
        if ($redirect = $this->landingHostRedirect($request)) {
            return $redirect;
        }

        if (auth()->check()) {
            return redirect()->away($workspaceUrl->forCurrentUser($request));
        }

        return view('landing-website-apps', [
            'modules' => $catalog->modules([
                'live_chat',
                'crm',
                'contacts',
                'sales',
                'payments',
                'reports',
            ]),
        ]);
    }

    public function websiteService(Request $request, WorkspaceUrl $workspaceUrl): View|RedirectResponse
    {
        if ($redirect = $this->landingHostRedirect($request)) {
            return $redirect;
        }

        if (auth()->check()) {
            return redirect()->away($workspaceUrl->forCurrentUser($request));
        }

        return view('landing-website-service');
    }

    public function products(Request $request, WorkspaceUrl $workspaceUrl): View|RedirectResponse
    {
        if ($redirect = $this->landingHostRedirect($request)) {
            return $redirect;
        }

        if (auth()->check()) {
            return redirect()->away($workspaceUrl->forCurrentUser($request));
        }

        return view('landing-products');
    }

    public function workspaceFinder(
        Request $request,
        PlatformAffiliateService $affiliateService,
        WorkspaceUrl $workspaceUrl
    ): View|RedirectResponse
    {
        $affiliateService->captureFromRequest($request);

        if (auth()->check()) {
            return redirect()->away($workspaceUrl->forCurrentUser($request, false));
        }

        return view('workspace-finder');
    }

    public function about(): View
    {
        return view('about');
    }

    public function contact(): View
    {
        return view('contact');
    }

    public function security(): View
    {
        return view('security');
    }

    public function privacy(): View
    {
        return view('privacy');
    }

    public function terms(): View
    {
        return view('terms');
    }

    public function affiliateRedirect(Request $request, string $slug, PlatformAffiliateService $affiliateService): RedirectResponse
    {
        $affiliate = $affiliateService->findActiveBySlug($slug);
        abort_unless($affiliate, 404);

        $affiliateService->captureAffiliate($request, $affiliate);

        return redirect()->route('landing');
    }

    public function redirectToWorkspaceLogin(Request $request, WorkspaceUrl $workspaceUrl): RedirectResponse
    {
        $data = $request->validate([
            'workspace' => ['required', 'string', 'max:100'],
        ]);

        $workspace = strtolower(trim((string) $data['workspace']));
        $workspace = preg_replace('/[^a-z0-9-]/', '', $workspace) ?: '';

        $tenant = Tenant::query()
            ->where('slug', $workspace)
            ->active()
            ->first();

        if (!$tenant) {
            return back()->withErrors([
                'workspace' => 'Workspace tidak ditemukan atau belum aktif.',
            ])->withInput();
        }

        return redirect()->away($workspaceUrl->loginForTenant($request, $tenant->slug));
    }

    private function landingHostRedirect(Request $request): ?RedirectResponse
    {
        if ($request->attributes->get('platform_admin_host')) {
            return auth()->check()
                ? redirect()->route('platform.dashboard')
                : redirect()->route('login');
        }

        if ($request->attributes->has('tenant_id')) {
            return redirect()->route('landing');
        }

        return null;
    }
}
