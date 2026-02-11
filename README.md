# MyApp

Laravel 11 + Breeze (Blade) + Tabler UI. Core fitur: Dashboard, Profile, Users & Roles. Modul lain bersifat add-on.

## Kebutuhan inti
- PHP ≥ 8.2
- MySQL
- Node.js (build aset; realtime via Soketi butuh Node 18)

## Instalasi inti
1. `composer install`
2. Salin `.env` → isi DB → `php artisan key:generate`
3. `php artisan migrate --seed`
4. Frontend: `npm install` → `npm run dev` (atau `npm run build` untuk produksi)
5. Bersih cache: `php artisan config:clear && php artisan route:clear && php artisan view:clear`

## Realtime (Soketi, gratis)
- Node 18 portable: `app/Modules/WhatsAppApi/node18/`
- Jalankan (dev):
  ```
  set SOKETI_DEFAULT_APP_ID=local-app
  set SOKETI_DEFAULT_APP_KEY=local-key
  set SOKETI_DEFAULT_APP_SECRET=local-secret
  app/Modules/WhatsAppApi/node18/node-v18.20.4-win-x64/node.exe node_modules/@soketi/soketi/bin/server.js start
  ```
- Port: 6001 (WS), 9601 (metrics); `.env` sudah pakai key/secret itu.

## Queue
- Default `sync`. Untuk async set `QUEUE_CONNECTION=database`/`redis`, lalu `php artisan queue:work`.

## Webhook (global)
- WA API: `POST /whatsapp-api/webhook` (token=api_token, contact_id, message)
- Social DM: `POST /social-media/webhook` (token, platform=instagram|facebook, contact_id, message)
- CSRF dibebaskan untuk dua endpoint ini.

## Seeder demo
`php artisan db:seed --class=ConversationDemoSeeder` → instance WA API demo + 1 percakapan (token dicetak).

---

## Modul

### Conversations (core inbox)
- Inbox gabungan (internal/WA API/Social DM), claim/lock, activity log, sidebar Conversations.
- Integrasi: Chatbot (auto-reply), Echo/Soketi untuk realtime.

### Chatbot
- Kelola akun AI (OpenAI) di menu Chatbot (`chatbot_accounts`).
- Env: `OPENAI_API_KEY`, optional `OPENAI_MODEL`.
- Digunakan oleh WA API & Social Media ketika opsi auto-reply diaktifkan.

### WhatsApp API
- Inbox WA, manajemen Instances (Super-admin).
- Webhook: `/whatsapp-api/webhook`.
- Integrasi: Chatbot (auto-reply), Conversations, Echo/Soketi.

### WhatsApp Bro (bridge WA Web)
- QR connect via Socket.IO; start: `node app/Modules/WhatsAppBro/node/server.js`.
- Opsional kirim ke Conversations via webhook (set URL/token di env).

### Social Media (Instagram/Facebook DM)
- Multi account (Page ID / IG Business ID + access token).
- Webhook: `/social-media/webhook` + verify token Meta.
- Env: `META_PAGE_TOKEN`, `META_PAGE_ID`, `META_IG_BUSINESS_ID`, `META_VERIFY_TOKEN`, `META_GRAPH_VERSION`.
- Integrasi: Chatbot auto-reply, Conversations.

### Task Management (Internal Memo & Task Templates)
- Memo dengan task/subtask, auto progress, template reuse.

### Shortlink
- CRUD short URL sederhana.

### Contacts
- Manajemen kontak untuk integrasi kampanye.

### Email Marketing
- Campaign + attachment templates, memakai Tabler UI form.

---

## Build ulang aset
`npm run dev` (dev) / `npm run build` (prod).
