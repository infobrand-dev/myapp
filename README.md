# MyApp

Laravel 11 + Breeze (Blade) + Tabler UI. Core app menyediakan shell dasar seperti installer, auth, dashboard, profile, users, roles, dan module registry. Fitur bisnis utama ditempatkan sebagai modul di `app/Modules`.

Dokumen arsitektur terkait:
- `ARCHITECTURE.md`
- `MODULES.md`
- `SAAS_TENANCY.md`
- `SAAS_PRODUCT_MODEL.md`

## Quick start

### Opsi A: Web installer
Setelah project dicopy dan dependency terpasang, buka:

```text
/install
```

Installer akan:
1. cek requirement server,
2. menyimpan konfigurasi `.env`,
3. menguji koneksi database,
4. menjalankan migrate + seed core,
5. membuat akun Super-admin pertama dari form instalasi,
6. menandai aplikasi sebagai installed.

### Opsi B: Manual setup

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
- Untuk instalasi baru, akun Super-admin sebaiknya dibuat melalui `/install`, bukan diasumsikan dari seed default lama.

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

## Installer notes
- Installer tersedia di `/install`.
- Installer akan redirect ulang ke dashboard bila aplikasi sudah dianggap installed.
- Status installed ditentukan dari `APP_KEY`, `storage/app/installed.lock`, `APP_INSTALLED`, dan fallback pengecekan tabel inti untuk kompatibilitas instalasi lama.

## Queue
- Default dapat berjalan dengan `QUEUE_CONNECTION=sync`.
- Jika memakai async queue, jalankan worker yang sesuai dengan driver yang dipakai.

Contoh untuk driver database:
```bash
php artisan queue:table
php artisan migrate
php artisan queue:work
```

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
- communication: `conversations`, `live_chat`, `whatsapp_api`, `whatsapp_web`, `social_media`, `email_marketing`
- automation: `chatbot`
- support: `contacts`, `task_management`, `shortlink`, `sample_data`
