<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\PlatformAffiliateService;
use App\Services\TenantOnboardingSalesService;
use Illuminate\Support\Facades\File;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    public function __invoke(Request $request, TenantOnboardingSalesService $sales, PlatformAffiliateService $affiliateService): View|RedirectResponse
    {
        $affiliate = $affiliateService->captureFromRequest($request);

        if ($request->attributes->get('platform_admin_host')) {
            return auth()->check()
                ? redirect()->route('platform.dashboard')
                : redirect()->route('login');
        }

        if ($request->attributes->has('tenant_id')) {
            return auth()->check()
                ? redirect()->route('dashboard')
                : redirect()->route('login');
        }

        if (auth()->check()) {
            return redirect()->away($this->workspaceUrlFor($request));
        }

        return view('landing', [
            'plans' => $sales->publicPlans(),
            'workspaceUrl' => $this->workspaceUrlFor($request, false),
            'affiliate' => $affiliate,
        ]);
    }

    public function omnichannel(Request $request, TenantOnboardingSalesService $sales, PlatformAffiliateService $affiliateService): View|RedirectResponse
    {
        $affiliate = $affiliateService->captureFromRequest($request);

        if (auth()->check()) {
            return redirect()->away($this->workspaceUrlFor($request));
        }

        return view('landing-omnichannel', [
            'plans' => $sales->publicPlans(),
            'workspaceUrl' => $this->workspaceUrlFor($request, false),
            'affiliate' => $affiliate,
        ]);
    }

    public function accounting(Request $request, TenantOnboardingSalesService $sales): View|RedirectResponse
    {
        if (auth()->check()) {
            return redirect()->away($this->workspaceUrlFor($request));
        }

        return view('landing-accounting', [
            'publicPlans' => $sales->publicPlans('accounting'),
            'modules' => $this->publicModuleCatalog($this->accountingModuleSlugs()),
            'supportingModules' => $this->publicModuleCatalog(['purchases', 'inventory', 'discounts']),
            'addonModules' => $this->publicModuleCatalog(['point-of-sale']),
        ]);
    }

    public function mulaiDigital(Request $request): View|RedirectResponse
    {
        if (auth()->check()) {
            return redirect()->away($this->workspaceUrlFor($request));
        }

        return view('landing-mulai-digital', [
            'modules' => $this->publicModuleCatalog([
                'contacts',
                'products',
                'sales',
                'payments',
                'finance',
                'reports',
            ]),
        ]);
    }

    public function meetra(Request $request): View|RedirectResponse
    {
        if (auth()->check()) {
            return redirect()->away($this->workspaceUrlFor($request));
        }

        return view('landing-meetra', [
            'featuredModules' => $this->publicModuleCatalog([
                'conversations',
                'whatsapp_api',
                'social_media',
                'live_chat',
                'chatbot',
                'crm',
                'sales',
                'payments',
                'purchases',
                'finance',
                'point-of-sale',
                'reports',
                'task_management',
                'shortlink',
            ]),
            'accountingModules' => $this->publicModuleCatalog($this->accountingModuleSlugs()),
        ]);
    }

    public function workspaceFinder(Request $request, PlatformAffiliateService $affiliateService): View|RedirectResponse
    {
        $affiliateService->captureFromRequest($request);

        if (auth()->check()) {
            return redirect()->away($this->workspaceUrlFor($request, false));
        }

        return view('workspace-finder');
    }

    public function about(): View
    {
        return view('about');
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

    public function redirectToWorkspaceLogin(Request $request): RedirectResponse
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

        $appUrl = (string) config('app.url');
        $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?: ($request->isSecure() ? 'https' : 'http');

        return redirect()->away($scheme . '://' . $tenant->slug . '.' . config('multitenancy.saas_domain') . '/login');
    }

    private function workspaceUrlFor(Request $request, bool $appendDashboard = true): string
    {
        $user = $request->user();
        $appUrl = (string) config('app.url');
        $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?: ($request->isSecure() ? 'https' : 'http');
        $path = $appendDashboard ? '/dashboard' : '/login';

        if ($user && (int) $user->tenant_id === 1 && $user->hasRole('Super-admin')) {
            return $scheme . '://' . config('multitenancy.platform_admin_subdomain', 'dash') . '.' . config('multitenancy.saas_domain') . $path;
        }

        if ($user && $user->tenant) {
            return $scheme . '://' . $user->tenant->slug . '.' . config('multitenancy.saas_domain') . $path;
        }

        return route('workspace.finder');
    }

    /**
     * @param  array<int, string>|null  $slugs
     * @return array<int, array<string, mixed>>
     */
    private function publicModuleCatalog(?array $slugs = null): array
    {
        $catalog = [];
        $requested = $slugs ? array_fill_keys($slugs, true) : null;
        $copy = $this->publicModuleCopy();

        foreach (File::directories(app_path('Modules')) as $moduleDir) {
            $manifestPath = $moduleDir . DIRECTORY_SEPARATOR . 'module.json';
            if (!File::exists($manifestPath)) {
                continue;
            }

            $manifest = json_decode((string) File::get($manifestPath), true);
            if (!is_array($manifest) || empty($manifest['slug'])) {
                continue;
            }

            $slug = (string) $manifest['slug'];
            if ($requested !== null && !isset($requested[$slug])) {
                continue;
            }

            if (in_array($slug, ['sampledata', 'midtrans', 'email_inbox'], true)) {
                continue;
            }

            $entry = $copy[$slug] ?? null;
            if (!$entry) {
                continue;
            }

            $iconPath = $moduleDir . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'icons' . DIRECTORY_SEPARATOR . 'icon.svg';
            $catalog[$slug] = [
                'slug' => $slug,
                'name' => $entry['name'] ?? ($manifest['name'] ?? $slug),
                'eyebrow' => $entry['eyebrow'] ?? strtoupper((string) ($manifest['category'] ?? 'module')),
                'description' => $entry['description'] ?? ($manifest['description'] ?? ''),
                'public_points' => $entry['public_points'] ?? [],
                'icon_svg' => File::exists($iconPath) ? File::get($iconPath) : null,
                'category' => (string) ($entry['category'] ?? ($manifest['category'] ?? 'module')),
            ];
        }

        if ($slugs === null) {
            return array_values($catalog);
        }

        return array_values(array_filter(array_map(
            fn (string $slug) => $catalog[$slug] ?? null,
            $slugs
        )));
    }

    /**
     * @return array<int, string>
     */
    private function accountingModuleSlugs(): array
    {
        return [
            'sales',
            'payments',
            'finance',
            'reports',
            'products',
            'contacts',
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function publicModuleCopy(): array
    {
        return [
            'conversations' => [
                'name' => 'Shared Inbox',
                'eyebrow' => 'Omnichannel',
                'category' => 'omnichannel',
                'description' => 'Satukan chat dari berbagai channel dalam satu inbox tim agar balasan lebih cepat, rapi, dan mudah dipantau.',
                'public_points' => ['Inbox bersama tim', 'Distribusi percakapan', 'Riwayat customer lebih jelas'],
            ],
            'whatsapp_api' => [
                'name' => 'WhatsApp API',
                'eyebrow' => 'Channel',
                'category' => 'omnichannel',
                'description' => 'Kelola percakapan WhatsApp bisnis dari workspace yang sama dengan kontrol agent, template, dan broadcast terarah.',
                'public_points' => ['Nomor bisnis terpusat', 'Template dan blast', 'Routing agent lebih rapi'],
            ],
            'whatsapp_web' => [
                'name' => 'WhatsApp Web',
                'eyebrow' => 'Channel',
                'category' => 'omnichannel',
                'description' => 'Tambahkan ritme operasional WhatsApp yang lebih ringan untuk tim yang masih bergerak cepat dengan perangkat aktif.',
                'public_points' => ['Akses cepat untuk tim', 'Monitoring lebih mudah', 'Tetap dalam satu workspace'],
            ],
            'social_media' => [
                'name' => 'Social Inbox',
                'eyebrow' => 'Social',
                'category' => 'omnichannel',
                'description' => 'Masuk ke DM dan pesan sosial media tanpa berpindah-pindah aplikasi agar follow up tidak tercecer.',
                'public_points' => ['Instagram dan Facebook DM', 'Percakapan terpusat', 'Respon brand lebih konsisten'],
            ],
            'live_chat' => [
                'name' => 'Live Chat',
                'eyebrow' => 'Website',
                'category' => 'omnichannel',
                'description' => 'Tambahkan widget chat ke website untuk menangkap pertanyaan, lead, dan permintaan bantuan langsung dari pengunjung.',
                'public_points' => ['Widget web siap pakai', 'Lead masuk lebih cepat', 'Terhubung ke inbox tim'],
            ],
            'chatbot' => [
                'name' => 'Chatbot AI',
                'eyebrow' => 'Automation',
                'category' => 'omnichannel',
                'description' => 'Otomatisasi jawaban awal dan alur bantuan dasar agar tim fokus ke percakapan bernilai lebih tinggi.',
                'public_points' => ['Jawaban otomatis awal', 'Operasional 24 jam', 'Bisa dipadukan dengan agent'],
            ],
            'contacts' => [
                'name' => 'Contacts',
                'eyebrow' => 'Data',
                'category' => 'support',
                'description' => 'Simpan data customer, supplier, dan PIC dalam satu tempat agar transaksi dan follow up tetap nyambung.',
                'public_points' => ['Master kontak terpusat', 'Dipakai lintas modul', 'Riwayat relasi lebih jelas'],
            ],
            'crm' => [
                'name' => 'CRM',
                'eyebrow' => 'Pipeline',
                'category' => 'crm',
                'description' => 'Kelola follow up lead dan peluang penjualan dengan pipeline yang mudah dibaca oleh tim sales.',
                'public_points' => ['Pipeline lead', 'Follow up lebih terukur', 'Aktivitas sales lebih terlihat'],
            ],
            'email_marketing' => [
                'name' => 'Email Marketing',
                'eyebrow' => 'Campaign',
                'category' => 'crm',
                'description' => 'Jalankan kampanye email dari data kontak yang sama untuk nurture, promo, dan follow up berkala.',
                'public_points' => ['Campaign dari contact list', 'Targeting lebih rapi', 'Satu data dengan CRM'],
            ],
            'sales' => [
                'name' => 'Sales',
                'eyebrow' => 'Accounting',
                'category' => 'accounting',
                'description' => 'Kelola transaksi penjualan harian dengan alur yang rapi, dari draft sampai finalize dan retur.',
                'public_points' => ['Order dan invoice operasional', 'Retur penjualan', 'Alur transaksi lebih tertib'],
            ],
            'payments' => [
                'name' => 'Payments',
                'eyebrow' => 'Accounting',
                'category' => 'accounting',
                'description' => 'Catat pembayaran masuk dan keluar dengan lebih jelas agar status transaksi tidak tertukar.',
                'public_points' => ['Pembayaran customer', 'Alokasi pembayaran', 'Monitoring status lebih mudah'],
            ],
            'purchases' => [
                'name' => 'Purchases',
                'eyebrow' => 'Accounting',
                'category' => 'accounting',
                'description' => 'Atur pembelian supplier, receiving, dan ritme pengadaan agar operasional lebih terkendali.',
                'public_points' => ['Pembelian supplier', 'Receiving barang', 'Kontrol belanja operasional'],
            ],
            'finance' => [
                'name' => 'Finance',
                'eyebrow' => 'Accounting',
                'category' => 'accounting',
                'description' => 'Pantau kas masuk dan kas keluar operasional untuk kebutuhan pencatatan keuangan harian yang ringan.',
                'public_points' => ['Cash in dan cash out', 'Kategori transaksi', 'Ringkasan arus uang'],
            ],
            'point-of-sale' => [
                'name' => 'Point of Sale',
                'eyebrow' => 'Outlet',
                'category' => 'accounting',
                'description' => 'Jalankan kasir outlet dari workspace yang sama agar transaksi toko langsung terhubung ke data penjualan.',
                'public_points' => ['Checkout kasir', 'Shift kas', 'Cocok untuk outlet dan toko'],
            ],
            'reports' => [
                'name' => 'Reports',
                'eyebrow' => 'Insight',
                'category' => 'accounting',
                'description' => 'Baca performa operasional dan ringkasan transaksi lebih cepat tanpa menunggu rekap manual.',
                'public_points' => ['Ringkasan performa', 'Baca data lebih cepat', 'Mendukung keputusan harian'],
            ],
            'products' => [
                'name' => 'Products',
                'eyebrow' => 'Master Data',
                'category' => 'operations',
                'description' => 'Kelola katalog produk dan varian agar penjualan, pembelian, dan POS memakai data yang sama.',
                'public_points' => ['Produk dan varian', 'Harga lebih terpusat', 'Dipakai lintas transaksi'],
            ],
            'inventory' => [
                'name' => 'Inventory',
                'eyebrow' => 'Stock',
                'category' => 'operations',
                'description' => 'Pantau stok dan lokasi penyimpanan agar alur barang tetap sinkron dengan penjualan dan pembelian.',
                'public_points' => ['Kontrol stok', 'Lokasi inventory', 'Sinkron dengan transaksi'],
            ],
            'discounts' => [
                'name' => 'Discounts',
                'eyebrow' => 'Pricing',
                'category' => 'operations',
                'description' => 'Atur promosi dan diskon agar tim outlet atau sales bisa menjalankan penawaran dengan lebih konsisten.',
                'public_points' => ['Aturan promo', 'Voucher dan diskon', 'Lebih rapi saat checkout'],
            ],
            'task_management' => [
                'name' => 'Task Management',
                'eyebrow' => 'Execution',
                'category' => 'operations',
                'description' => 'Jaga ritme kerja tim internal dengan daftar tugas, progres, dan pembagian tanggung jawab yang lebih jelas.',
                'public_points' => ['Task list tim', 'Progress kerja', 'Koordinasi lebih ringan'],
            ],
            'shortlink' => [
                'name' => 'Shortlink',
                'eyebrow' => 'Campaign',
                'category' => 'operations',
                'description' => 'Buat link pendek yang lebih rapi untuk kebutuhan kampanye, distribusi, dan tracking sederhana.',
                'public_points' => ['Link lebih ringkas', 'Lebih mudah dibagikan', 'Cocok untuk kampanye cepat'],
            ],
        ];
    }
}
