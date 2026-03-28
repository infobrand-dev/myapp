# GO_LIVE_RUNBOOK.md

## Tujuan
- Menjalankan launch SaaS production dengan checklist yang pendek, berurutan, dan bisa diulang.

## 1. Runtime config
- Pastikan `.env` production sudah benar untuk:
  - `APP_ENV=production`
  - `APP_DEBUG=false`
  - `APP_URL=https://your-domain`
  - `TENANT_MODE=saas`
  - `SAAS_DOMAIN=your-domain`
  - `PLATFORM_ADMIN_SUBDOMAIN=dash`
  - `SESSION_DOMAIN=.your-domain`
  - `SESSION_SECURE_COOKIE=true`
  - `QUEUE_CONNECTION=database` atau `redis`

## 2. Secrets
- Isi secret production:
  - mail SMTP
  - Midtrans server/client key
  - WA verify token
  - Meta verify token
  - secret lain yang masih placeholder

## 3. Database
- Jalankan migration terbaru.
- Pastikan tabel berikut ada:
  - `jobs`
  - `job_batches`
  - `failed_jobs`
  - `platform_plan_orders`
  - `platform_invoices`
  - `platform_payments`

## 4. Workers
- Jalankan queue worker production.
- Pastikan prosesnya dipantau supervisor/systemd.

Contoh:
```bash
php artisan queue:work --tries=3 --timeout=120
```

## 5. Scheduler
- Pasang cron:

```bash
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
```

## 6. DNS dan SSL
- Pastikan domain utama hidup.
- Pastikan wildcard subdomain tenant hidup.
- Pastikan `dash.domain.com` hidup.
- Pastikan SSL valid untuk semuanya.

## 7. Webhook
- Midtrans notification URL:
  - `/platform/billing/midtrans/webhook`
- Pastikan webhook provider lain juga sudah diarahkan ke domain production final.

## 8. Smoke test minimum
- `dash.domain.com/login`
- `tenant.domain.com/login`
- dashboard platform owner
- public invoice
- Midtrans checkout
- Midtrans callback
- payment tercatat
- subscription aktif
- email invoice/payment terkirim

## 9. Audit
- Jalankan:

```bash
php artisan golive:audit
```

- Launch hanya jika item `FAIL` sudah nol.

## 10. Freeze
- Setelah smoke test lolos, hindari merge fitur baru sampai launch stabil.
