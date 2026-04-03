@extends('layouts.landing')

@section('head_title', 'Syarat & Ketentuan — ' . config('app.name'))
@section('head_description', 'Syarat dan ketentuan penggunaan layanan ' . config('app.name') . '.')

@section('content')

{{-- ══ HERO ════════════════════════════════════════════════ --}}
<section class="py-5" style="background: linear-gradient(135deg, #f8fafc 0%, #eef1ff 100%); border-bottom: 1px solid var(--landing-line);">
    <div class="container py-3">
        <div class="legal-page-header">
            <div class="landing-badge mb-3"><i class="ti ti-file-description"></i> Dokumen Legal</div>
            <h1 class="landing-headline mb-3">Syarat &amp; Ketentuan</h1>
            <p class="landing-subtext mb-3">Terakhir diperbarui: <strong>{{ date('d F Y') }}</strong></p>
            <p class="landing-subtext mb-0">Dokumen ini mengatur hubungan antara Anda sebagai pengguna dan {{ config('app.name') }} sebagai penyedia layanan. Harap baca dengan seksama sebelum menggunakan platform.</p>
        </div>
    </div>
</section>

{{-- ══ CONTENT ═════════════════════════════════════════════ --}}
<section class="py-5 py-lg-6">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                <div class="legal-toc mb-5">
                    <div class="legal-toc-title">Daftar Isi</div>
                    <ol class="legal-toc-list">
                        <li><a href="#t1">Penerimaan Syarat</a></li>
                        <li><a href="#t2">Deskripsi Layanan</a></li>
                        <li><a href="#t3">Akun & Workspace</a></li>
                        <li><a href="#t4">Langganan & Pembayaran</a></li>
                        <li><a href="#t5">Penggunaan yang Diizinkan</a></li>
                        <li><a href="#t6">Larangan Penggunaan</a></li>
                        <li><a href="#t7">Kepemilikan Data</a></li>
                        <li><a href="#t8">Hak Kekayaan Intelektual</a></li>
                        <li><a href="#t9">Penghentian Layanan</a></li>
                        <li><a href="#t10">Batasan Tanggung Jawab</a></li>
                        <li><a href="#t11">Perubahan Layanan & Harga</a></li>
                        <li><a href="#t12">Hukum yang Berlaku</a></li>
                        <li><a href="#t13">Hubungi Kami</a></li>
                    </ol>
                </div>

                <div class="legal-body">

                    <div class="legal-intro mb-5">
                        <p>Dengan mendaftar, mengakses, atau menggunakan layanan {{ config('app.name') }}, Anda menyatakan telah membaca, memahami, dan menyetujui syarat dan ketentuan ini. Jika Anda tidak menyetujui, harap tidak menggunakan layanan kami.</p>
                        <p class="mb-0">Syarat dan ketentuan ini berlaku untuk semua pengguna platform, termasuk pemilik workspace, administrator, dan anggota tim yang diundang.</p>
                    </div>

                    <div id="t1" class="legal-section">
                        <h2><span class="legal-section-num">1</span> Penerimaan Syarat</h2>
                        <p>Dengan membuat akun atau menggunakan platform {{ config('app.name') }}, Anda menyetujui syarat dan ketentuan ini serta Kebijakan Privasi kami. Jika Anda mewakili sebuah bisnis atau organisasi, Anda menyatakan memiliki wewenang untuk mengikat entitas tersebut pada syarat ini.</p>
                    </div>

                    <div id="t2" class="legal-section">
                        <h2><span class="legal-section-num">2</span> Deskripsi Layanan</h2>
                        <p>{{ config('app.name') }} adalah platform omnichannel berbasis cloud yang memungkinkan bisnis mengelola percakapan pelanggan dari berbagai channel (WhatsApp, sosial media, live chat) dalam satu workspace terpadu.</p>
                        <p>Layanan disediakan berdasarkan model berlangganan (SaaS). Fitur yang tersedia bergantung pada paket yang dipilih. Kami berhak menambah, mengubah, atau menghentikan fitur tertentu dengan pemberitahuan yang wajar.</p>
                    </div>

                    <div id="t3" class="legal-section">
                        <h2><span class="legal-section-num">3</span> Akun &amp; Workspace</h2>
                        <ul>
                            <li>Setiap workspace memiliki subdomain unik yang dipilih saat pendaftaran. Subdomain tidak dapat diubah setelah aktif.</li>
                            <li>Anda bertanggung jawab menjaga keamanan dan kerahasiaan kredensial akun Anda.</li>
                            <li>Anda bertanggung jawab atas seluruh aktivitas yang terjadi di bawah akun Anda.</li>
                            <li>Segera hubungi kami jika Anda menduga akun Anda diakses tanpa izin.</li>
                            <li>Satu workspace hanya boleh digunakan oleh satu entitas bisnis. Penggunaan untuk keperluan reseller atau multi-klien tanpa izin tidak diperbolehkan.</li>
                        </ul>
                    </div>

                    <div id="t4" class="legal-section">
                        <h2><span class="legal-section-num">4</span> Langganan &amp; Pembayaran</h2>
                        <ul>
                            <li>Layanan aktif setelah pembayaran dikonfirmasi oleh sistem. Tidak ada aktivasi manual.</li>
                            <li>Semua harga yang tercantum belum termasuk PPN sesuai peraturan perpajakan yang berlaku.</li>
                            <li>Pembayaran dilakukan di muka untuk periode yang dipilih (bulanan, 6 bulanan, atau tahunan).</li>
                            <li>Langganan tidak diperpanjang otomatis — Anda perlu melakukan pembayaran baru untuk melanjutkan layanan.</li>
                            <li>Tidak ada pengembalian dana (refund) untuk periode yang telah berjalan, kecuali dalam kondisi yang ditentukan lebih lanjut oleh tim kami.</li>
                            <li>Kami berhak mengubah harga dengan pemberitahuan minimal 30 hari sebelumnya.</li>
                        </ul>
                        <div class="legal-callout legal-callout-blue">
                            <i class="ti ti-info-circle"></i>
                            <div>Jika ada pertanyaan tentang tagihan atau pembayaran, hubungi kami melalui <a href="mailto:support@meetra.id">support@meetra.id</a> sebelum melakukan dispute ke bank atau payment gateway.</div>
                        </div>
                    </div>

                    <div id="t5" class="legal-section">
                        <h2><span class="legal-section-num">5</span> Penggunaan yang Diizinkan</h2>
                        <p>Anda diizinkan menggunakan platform untuk:</p>
                        <ul>
                            <li>Mengelola percakapan pelanggan bisnis Anda secara sah</li>
                            <li>Menghubungkan channel komunikasi resmi bisnis Anda</li>
                            <li>Mengonfigurasi chatbot dan otomasi untuk keperluan bisnis yang wajar</li>
                            <li>Mengundang anggota tim sesuai kuota paket yang dipilih</li>
                        </ul>
                    </div>

                    <div id="t6" class="legal-section">
                        <h2><span class="legal-section-num">6</span> Larangan Penggunaan</h2>
                        <p>Anda dilarang menggunakan platform untuk:</p>
                        <ul>
                            <li>Mengirim spam, pesan massal tidak diminta, atau melakukan penipuan</li>
                            <li>Menyebarkan konten ilegal, melanggar hak cipta, atau bertentangan dengan hukum Indonesia</li>
                            <li>Melakukan percobaan akses tidak sah ke sistem atau workspace lain</li>
                            <li>Menjual kembali akses layanan kepada pihak ketiga tanpa izin tertulis dari kami</li>
                            <li>Menggunakan platform untuk kegiatan yang melanggar ketentuan WhatsApp Business API atau platform pihak ketiga lainnya yang terhubung</li>
                            <li>Melakukan tindakan yang membebani atau mengganggu infrastruktur platform secara tidak wajar</li>
                        </ul>
                        <p>Pelanggaran ketentuan ini dapat mengakibatkan penangguhan atau pemutusan layanan tanpa pengembalian dana.</p>
                    </div>

                    <div id="t7" class="legal-section">
                        <h2><span class="legal-section-num">7</span> Kepemilikan Data</h2>
                        <p>Data percakapan, kontak, dan informasi bisnis yang Anda masukkan ke dalam platform adalah milik Anda sepenuhnya. Kami tidak mengklaim kepemilikan atas data tersebut.</p>
                        <p>Dengan menggunakan layanan, Anda memberikan kami lisensi terbatas untuk menyimpan dan memproses data tersebut semata-mata untuk keperluan penyediaan layanan kepada Anda.</p>
                        <p>Anda dapat mengekspor atau meminta penghapusan data Anda kapan saja sesuai ketentuan pada <a href="{{ route('privacy') }}">Kebijakan Privasi</a>.</p>
                    </div>

                    <div id="t8" class="legal-section">
                        <h2><span class="legal-section-num">8</span> Hak Kekayaan Intelektual</h2>
                        <p>Seluruh elemen platform {{ config('app.name') }} — termasuk antarmuka, desain, kode, merek dagang, dan dokumentasi — adalah milik kami dan dilindungi oleh hukum hak cipta yang berlaku.</p>
                        <p>Anda tidak diizinkan menyalin, memodifikasi, mendistribusikan, atau membuat karya turunan dari platform tanpa izin tertulis dari kami.</p>
                    </div>

                    <div id="t9" class="legal-section">
                        <h2><span class="legal-section-num">9</span> Penghentian Layanan</h2>
                        <h3>Penghentian oleh Anda</h3>
                        <p>Anda dapat menghentikan langganan kapan saja. Layanan tetap aktif hingga akhir periode yang telah dibayar. Setelah itu, workspace akan dinonaktifkan dan data akan disimpan selama masa grace period sebelum dihapus permanen.</p>
                        <h3>Penghentian oleh Kami</h3>
                        <p>Kami berhak menangguhkan atau menghentikan akses Anda jika:</p>
                        <ul>
                            <li>Terjadi pelanggaran terhadap syarat dan ketentuan ini</li>
                            <li>Terdapat aktivitas yang berpotensi merugikan platform atau pengguna lain</li>
                            <li>Diwajibkan oleh peraturan hukum yang berlaku</li>
                        </ul>
                        <p>Dalam hal penghentian karena pelanggaran, tidak ada pengembalian dana untuk sisa periode yang belum digunakan.</p>
                    </div>

                    <div id="t10" class="legal-section">
                        <h2><span class="legal-section-num">10</span> Batasan Tanggung Jawab</h2>
                        <p>Layanan kami disediakan "sebagaimana adanya". Meskipun kami berusaha menjaga uptime dan keandalan platform, kami tidak dapat menjamin layanan bebas dari gangguan atau error sepenuhnya.</p>
                        <p>Tanggung jawab kami terbatas pada nilai langganan yang Anda bayarkan dalam 3 bulan terakhir. Kami tidak bertanggung jawab atas kerugian tidak langsung, kehilangan keuntungan, atau gangguan bisnis yang timbul dari penggunaan layanan.</p>
                        <div class="legal-callout legal-callout-yellow">
                            <i class="ti ti-alert-triangle"></i>
                            <div>Koneksi ke layanan pihak ketiga (WhatsApp API, sosial media, provider AI) bergantung pada ketersediaan layanan pihak tersebut di luar kendali kami.</div>
                        </div>
                    </div>

                    <div id="t11" class="legal-section">
                        <h2><span class="legal-section-num">11</span> Perubahan Layanan &amp; Harga</h2>
                        <p>Kami berhak mengubah fitur, paket, atau harga layanan. Perubahan signifikan akan dikomunikasikan minimal 30 hari sebelumnya melalui email atau notifikasi di dalam platform.</p>
                        <p>Perubahan kecil seperti penambahan fitur baru atau perbaikan dapat dilakukan tanpa pemberitahuan sebelumnya.</p>
                    </div>

                    <div id="t12" class="legal-section">
                        <h2><span class="legal-section-num">12</span> Hukum yang Berlaku</h2>
                        <p>Syarat dan ketentuan ini diatur oleh dan ditafsirkan sesuai dengan hukum yang berlaku di Republik Indonesia. Setiap sengketa yang timbul akan diselesaikan melalui musyawarah terlebih dahulu, dan jika tidak tercapai kesepakatan, akan diselesaikan melalui jalur hukum yang berlaku di Indonesia.</p>
                    </div>

                    <div id="t13" class="legal-section">
                        <h2><span class="legal-section-num">13</span> Hubungi Kami</h2>
                        <p>Jika ada pertanyaan tentang syarat dan ketentuan ini, hubungi kami:</p>
                        <div class="legal-contact-grid">
                            <a href="mailto:support@meetra.id" class="legal-contact-item">
                                <i class="ti ti-mail"></i>
                                <div><div class="fw-semibold">Email</div><div class="text-muted">support@meetra.id</div></div>
                            </a>
                            <a href="https://wa.me/6281222229815" target="_blank" rel="noopener" class="legal-contact-item">
                                <i class="ti ti-brand-whatsapp"></i>
                                <div><div class="fw-semibold">WhatsApp</div><div class="text-muted">+62 812-222-9815</div></div>
                            </a>
                        </div>
                    </div>

                </div>{{-- .legal-body --}}
            </div>
        </div>
    </div>
</section>

@endsection
