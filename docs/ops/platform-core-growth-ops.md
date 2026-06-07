# Platform Core Growth Ops Baseline

## Growth-heavy tables

- `notifications`
- `notification_deliveries`
- `stored_file_access_logs`
- `activity_log`
- `platform_audit_logs`
- `platform_activity_events`
- `platform_event_outbox`
- `platform_webhook_receipts`
- `search_documents`
- provider-specific webhook event tables

## Retention baseline

- webhook receipts: 30 hari raw payload retention kecuali sedang investigasi
- notification deliveries: review per 90 hari untuk archive/purge
- file access logs: review per 90 hari
- platform activity events: review per 180 hari
- audit logs: jangan purge tanpa policy compliance yang eksplisit

## Partition candidates

Prioritas pertama:

- `notification_deliveries`
- `stored_file_access_logs`
- `platform_audit_logs`
- `platform_activity_events`
- provider webhook event tables

## Operational rules

- dashboard/report agregat baru tidak boleh langsung query tabel transaksi mentah tanpa review cost
- tabel log-heavy wajib punya index review berkala
- queue backlog dan slow query harus dipantau sebelum tenant growth dipercepat
- tenant shared mode aman untuk growth awal, tetapi selective isolation hanya dilakukan setelah health check dan query readiness bersih
