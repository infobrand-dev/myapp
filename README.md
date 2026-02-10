# MyApp

Laravel 11 + Breeze + Tabler UI dengan modul Conversations, WhatsApp API, Social Media, WhatsApp Bro, Shortlink, Contacts, Email Marketing.

## Kebutuhan
- PHP ≥ 8.2
- Node (build aset). Untuk Soketi: Node 18.
- MySQL

## Setup Cepat
1) Backend: `composer install` → salin `.env` → `php artisan key:generate` → `php artisan migrate --seed`
2) Frontend: `npm install` → `npm run dev` (atau `npm run watch`)

## Realtime (Soketi, gratis)
- Node 18 portable tersedia: `app/Modules/WhatsAppApi/node18/`
- Start Soketi (dev):
  ```
  set SOKETI_DEFAULT_APP_ID=local-app
  set SOKETI_DEFAULT_APP_KEY=local-key
  set SOKETI_DEFAULT_APP_SECRET=local-secret
  app/Modules/WhatsAppApi/node18/node-v18.20.4-win-x64/node.exe node_modules/@soketi/soketi/bin/server.js start
  ```
- Port: 6001 (WS), 9601 (metrics). `.env` sudah pakai key/secret itu.

## Modul & Menu
- Conversations: inbox gabungan + claim/lock
- WhatsApp API: Inbox, Instances (Super-admin)
- Social Media: placeholder inbox DM (webhook siap)
- WhatsApp Bro: bridge QR via Socket.IO (start: `node app/Modules/WhatsAppBro/node/server.js`)
- Shortlink, Contacts, Email Marketing, Task Management

## Webhook
- WA API: `POST /whatsapp-api/webhook` (token=api_token instance, contact_id, message)
- Social DM: `POST /social-media/webhook` (token, platform=instagram|facebook, contact_id, message)
- CSRF sudah dibebaskan untuk dua endpoint ini.

## Seeder Demo
`php artisan db:seed --class=ConversationDemoSeeder`
- Membuat instance WA API demo + 1 percakapan, token dicetak di output.

## Queue
- Default `sync`. Untuk async gunakan `QUEUE_CONNECTION=database/redis` dan jalankan `php artisan queue:work`.

## Build Ulang Assets
`npm run dev` (dev) / `npm run prod` (build).
