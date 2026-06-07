# MyApp

Laravel 11 + Breeze (Blade) + Tabler UI. Core app menyediakan shell dasar seperti auth, dashboard, profile, users, roles, dan module registry. Fitur bisnis utama ditempatkan sebagai modul di `app/Modules`.

Dokumen arsitektur terkait:
- `ARCHITECTURE.md`
- `MODULES.md`
- `docs/product/crm-suite-blueprint.md`
- `docs/architecture/storage-file-model.md`
- `SAAS_TENANCY.md`
- `SAAS_PRODUCT_MODEL.md`
- `GO_LIVE_RUNBOOK.md`

## Quick start

### 1. Prasyarat
- PHP `>= 8.2`
- Composer
- PostgreSQL `>= 15` untuk lokal dan production
- Node.js + npm

### 2. Clone project
```bash
git clone <repo-url> myapp
cd myapp
```

### 3. Install dependency backend
```bash
composer install
```

### 4. Siapkan environment
```powershell
Copy-Item .env.example .env
```

Lalu isi minimal:
- `APP_NAME`
- `APP_URL`
- `DB_CONNECTION`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `DB_SSLMODE`

Default lokal yang direkomendasikan:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=myapp
DB_USERNAME=postgres
DB_PASSWORD=
DB_SSLMODE=disable
```

Catatan production:
- Untuk Supabase, gunakan `DB_CONNECTION=pgsql`.
- Isi host, database, username, dan password dari project Supabase.
- Biasanya `DB_SSLMODE=require`.
- Disarankan `DB_EMULATE_PREPARES=false` agar binding tipe PostgreSQL, terutama boolean dan JSON, lebih konsisten.
- Untuk tenancy registry terpisah, optional env `CENTRAL_DB_*` dan `TENANT_DB_*` sekarang didukung. Bila tidak diisi, keduanya fallback ke `DB_*`.
- Runtime tenancy default saat ini adalah `TENANT_RUNTIME_MODE=column`, jadi aplikasi tetap memakai koneksi utama dari `.env` dan isolasi aktif tetap melalui `tenant_id`. Mode `schema` atau `database` disiapkan untuk switch di tahap berikutnya.
- Registry topology tenant sekarang disiapkan di tabel `tenant_servers`, `tenant_databases`, dan `tenant_topologies`. Source of truth mapping tenant adalah `tenant_topologies`, dengan default `server_key=primary`, `database_key=main`, `schema_name=public`, dan `isolation_mode=tenant_id`.
- Topology registry juga mencakup runtime/app dan storage: `app_servers`, `tenant_runtime_topologies`, `storage_servers`, `storage_buckets`, dan `tenant_storage_topologies`. Default tenant baru akan mendapat mapping `primary-app` untuk runtime dan `primary-storage` untuk storage publik/private.
- `storage_profiles` sekarang adalah control-plane global/platform-level di koneksi `central`. Record tenant seperti `stored_files` hanya menyimpan referensi `storage_profile_id` dan snapshot lokasi aktual; tidak ada lagi asumsi FK lintas boundary tenant/control-plane.
- Query hardening sekarang memakai ownership manifest + readiness audit. Model central wajib pakai connection `central`, query tenant-scoped wajib punya tenant context aktif, dan `tenant:enable-schema-mode` akan menolak switch bila audit readiness masih punya temuan.

### 5. Generate app key
```bash
php artisan key:generate
```

### 6. Migrasi dan seed
```bash
php artisan migrate --seed
```

Catatan:
- Seed default membuat role dan permission inti.

### 7. Install dependency frontend
```bash
npm install
```

### 8. Build asset
Development build:
```bash
npm run dev
```

Watch mode:
```bash
npm run watch
```

Production build:
```bash
npm run production
```

Catatan:
- Project ini memakai Laravel Mix, bukan Vite.

### 9. Jalankan aplikasi
```bash
php artisan serve
```

### 10. Bersihkan cache
```bash
php artisan optimize:clear
```

## SaaS login flow
- Untuk mode `TENANT_MODE=saas`, login user final harus lewat subdomain tenant, misalnya `acme.example.com/login`.
- Untuk local development, resolver subdomain juga menerima host dasar dari `APP_URL`. Jadi bila `APP_URL=https://myapp.test` dan `SAAS_DOMAIN` production tetap `meetra.id`, route tenant seperti storefront publik tetap bisa diuji lewat `acme.myapp.test`.
- Apex/root domain dipakai untuk onboarding, landing, atau workspace lookup, bukan login tenant umum.
- Resolver tenant membaca slug dari subdomain lebih dulu lalu mengautentikasi user dalam scope `tenant_id` tersebut.
- Registrasi tenant user publik di subdomain tenant sekarang ditutup. Penambahan user tenant dilakukan lewat owner/admin, idealnya memakai undangan dari halaman `Users`.
- User baru tenant wajib verifikasi email terlebih dahulu sebelum bisa mengakses dashboard.

## SaaS self-serve sales flow
- Flow jualan publik berjalan lewat `/onboarding`: calon tenant memilih business suite yang ingin didaftarkan, memilih paket yang tersedia untuk suite tersebut, lalu mengisi workspace dan akun admin.
- Setelah itu sistem membuat `platform_plan_orders` + `platform_invoices` + `platform_invoice_items`, lalu tenant memilih pembayaran via Midtrans atau transfer bank manual dengan nominal unik.
- Tenant hasil self-serve onboarding dibuat dalam status `pending_payment` dan baru aktif setelah payment platform settle atau pembayaran manual diverifikasi.
- Welcome email tenant dikirim setelah payment sukses, sedangkan email invoice platform dikirim saat invoice diterbitkan.
- Business suite yang sudah dibuka untuk self-serve saat ini adalah `accounting` dan `commerce`. `Omnichannel` tetap disiapkan di codebase, tetapi belum dibuka sebagai jalur self-serve utama sampai siap dijual penuh.

## Queue
- Default dapat berjalan dengan `QUEUE_CONNECTION=sync`.
- Jika memakai async queue, jalankan worker yang sesuai dengan driver yang dipakai.

Contoh untuk driver database:
```bash
php artisan queue:table
php artisan migrate
php artisan queue:work
```

## Go-live audit
Jalankan audit readiness production:

```bash
php artisan golive:audit
```

Command ini memeriksa critical path env dan runtime seperti tenancy mode, session cookie, queue, tabel billing platform, mail, Midtrans, serta notification center / web push.

## Tenant topology readiness
- Audit query readiness topology:
  ```bash
  php artisan tenant:query-readiness-audit
  ```
- Audit topology + query readiness per tenant:
  ```bash
  php artisan tenant:health-check
  php artisan tenant:health-check acme
  ```
- Status health-check sekarang dibagi menjadi `Registry`, `Runtime`, `Storage`, dan `Move Ready`. Launch atau move dedicated tenant hanya aman bila semuanya `ok`/`ready`.
- Menandai tenant ke schema mode hanya boleh setelah audit bersih:
  ```bash
  php artisan tenant:enable-schema-mode acme --schema=tenant_acme
  ```
- Memindahkan registry tenant setelah copy data eksternal selesai:
  ```bash
  php artisan tenant:move acme main primary --target-schema=tenant_acme --isolation-mode=schema --app-server-key=primary-app --public-storage-bucket-key=public-default --private-storage-bucket-key=private-default
  ```
- Saat koneksi `central` terpisah dari `DB_*`, migration control-plane harus dijalankan di database `central` juga:
  ```bash
  php artisan migrate --database=central --path=database/migrations/2026_06_02_090000_create_tenant_registry_topology_tables.php --path=database/migrations/2026_06_02_090100_expand_tenants_for_schema_registry.php --path=database/migrations/2026_06_02_100000_create_tenant_topologies_table.php --path=database/migrations/2026_06_02_100100_add_key_to_tenant_databases_table.php --path=database/migrations/2026_06_02_101000_create_runtime_and_storage_topology_tables.php --path=database/migrations/2026_06_02_102000_create_central_storage_profiles_table.php --force
  ```

## PostgreSQL dan Supabase
- Database target utama aplikasi ini adalah PostgreSQL.
- Local development sebaiknya memakai PostgreSQL juga agar perilaku migration, JSON, boolean, index, dan query runtime sama dengan production.
- Untuk fitur chatbot embeddings di PostgreSQL/Supabase, extension `vector` harus tersedia. Migration akan mencoba menjalankan `CREATE EXTENSION IF NOT EXISTS vector`.
- Jika role database tidak diizinkan membuat extension, aktifkan `vector` lebih dulu dari dashboard atau SQL editor Supabase.

## File storage
- Public asset dapat diarahkan ke S3 dengan mengisi `AWS_*` lalu set `WORKSPACE_PUBLIC_FILESYSTEM_DISK=s3`.
- Dokumen sensitif seperti lampiran finance, proof payment, statement reconciliation, dan attachment sales sebaiknya memakai disk private. Default lokalnya `private` (`storage/app/private`), atau bisa diarahkan ke S3 private dengan `WORKSPACE_PRIVATE_FILESYSTEM_DISK=s3`.
- Metadata file internal dicatat di tabel `stored_files`, dan akses download private dicatat di `stored_file_access_logs`.
- Model akses file sekarang `private-first`:
  - `public_asset` boleh public permanent URL
  - `private_document` wajib lewat route app terotorisasi
  - `channel_shared_media` memakai signed URL singkat saat provider/penerima perlu fetch
  - `channel_inbound_evidence` disimpan private dengan provenance metadata
- Untuk routing storage yang owner-managed, gunakan halaman `Platform > Storage` untuk mendaftarkan `storage_profiles`. Sistem akan memilih profile aktif otomatis per scope/purpose, lalu menyimpan snapshot lokasi aktual di `stored_files`.
- Routing upload tenant sekarang juga membaca `tenant_storage_topologies` aktif. Default path akan diprefix ke `base_path` topology tenant, misalnya `tenants/<tenant_id>/public` atau `tenants/<tenant_id>/private`.
- Jika akses S3 ditutup atau profile dinonaktifkan, upload baru tidak akan memilih profile itu lagi. File historis tetap tercatat, dan download akan mengembalikan status terkontrol (`404` atau `503`) sambil menandai `availability_status`.
- Jika routing topology tenant tidak bisa dipakai dan sistem jatuh ke legacy disk, `stored_files.meta.storage_topology_degraded=true` akan disimpan sebagai alert operasional.
- Provider/channel tidak selalu memberi URL public permanen. Sistem mendukung tiga pola:
  - `provider_media_id`
  - `provider_media_url`
  - signed URL internal untuk outbound fetch
- Inbound media penting sekarang dicatat dengan provenance seperti `provider_origin`, `provider_media_id`, `provider_media_url`, `copied_locally`, `fetched_at`, dan `stored_file_id` bila berhasil dicopy ke storage internal.
- Capture inbound media saat ini sudah aktif untuk:
  - WhatsApp Cloud `media_id`
  - Meta social attachment URL
  Provider lain tetap aman di level provenance sampai fetch flow resminya tersedia.
- Audit cepat storage bisa dijalankan dengan:
  ```bash
  php artisan storage:audit-profiles
  ```

## e-Faktur partner handoff
- Export e-Faktur CSV tetap tersedia untuk proses manual tenant.
- Untuk integrator pihak ketiga yang sudah memiliki akses DJP, sistem juga menyiapkan handoff JSON export dari halaman Tax Register.
- Konfigurasi dasar partner:
  - `EFAKTUR_PARTNER_MODE`
  - `EFAKTUR_PARTNER_NAME`
  - `EFAKTUR_PARTNER_ENDPOINT`
  - `EFAKTUR_PARTNER_API_KEY`
- Mode ini belum melakukan direct submit ke DJP dari aplikasi.

## Notifications and web push
- Notification center tersedia di area app lewat bell topbar dan halaman `/notifications`.
- Web push memakai browser Push API + service worker `public/sw.js`.
- Production env yang perlu diisi:
  - `NOTIFICATION_VAPID_SUBJECT`
  - `NOTIFICATION_VAPID_PUBLIC_KEY`
  - `NOTIFICATION_VAPID_PRIVATE_KEY`
- Pastikan PHP OpenSSL aktif dan curve `prime256v1` tersedia.
- Setelah mengubah env, jalankan:

```bash
php artisan optimize:clear
```

- Untuk mengaktifkan push:
  1. login ke area app
  2. buka halaman `/notifications`
  3. klik `Aktifkan Web Push`
  4. izinkan notification permission di browser

Catatan:
- Prompt install PWA saat ini hanya ditampilkan di area app, bukan landing page publik.
- Chrome Android dapat menawarkan install app setelah beberapa kunjungan.
- iPhone/iPad tetap memakai alur Safari `Share > Add to Home Screen`.

## Tenant repair
Untuk scan atau memperbaiki tenant context workspace:

```bash
php artisan meetra:repair-tenant 2
php artisan meetra:repair-tenant 2 --fix
php artisan meetra:repair-tenant --all
php artisan meetra:repair-tenant --all --fix
```

Command ini fokus ke integritas context tenant:
- sinkronisasi sequence PostgreSQL untuk `companies`, `branches`, `user_companies`, dan `user_branches`
- memastikan tenant punya default `company` dan `branch`
- memastikan user tenant punya default access `company` dan `branch`

## Onboarding cleanup
Untuk membersihkan workspace onboarding yang stale sambil tetap mengunci slug sementara:

```bash
php artisan onboarding:cleanup-stale
php artisan onboarding:cleanup-stale --dry-run
```

Command ini saat ini membersihkan:
- tenant `pending_payment` yang sudah stale dan belum pernah punya order berstatus `paid`
- workspace `trialing` yang sudah lewat masa trial dan belum pernah jadi customer berbayar

Saat cleanup dijalankan, slug tenant akan tetap dikunci sementara agar tidak langsung bisa dipakai ulang.

## Migration scan
Untuk scan file migration core dan module lalu membandingkannya dengan tabel `migrations`:

```bash
php artisan meetra:migrate
php artisan meetra:migrate --module=finance
php artisan meetra:migrate --show-all
php artisan meetra:migrate --command-for=1
php artisan meetra:migrate --command-for=2,3
```

Command ini tidak menjalankan migration custom. Untuk apply tetap gunakan `artisan migrate`, misalnya:

```bash
php artisan migrate --path=database/migrations --force
php artisan migrate --path=app/Modules/Finance/Database/Migrations --force
php artisan migrate --path=app/Modules/Finance/Database/Migrations/2026_04_08_150000_create_finance_accounts_and_link_transactions.php --force
```

## Boundary audit
Untuk audit boundary antara `core` dan `modules`:

```bash
php artisan modules:audit-boundaries
```

Command ini menandai dua kebocoran utama:
- file core yang langsung mengimpor class dari `App\\Modules\\*`
- migration core yang langsung membuat atau mengubah tabel milik module

## Sentry
- Paket monitoring yang dipakai: `sentry/sentry-laravel`
- Isi minimal di `.env` production:
  - `SENTRY_LARAVEL_DSN`
  - `SENTRY_ENVIRONMENT`
  - `SENTRY_RELEASE`
- Sample tracing default di `.env.example`:
  - `SENTRY_TRACES_SAMPLE_RATE=0.1`
  - `SENTRY_PROFILES_SAMPLE_RATE=0`
- Setelah mengubah env, jalankan:

```bash
php artisan optimize:clear
```

- Cek integrasi lokal/runtime:

```bash
php artisan about
```

## AI Credits
- Konversi internal launch: `1 AI Credit = 1.000 tokens`
- Harga default launch:
  - `Rp 100 / AI Credit`
  - `500 AI Credits = Rp 50.000`
  - `1.000 AI Credits = Rp 100.000`
- Fallback env default tersedia di `.env.example`:
  - `AI_CREDIT_CURRENCY=IDR`
  - `AI_CREDIT_UNIT_TOKENS=1000`
  - `AI_CREDIT_PRICE_PER_CREDIT=100`
  - `AI_CREDIT_PACK_OPTIONS=500,1000`
- Source of truth runtime tetap di database table `ai_credit_pricing_settings` bila migration sudah dijalankan dan pricing diubah dari control plane.

Jika DSN terisi, blok `Sentry` akan tampil aktif dan unhandled exception akan dikirim ke proyek Sentry Anda.

## Realtime
- Realtime memakai driver `pusher` dengan server yang ditujukan untuk Pusher-compatible stack.
- Untuk local/self-hosted, gunakan `soketi`.

## WhatsApp Web bridge
Jalankan bridge:

```bash
node app/Modules/WhatsAppWeb/node/server.js
```

Default bridge URL lokal:
- `http://localhost:3020`

## Webhook examples
- WhatsApp API: `POST /whatsapp-api/webhook`
- Social Media: `POST /social-media/webhook`
- Live Chat bootstrap: `POST /live-chat/api/{token}/bootstrap`
- UTAS personal webhook: `POST /webhooks/utas` (`state=paid` langsung kirim email ke `UTAS_WEBHOOK_NOTIFY_EMAIL` via native PHP `mail()` / `php.ini`; `state=complete` diterima tanpa penyimpanan DB)

Pastikan `APP_URL` dan reverse proxy sesuai jika webhook dipakai di environment publik.

## Modules
Ringkasan modul tersedia di `MODULES.md`.

Kategori saat ini:
- commerce: `products`, `inventory`, `discounts`, `sales`, `payments`, `purchases`, `finance`, `point-of-sale`
- accounting (product line pricing): bundle existing `sales`, `payments`, `purchases`, `finance`, `point-of-sale`, `reports`
- commerce (product line pricing): `storefront`, `shipping`, `fulfillment` dengan shared core `products`, `sales`, `payments`, `contacts`
- reporting: `reports`
- communication: `conversations`, `live_chat`, `whatsapp_api`, `whatsapp_web`, `social_media`, `email_marketing`, `email_inbox`
- automation: `chatbot`
- support: `crm`, `contacts`, `task_management`, `shortlink`, `sample_data`
