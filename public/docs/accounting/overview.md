# Accounting — Overview

Meetra menggunakan sistem **double-entry bookkeeping** — setiap transaksi selalu menghasilkan dua sisi pencatatan (debit dan kredit) yang seimbang. Prinsip ini menjamin laporan keuangan yang akurat dan bisa diaudit.

Yang membuat Meetra berbeda: **Anda tidak perlu memahami akuntansi untuk menggunakannya sehari-hari.** Jurnal terbentuk otomatis dari setiap transaksi. Fitur akuntansi formal seperti COA, jurnal manual, dan laporan GL tersedia untuk staf keuangan yang memang membutuhkannya.

---

## Dua Mode Tampilan

Meetra menyediakan dua mode yang bisa dipilih per workspace:

### Mode Standard
Untuk pemilik usaha dan operator harian. Menampilkan transaksi, faktur, dan ringkasan keuangan dalam bahasa yang mudah dipahami — tanpa istilah teknis akuntansi.

### Mode Advanced
Untuk staf keuangan dan akuntan. Membuka akses ke:
- Chart of Accounts (COA) dan pengelolaan akun
- Jurnal manual dengan validasi debit/kredit
- Audit trail detail setiap perubahan
- Period lock dan governance jurnal
- Approval flow untuk transaksi sensitif

Untuk mengubah mode: **Pengaturan → Tampilan Accounting**.

---

## Alur Accounting di Meetra

```
Transaksi Operasional            Accounting Layer
─────────────────────────────────────────────────────
Invoice Penjualan   ──┐
Penerimaan Barang   ──┤──▶  Auto Journal (Posted)  ──▶  GL / Buku Besar
Pembayaran Masuk    ──┤
Kas Keluar          ──┘

                         +  Jurnal Manual (Draft → Post)

                         ──▶  Trial Balance  ──▶  Neraca & Laba Rugi
```

Semua jurnal otomatis langsung masuk ke status **posted** dan tidak bisa diedit — hanya bisa dibatalkan dengan jurnal koreksi. Jurnal manual bisa disimpan sebagai **draft** dulu sebelum diposting.

---

## Modul dalam Ekosistem Accounting

| Modul | Fungsi Utama |
|-------|-------------|
| [Products](/products/overview) | Master produk, harga, margin, stok awal |
| [Sales](/sales/overview) | Invoice penjualan, piutang, retur |
| [Purchases](/purchases/overview) | Pembelian supplier, receiving, hutang |
| [Payments](/payments/overview) | Terima & bayar, alokasi, bukti bayar |
| [Inventory](/inventory/overview) | Stok fisik, mutasi, valuasi persediaan |
| [Finance](/finance/overview) | Kas & bank, jurnal manual, period lock |
| [Contacts](/contacts/overview) | Customer, supplier, profil pajak (NPWP) |
| [Reports](/reports/overview) | Laba rugi, neraca, arus kas, aging, GL |

---

## Jurnal Otomatis

Meetra membuat jurnal akuntansi secara otomatis untuk setiap transaksi:

| Transaksi | Debit | Kredit |
|-----------|-------|--------|
| Finalize invoice penjualan | Piutang Usaha | Pendapatan Penjualan |
| Terima pembayaran dari customer | Kas / Bank | Piutang Usaha |
| Finalize purchase dari supplier | Persediaan / Beban | Hutang Usaha |
| Bayar hutang ke supplier | Hutang Usaha | Kas / Bank |
| Kas masuk (non-invoice) | Kas / Bank | Akun sesuai kategori |
| Kas keluar (non-purchase) | Akun sesuai kategori | Kas / Bank |

> Untuk melihat jurnal dari suatu transaksi, buka detail transaksi dan klik **Lihat Jurnal**.

---

## Period Lock

Setelah periode akuntansi selesai dan laporan sudah diverifikasi, Anda bisa **mengunci periode** agar tidak ada transaksi baru yang bisa diposting ke tanggal tersebut. Ini mencegah perubahan tidak sengaja pada data historis.

Buka **Finance → Period Lock** untuk mengelola kunci periode.

---

## Approval Flow

Untuk aksi sensitif seperti void transaksi atau edit yang memengaruhi jurnal, Meetra menyediakan approval flow sederhana. Aksi tersebut tidak langsung dieksekusi — perlu disetujui oleh user dengan role yang memiliki izin approval.

---

## Import Data Awal

Sebelum mulai bertransaksi, Anda bisa import data awal secara bulk via file Excel/CSV:

| Data | Modul |
|------|-------|
| Daftar produk | Products → Import |
| Daftar customer & supplier | Contacts → Import |
| Saldo pembuka akun | Finance → Opening Balance |
| Stok awal | Inventory → Stok Awal |
