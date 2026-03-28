# MyApp

Laravel 11 + Breeze (Blade) + Tabler UI. Core app menyediakan shell dasar seperti auth, dashboard, profile, users, roles, dan module registry. Fitur bisnis utama ditempatkan sebagai modul di `app/Modules`.

Dokumen arsitektur terkait:
- `ARCHITECTURE.md`
- `MODULES.md`
- `SAAS_TENANCY.md`
- `SAAS_PRODUCT_MODEL.md`
- `GO_LIVE_RUNBOOK.md`

## Quick start

### 1. Prasyarat
- PHP `>= 8.2`
- Composer
- MySQL atau MariaDB
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
- Apex/root domain dipakai untuk onboarding, landing, atau workspace lookup, bukan login tenant umum.
- Resolver tenant membaca slug dari subdomain lebih dulu lalu mengautentikasi user dalam scope `tenant_id` tersebut.

## SaaS self-serve sales flow
- Flow jualan publik saat ini berjalan lewat `/onboarding`: pilih paket, daftar workspace, sistem membuat `platform_plan_orders` + `platform_invoices` + `platform_invoice_items`, lalu redirect ke checkout Midtrans.
- Tenant hasil self-serve onboarding dibuat dalam status `pending_payment` dan baru aktif setelah payment platform settle.
- Welcome email tenant dikirim setelah payment sukses, sedangkan email invoice platform dikirim saat invoice diterbitkan.
- Paket omnichannel publik saat ini mengontrol entitlement modul inti seperti `conversations`, `social_media`, `chatbot`, `whatsapp_api`, dan `whatsapp_web` melalui middleware plan feature.

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

Command ini memeriksa critical path env dan runtime seperti tenancy mode, session cookie, queue, tabel billing platform, mail, dan Midtrans.

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

Pastikan `APP_URL` dan reverse proxy sesuai jika webhook dipakai di environment publik.

## Modules
Ringkasan modul tersedia di `MODULES.md`.

Kategori saat ini:
- commerce: `products`, `inventory`, `discounts`, `sales`, `payments`, `purchases`, `finance`, `point-of-sale`
- reporting: `reports`
- communication: `conversations`, `live_chat`, `whatsapp_api`, `whatsapp_web`, `social_media`, `email_marketing`, `email_inbox`
- automation: `chatbot`
- support: `contacts`, `task_management`, `shortlink`, `sample_data`
