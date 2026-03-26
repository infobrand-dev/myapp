# Email Inbox Architecture

## Positioning
- `Email Inbox` harus tetap terpisah dari `Email Marketing`.
- `Email Marketing` tetap menjadi domain campaign blast, unsubscribe, tracking open/click, dan attachment template mass-send.
- `Email Inbox` menangani mailbox operasional: account SMTP/IMAP, folder, pesan masuk/keluar, manual compose, dan sinkronisasi inbox.

## Why separate
- Blast campaign dan mailbox operasional punya aturan deliverability, audit trail, throughput, dan consent yang berbeda.
- Operational email butuh state mailbox seperti folder, `message-id`, `in-reply-to`, read state, dan inbound sync log yang tidak cocok dicampur ke tabel campaign.
- Blast campaign perlu data recipient massal dan metrics marketing; mailbox perlu thread-by-thread trace dan config account per mailbox.

## Current rollout
- Modul ini sudah menyediakan struktur account, folder, message, attachment metadata, sync run, UI dasar, command fetch, dan job send.
- Outbound SMTP bisa langsung dipakai jika account diisi lengkap.
- Inbound fetch memakai ekstensi PHP IMAP. Jika ekstensi belum tersedia, sync akan berhenti aman dan menyimpan error tanpa merusak data.

## Safe next steps
- Tambahkan parsing MIME yang lebih lengkap untuk multipart/attachment besar.
- Tambahkan bridge opsional ke `conversations` bila email ingin masuk unified inbox.
- Tambahkan webhook provider-specific bila memakai Gmail API / Microsoft Graph sebagai pengganti IMAP polling.
- Tambahkan policy per company/branch jika mailbox perlu scope organisasi yang lebih sempit.
