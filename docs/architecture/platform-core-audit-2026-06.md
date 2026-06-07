# Platform Core Audit & Long-Term SaaS Readiness Review

Tanggal audit: 2026-06-06

Status implementasi terakhir: 2026-06-07

Scope audit ini dibatasi ke Platform Core. Audit ini sengaja tidak menilai kualitas fitur bisnis seperti CRM, accounting, commerce, HR, atau modul operasional lain sebagai produk akhir. Fokusnya adalah apakah fondasi SaaS cukup kuat untuk menopang pertumbuhan 3-10 tahun tanpa refactor besar yang tidak perlu.

## Ringkasan Eksekutif

Fondasi Platform Core sudah lebih matang dari aplikasi Laravel modular biasa. Tenancy context, plan gating, notification center, file ownership, topology registry, dan module registry sudah ada dan sebagian besar menuju arah yang benar.

Masalah utamanya bukan kekurangan fitur dasar, tetapi ketidakseimbangan investasi arsitektur:

- Beberapa area advanced seperti tenant topology registry sudah cukup jauh.
- Beberapa fondasi universal yang jauh lebih cepat menjadi bottleneck justru masih lemah atau belum ada, terutama audit log, API platform, global search, event contract lintas modul, dan activity foundation yang benar-benar konsisten.
- Core sudah mulai bocor ke domain module. Ini adalah risiko refactor terbesar saat ini.

Kesimpulan tegas:

- Platform ini cukup layak untuk fase growth awal.
- Platform ini belum aman disebut fondasi jangka panjang 5-10 tahun tanpa disiplin refactor arsitektur dalam waktu dekat.
- Risiko terbesar bukan pada multi-tenant dasar, tetapi pada boundary core-vs-module dan belum adanya beberapa primitive platform yang benar-benar generik.

## Metode Audit

Audit ini berbasis:

- pembacaan implementasi aktual di codebase
- pembacaan dokumentasi arsitektur dan tenancy
- verifikasi command audit internal project

Temuan runtime yang berhasil diverifikasi:

- `tenant:query-readiness-audit`: lulus
- `tenant:health-check`: lulus setelah registry topology central dilengkapi
- `modules:audit-boundaries`: lulus tanpa pelanggaran baru
- `modules:audit-boundaries`: `Approved transitional references: 0`
- migration compatibility lama yang masih ditoleransi: 2 touch terhadap tabel module pada migration historis yang sudah dibekukan di audit config

## Addendum Implementasi 2026-06-07

Sejak audit awal, beberapa fondasi yang sebelumnya hanya menjadi rekomendasi sudah dipasang sebagai baseline implementasi:

- policy boundary `core vs module` sekarang diaudit aktif lewat CI/command
- tenancy readiness untuk topology central sekarang lulus di environment dev utama
- entitlement runtime sekarang sudah punya snapshot/service core tersendiri
- audit log, activity foundation, event outbox, webhook receipt, API v1 baseline, search foundation, notification policy layer, dan file post-processing pipeline sudah masuk ke core sebagai primitive generik

Perubahan paling penting untuk temuan audit awal:

- coupling `core -> module` yang tadinya tercatat 46 referensi transisional sekarang sudah dibersihkan dari area core yang diaudit
- approved transitional reference sekarang `0`
- boundary audit berubah fungsi dari sekadar pemetaan masalah menjadi guardrail aktif agar coupling baru tidak masuk diam-diam

Contoh refactor boundary yang sudah dilakukan:

- `App\Support\AccountingSourceReferenceService`
  - sebelumnya: import langsung model module `Finance`, `Payments`, `Sales`, `Purchases`, `Inventory`
  - sekarang: membaca registry source reference dari `config/platform-core.php`
- `App\Support\Commerce\CommerceOrderLifecycleService`
  - sebelumnya: inject `FinalizeSaleAction` dari module `Sales`
  - sekarang: bergantung pada contract `App\Contracts\CommerceDraftFinalizer`
- payment/shipping provider driver di core
  - sebelumnya: langsung inject service vendor milik module owner
  - sekarang: bergantung pada contract core, lalu module owner bind adapter implementasinya
- `App\Support\ModeAwarePayloadSanitizer`, `App\Support\TenantPlanManager`, `App\Multitenancy\TenantOwnershipManifest`, `App\Services\PlatformMidtransBillingService`
  - sebelumnya: mengunci class module langsung di service core
  - sekarang: memakai config string atau contract agar owner tetap di module

## 1. Platform Core Inventory

### Sudah Baik

- Authentication, user shell, profile, settings, dan module registry sudah jelas diposisikan sebagai core.
- Tenant context, company context, dan branch context sudah ada sebagai resolver runtime terpisah.
- Plan, feature flag, dan usage limit foundation sudah cukup solid untuk monetisasi awal.
- Notification center sudah dipisahkan sebagai shared subsystem, bukan ditempel ke satu modul bisnis.
- File ownership, access log, dan storage abstraction bergerak ke model private-first yang benar.
- Queue sudah membawa tenant topology snapshot dan tervalidasi saat worker memproses job.
- Query hardening untuk tenancy sudah lebih matang dari rata-rata aplikasi SaaS tahap awal.

### Missing

- Dedicated audit log yang terpisah dari activity log
- Global activity foundation yang konsisten lintas modul
- API platform contract yang formal
- Global search foundation
- Event contract lintas modul yang stabil
- Generic webhook governance contract
- Archive and retention architecture untuk tabel growth-heavy
- Generic custom field platform
- Generic tagging platform

### Perlu Refactor

- Core bergantung langsung ke class module di banyak tempat
- Migration core masih menyentuh tabel milik module
- Activity timeline tersebar di beberapa pola penyimpanan
- Search masih dominan per-module, bukan platform primitive
- Reporting masih terlalu dekat ke query OLTP

### Overengineered

- Tenant topology registry untuk schema/database isolation relatif lebih maju dibanding fondasi universal lain yang lebih mendesak
- Runtime tenancy future-ready sudah banyak desain, tetapi operational readiness environment aktif belum sepenuhnya mengikuti

### Underengineered

- Audit/compliance log
- API consistency
- Global eventing
- Search strategy platform-level
- Customer 360 timeline foundation
- Archival strategy untuk tabel log-heavy

## 2. Multi Tenant Readiness

### Kondisi Saat Ini

Yang sudah benar:

- Tenant context adalah konsep runtime eksplisit
- Ownership manifest sudah mulai dibekukan
- Queue payload sudah membawa snapshot topology
- Registry untuk server, database, runtime, dan storage sudah ada
- Provisioning service tenant sudah ada

Yang belum matang:

- Suspension strategy belum terlihat sebagai lifecycle formal end-to-end
- Deletion strategy belum terlihat sebagai policy formal antara soft delete, archive, purge, dan legal retention
- Migration/move readiness belum bisa dianggap aman karena health-check gagal di environment aktif
- Tenant feature activation masih campuran antara module activation, plan feature, dan logic UI/runtime

### Kesiapan Skala

#### 100 tenant

Layak, dengan catatan:

- query discipline tetap dijaga
- reporting berat tidak terlalu dominan
- modul growth-heavy belum terlalu masif

#### 1.000 tenant

Masih mungkin, tetapi mulai berisiko tanpa:

- reporting offload
- retention policy
- search strategy yang konsisten
- observability untuk tabel log-heavy
- kontrol lebih kuat terhadap coupling core-vs-module

#### 10.000 tenant

Belum siap. Bukan karena `tenant_id` shared model selalu salah, tetapi karena fondasi pendukungnya belum cukup:

- belum ada partition strategy nyata
- belum ada archive strategy yang jelas
- belum ada analytics separation
- schema/database isolation belum terbukti operasional
- API, event, search, dan audit log belum cukup formal

### Risiko Multi Tenant

- Risiko data growth lebih cepat datang dari log, webhook, notification, activity, dan report query daripada dari tabel master
- Kegagalan health-check topology menunjukkan adanya gap antara target architecture dan environment reality
- Bila tenant move/schema isolation dijalankan sebelum seluruh query dan raw access benar-benar steril, refactor-nya akan mahal

## 3. RBAC & Access Control

### Yang Sudah Baik

- Spatie Permission teams dengan `tenant_id` adalah arah yang benar
- Role tenant-scoped sudah berjalan
- Company dan branch access sudah mulai dimodelkan eksplisit lewat tabel akses user

### Masalah

- Model akses berhenti di tenant, company, branch
- Belum ada abstraction yang rapi untuk department, team, division, cost center, atau ownership scope lain
- Role architecture masih cocok untuk tenant kecil-menengah, belum untuk tenant enterprise dengan struktur matriks

### Potensi Refactor Sulit

- Jika nanti department/team ditambah langsung ke role atau permission tanpa abstraction access scope, refactor akan sulit
- Jika ownership data di masa depan dicampur ke role layer, kebijakan akses akan meledak kompleks

### Rekomendasi

- Pisahkan dengan tegas:
  - role/permission = capability
  - access scope = batas data yang boleh dilihat/diubah
- Siapkan desain `access scope assignment` generik sekarang, meski implementasi department/team bisa ditunda
- Hindari menambah kolom ad hoc seperti `department_id` ke banyak tabel akses sebelum model scope dibakukan

## 4. Subscription & Feature Access

### Yang Sudah Baik

- Plan, feature, limit, dan override sudah diposisikan sebagai core
- Product-line-aware subscription sudah menunjukkan arah packaging yang fleksibel
- Over-limit policy cukup jelas

### Masalah

- Entitlement model masih sangat dekat ke runtime code, belum terasa sebagai kontrak platform yang benar-benar formal
- Module activation, plan feature, dan UX visibility berpotensi tumpang tindih bila tidak terus dibersihkan
- Belum terlihat contract yang kuat untuk grace period, downgrade policy, suspension by billing, dan entitlement state transition

### Risiko Jangka Menengah

- Monetisasi akan mulai menghambat pengembangan bila aturan feature access tersebar ke controller, Blade, job, dan service tanpa registry policy yang konsisten

### Rekomendasi

- Bekukan satu sumber kebenaran entitlement
- Pisahkan jelas:
  - installed module
  - active module
  - entitled feature
  - usage limit
  - billing state

## 5. Notification Foundation

### Yang Sudah Baik

- Notifications diposisikan sebagai core
- Tabel notifikasi, recipient, delivery, preference, push subscription sudah rapi
- Dedupe, severity, recipient scope, dan delivery log sudah dipikirkan

### Masalah

- Runtime delivery nyata baru terlihat mapan untuk `email` dan `web_push`
- WhatsApp notification readiness belum tampak sebagai channel contract yang benar-benar plug-in
- Belum terlihat strategy untuk notification fan-out besar, digest, escalation, atau noisy-event suppression

### Kesimpulan

Fondasinya cukup baik untuk early stage. Belum cukup matang untuk jadi channel platform jangka panjang tanpa layer policy tambahan.

## 6. Audit Log & Activity Foundation

### Temuan

Saat ini ada beberapa pola berbeda:

- `activity_log` dari Spatie
- `conversation_activity_logs`
- timeline JSON pada `sales.meta.commerce`
- event log tertentu seperti `tenant_domain_events`

Ini bukan fondasi activity platform yang seragam.

### Risiko

- Customer 360 akan sulit dibangun konsisten
- Business timeline lintas modul akan mahal jika setiap modul menyimpan histori sendiri-sendiri
- Audit log dan activity log berisiko tercampur secara konsep

### Yang Missing

- immutable audit log untuk perubahan sensitif
- actor tracking yang konsisten
- change diff yang konsisten
- impersonation tracking
- security event stream
- generic entity timeline model

### Penilaian

Ini salah satu gap terpenting saat ini. Bukan karena semua harus dibangun sekarang, tetapi karena kalau dibiarkan tiap modul menyimpan timeline sendiri, refactor 2-3 tahun lagi akan besar.

## 7. Event System

### Yang Sudah Ada

- Laravel event/listener dipakai di beberapa modul
- Hook manager dipakai untuk beberapa event bisnis
- Queue jobs sudah banyak dipakai untuk async work

### Masalah

- Belum ada event contract lintas modul yang terasa dibakukan
- Naming belum terlihat dikelola lewat catalog platform
- Publishing dan consumption masih campuran antara Laravel event, hook string, dan direct orchestration
- Belum terlihat outbox pattern untuk event penting

### Risiko

- Modul akan berkomunikasi dengan cara yang tidak konsisten
- Cross-module automation atau integration akan sulit distabilkan
- Idempotency dan replay akan menjadi masalah saat volume naik

### Rekomendasi

- Definisikan event taxonomy sekarang
- Pisahkan:
  - domain events internal modul
  - platform integration events
  - UI hooks
- Siapkan outbox pattern untuk event penting yang men-trigger proses lintas modul

## 8. API & Webhook Platform

### API

Temuan tegas:

- API platform praktis belum ada
- `routes/api.php` masih stub default Sanctum/broadcast

Ini berarti platform belum punya kontrak resmi untuk:

- versioning
- filtering grammar
- pagination standard
- response envelope
- idempotency
- API error contract

### Webhook

Yang sudah baik:

- Banyak webhook provider diletakkan di modul owner masing-masing
- Signature validation sudah ada di beberapa integrasi

Yang kurang:

- Belum terlihat webhook platform contract yang seragam
- Belum ada standard event receipt, replay, dedupe, retention, dan failure recovery yang benar-benar generik

### Kesimpulan

Webhook isolation per module sudah benar. API platform masih underbuilt.

## 9. File Management

### Yang Sudah Baik

- Ownership metadata cukup lengkap
- Tenant/company/branch scope ada
- Access log sudah ada
- Storage abstraction sudah mulai memisahkan control-plane dan operational file records
- Private-first storage model adalah keputusan yang benar

### Yang Kurang

- Virus scanning readiness belum tampak sebagai pipeline nyata
- Media processing readiness belum tampak sebagai abstraction nyata
- Retention/legal hold policy belum formal
- Download/share policy jangka panjang belum dibakukan sebagai capability model

### Kesimpulan

Ini salah satu area core yang paling sehat saat ini.

## 10. Search Foundation

### Temuan

- Search masih dominan per-module
- Ada beberapa fulltext index per tabel, tetapi belum menjadi strategi platform-level
- Belum ada global search contract

### Risiko

- Setiap modul akan membangun search dengan pola sendiri
- Ranking, relevancy, dan filter behavior akan tidak konsisten
- PostgreSQL bottleneck akan muncul ketika pencarian lintas entity mulai dibutuhkan

### Rekomendasi PostgreSQL

- Gunakan strategi campuran:
  - `tsvector + GIN` untuk fulltext formal
  - `pg_trgm` untuk prefix/fuzzy lookup tertentu
  - search document/index table untuk global search lintas modul
- Jangan jadikan `LIKE '%...%'` sebagai pola utama jangka panjang

## 11. Database Readiness

### Yang Sudah Baik

- PostgreSQL dijadikan target utama secara eksplisit
- Beberapa indeks dan scope tenant-aware sudah mulai serius
- Ada kesadaran terhadap query hotspots dan raw-query readiness audit

### Masalah

- Belum terlihat partition strategy nyata untuk tabel growth-heavy
- Archive strategy belum formal
- Soft delete strategy belum tampak sebagai policy lintas domain
- Audit growth strategy belum matang
- Reporting masih terlalu dekat ke tabel transaksi langsung

### Tabel yang Berpotensi Cepat Membesar

- notifications
- notification_deliveries
- stored_file_access_logs
- activity_log
- conversation_activity_logs
- webhook event tables
- tenant domain event/history tables

### Risiko PostgreSQL

- index bloat
- hot table contention
- slow dashboard/report query
- vacuum pressure pada tabel log-heavy

## 12. Temuan Utama

1. Multi-tenant foundation sudah cukup baik untuk fase awal, tetapi belum didukung penuh oleh operational readiness environment.
2. Boundary core-vs-module adalah risiko arsitektur terbesar saat ini.
3. Subscription dan feature access foundation cukup kuat, tetapi perlu kontrak entitlement yang lebih formal.
4. Notification dan file subsystem termasuk area terbaik di core saat ini.
5. Audit log, activity foundation, API platform, search foundation, dan event contract masih tertinggal.
6. Topology future-readiness cukup maju, tetapi beberapa fondasi yang lebih cepat menjadi bottleneck justru belum matang.

## 13. Risiko

### Critical

- Historis kebocoran `core -> module` sudah dibersihkan dari area core yang diaudit, tetapi boundary policy tetap harus dijaga agar tidak regress
- Activity dan audit foundation belum seragam
- API platform belum dibakukan

### High

- Health-check topology gagal di environment aktif
- Event system belum punya kontrak lintas modul yang stabil
- Search foundation belum ada
- Reporting akan membebani OLTP saat tenant tumbuh

### Medium

- RBAC belum siap untuk organisasi kompleks
- Notification channel policy belum matang
- File pipeline belum siap untuk scanning/media processing

## 14. Technical Debt

- 0 approved transitional dependency `core -> module` di area core yang diaudit per 2026-06-07
- 2 migration historis core masih menyentuh tabel milik module dan masih dibekukan sebagai compatibility debt
- timeline/activity tersebar di beberapa storage model
- search pattern tidak seragam
- webhook handling bagus per modul, tetapi belum punya contract platform
- topology registry lebih maju daripada kesiapan operasionalnya

## 15. Missing Architecture

- dedicated audit log
- entity timeline foundation
- global search index
- API contract v1
- webhook governance contract
- event catalog + outbox
- archive/retention architecture
- generic custom field platform
- generic tagging platform

## 16. Refactor Risk

### Tinggi

- menjaga boundary tetap bersih setelah cleanup awal
- Activity foundation
- Search foundation
- Eventing pattern

### Menengah

- Org-scope RBAC
- Entitlement consistency
- Reporting foundation

## 17. Best Practice Recommendation

- Bekukan boundary core dan larang dependency baru core ke domain module
- Pindahkan schema ownership dan business coupling kembali ke module owner
- Bangun audit log terpisah dari activity log
- Definisikan event taxonomy dan outbox pattern
- Standarkan API v1 sebelum integrasi publik makin banyak
- Bangun search platform primitive sebelum setiap modul membuat pola sendiri
- Siapkan retention, archive, dan partition plan untuk tabel log-heavy
- Jadikan reporting summary/materialized layer sebagai roadmap resmi, bukan optimasi nanti

## 18. Prioritas Implementasi

### Critical

- Perbaiki environment readiness untuk topology central
- Hentikan kebocoran core-vs-module
- Definisikan audit log foundation
- Definisikan API platform contract

### High Priority

- Definisikan event contract lintas modul
- Bangun global activity/timeline foundation
- Bangun global search foundation
- Rapikan entitlement source of truth

### Medium Priority

- Siapkan archive dan partition roadmap
- Siapkan RBAC scope abstraction untuk department/team
- Tambah notification policy layer
- Tambah file scanning/media pipeline abstraction

### Future Ready

- selective schema/database isolation untuk tenant tertentu
- analytics/read-model terpisah
- enterprise-grade org hierarchy
- legal hold dan compliance retention

## 19. Core vs Module Classification

### Wajib Platform Core

- Authentication
- Tenant management
- User management
- Role & permission
- Subscription, billing, plan, feature access
- Settings, profile
- Notification center foundation
- Audit log foundation
- Activity/timeline foundation generik
- Queue foundation
- Event infrastructure generik
- API infrastructure
- Webhook infrastructure generik
- Storage infrastructure
- Search infrastructure generik

### Lebih Tepat Menjadi Module

- Automation
  - jenis: Automation Module
  - alasan: automation adalah kapabilitas tenant-facing yang bisa diaktifkan, dimonetisasi, dan dibatasi plan
- Custom Fields
  - jenis: Utility Module
  - alasan: reusable capability lintas domain, tetapi bukan syarat aplikasi SaaS dasar tetap hidup
- Tags
  - jenis: Utility Module
  - alasan: reusable metadata layer tenant-facing, bukan fondasi platform minimum
- Reports
  - jenis: Business Module atau Utility Module tergantung cakupan
  - alasan: dashboard/report tenant-facing bukan core minimum, meski reporting infrastructure perlu primitive core tertentu
- Vendor integrations seperti payment gateway/shipping/social/WA
  - jenis: Integration Module
  - alasan: optional, monetizable, tenant-activated

Catatan penting:

Sesuatu tidak otomatis menjadi core hanya karena dipakai banyak modul. Yang menjadi core adalah primitive yang dibutuhkan agar seluruh platform bisa hidup secara konsisten.

## 20. Roadmap Perbaikan

### Fase 1

- rapikan boundary core-vs-module
- benahi health readiness topology
- definisikan audit log dan API contract

Status:

- boundary cleanup baseline: selesai
- topology health readiness baseline: selesai
- audit log dan API contract baseline: sudah terpasang, masih perlu perluasan coverage

### Fase 2

- bangun event contract, activity foundation, dan search primitive
- rapikan entitlement consistency

### Fase 3

- siapkan retention, archive, dan reporting read-model
- siapkan scope abstraction untuk organisasi kompleks

### Fase 4

- selective isolation
- advanced compliance
- analytics architecture

## Penutup

Platform ini belum berada di kondisi buruk. Fondasi dasarnya justru di atas rata-rata untuk aplikasi SaaS modular tahap awal.

Tetapi tanpa koreksi arah dalam 1-3 bulan ke depan, risiko refactor besar akan datang dari area yang saat ini terlihat “masih bisa jalan”, terutama:

- boundary core-vs-module
- activity/audit foundation
- API consistency
- event consistency
- search foundation

Jika hanya satu prioritas strategis yang dipilih sekarang, pilih ini:

bekukan Platform Core sebagai primitive generik yang benar-benar reusable, lalu keluarkan seluruh coupling bisnis yang tidak wajib dari core sebelum jumlah module bertambah lebih banyak.
