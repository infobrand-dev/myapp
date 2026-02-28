# MyApp

Laravel 11 + Breeze (Blade) + Tabler UI. Core fitur: Dashboard, Profile, Users, Roles. Modul lain bersifat add-on.

## Quick Start (Install dari nol)

### Opsi A (Disarankan): Web Installer
Setelah project dicopy dan dependency terpasang, buka:

```text
/install
```

Installer akan:
1. cek requirement server,
2. simpan konfigurasi `.env`,
3. test koneksi database,
4. jalankan migrate + seed,
5. buat akun Super-admin,
6. lock installer otomatis.

### Opsi B: Manual Command

### 1. Prasyarat
- PHP `>= 8.2`
- Composer
- MySQL / MariaDB
- Node.js + npm

Cek versi yang aktif:
```bash
php -v
composer -V
node -v
npm -v
```

### 2. Clone project dan masuk folder
```bash
git clone <repo-url> myapp
cd myapp
```

Contoh:
```bash
git clone https://github.com/infobrand-dev/myapp.git myapp
cd myapp
```

### 3. Install dependency backend
```bash
composer install
```

Jika composer memory limit kecil:
```bash
php -d memory_limit=-1 composer.phar install
```

### 4. Siapkan file environment
```bash
cp .env.example .env
```

Untuk Windows PowerShell:
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

Contoh lokal:
```env
APP_NAME=MyApp
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=myapp
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Generate app key
```bash
php artisan key:generate
```

Penjelasan:
- Command ini mengisi `APP_KEY` di `.env`.
- Wajib sebelum login/session/enkripsi berjalan normal.

### 6. Migrasi dan seed data
```bash
php artisan migrate --seed
```

Penjelasan:
- `migrate` membuat tabel.
- `seed` mengisi data awal termasuk role dan user default.
- Jika hanya migrate tanpa seed:
```bash
php artisan migrate
```

### 7. Install dependency frontend
```bash
npm install
```

### 8. Build asset
Untuk development:
```bash
npm run dev
```

Penjelasan:
- Menjalankan Vite watcher (asset update realtime).
- Gunakan ini saat coding UI.

Untuk production build:
```bash
npm run build
```

Penjelasan:
- Generate asset final ke `public/build`.
- Gunakan ini untuk deploy production.

### 9. Jalankan aplikasi
```bash
php artisan serve
```

Default URL:
- `http://127.0.0.1:8000`

Kalau mau host/port custom:
```bash
php artisan serve --host=0.0.0.0 --port=8080
```

### 10. Bersihkan cache (jika ada masalah config/route/view)
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

Reset semua cache sekaligus:
```bash
php artisan optimize:clear
```

### 11. Akun default
- Email: `superadmin@myapp.test`
- Password: `password123!`

## Queue
- Default `QUEUE_CONNECTION=sync`.
- Jika async:
  1. set `QUEUE_CONNECTION=database` (atau `redis`)
  2. jika `database`, buat tabel jobs:
     ```bash
     php artisan queue:table
     php artisan migrate
     ```
  3. jalankan worker:
     ```bash
     php artisan queue:work
     ```

Untuk mode background (production), jalankan via Supervisor/PM2/service manager.

## Realtime (Soketi)
- Node 18 portable ada di: `app/Modules/WhatsAppApi/node18/`
- Jalankan (Windows):
  ```bat
  set SOKETI_DEFAULT_APP_ID=local-app
  set SOKETI_DEFAULT_APP_KEY=local-key
  set SOKETI_DEFAULT_APP_SECRET=local-secret
  app/Modules/WhatsAppApi/node18/node-v18.20.4-win-x64/node.exe node_modules/@soketi/soketi/bin/server.js start
  ```
- Port default: `6001` (WS), `9601` (metrics)

## Webhook (global)
- WhatsApp API: `POST /whatsapp-api/webhook`
- Social Media: `POST /social-media/webhook`

Catatan:
- Endpoint ini dipakai provider eksternal (Meta/API pihak ketiga) untuk kirim event masuk.
- Pastikan `APP_URL` dapat diakses publik jika dipakai di production.

## Seeder demo
```bash
php artisan db:seed --class=ConversationDemoSeeder
```

Fungsi:
- Membuat sample instance WA API + sample conversation untuk testing awal.

## Modul

### Conversations
- Inbox gabungan (internal/WA API/Social DM), claim/lock, activity log.

### Chatbot
- Kelola akun AI (`chatbot_accounts`).
- Env: `OPENAI_API_KEY`, optional `OPENAI_MODEL`.

### WhatsApp API
- Inbox WA, manajemen instance (Super-admin), webhook Cloud API.

### WhatsApp Bro
- Bridge WA Web via Socket.IO:
  ```bash
  node app/Modules/WhatsAppBro/node/server.js
  ```

### Social Media
- Instagram/Facebook DM, webhook Meta, integrasi Conversations.

### Task Management
- Internal Memo + Task Templates.

### Shortlink
- CRUD short URL.

### Contacts
- Manajemen kontak.

### Email Marketing
- Campaign + attachment templates.
