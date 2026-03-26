# Email Inbox

Modul ini ditujukan untuk email operasional dan inbox mailbox, bukan untuk campaign blast.

## Fitur yang sudah disiapkan
- konfigurasi account inbound/outbound
- penyimpanan folder mailbox
- penyimpanan email masuk dan keluar
- sync run log
- compose email keluar
- job send via SMTP account
- command fetch inbox via IMAP

## Prasyarat runtime
- outbound: SMTP account valid
- inbound: ekstensi PHP `imap` aktif jika memakai polling IMAP
- queue worker bila tidak memakai `sync`

## Checklist aman sebelum produksi
1. Pisahkan mailbox transactional dari mailbox marketing.
2. Gunakan credential mailbox khusus aplikasi, bukan password user personal.
3. Aktifkan TLS dan validasi sertifikat kecuali ada alasan kuat untuk menonaktifkan.
4. Batasi fetch per run dan gunakan worker/scheduler.
5. Tambahkan retention policy untuk raw body dan attachment besar.
6. Tambahkan bounce/spam monitoring di provider email.
