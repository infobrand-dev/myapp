# Finance — Kas, Bank & Governance Akuntansi

Modul Finance mengelola dua hal sekaligus: **operasional kas & bank** sehari-hari, dan **governance akuntansi** seperti jurnal manual, period lock, dan approval.

---

## Kas & Bank

### Akun Keuangan

Daftarkan semua rekening bank dan kas fisik yang Anda gunakan di bisnis:

1. Buka **Finance → Akun**
2. Klik **Tambah Akun**
3. Isi nama (contoh: "BCA Operasional", "Kas Toko"), jenis (Bank / Kas), dan saldo pembuka
4. Simpan

Setiap akun memiliki **running balance** — saldo berjalan yang terupdate setiap ada transaksi. Cocokkan dengan rekening koran bank untuk rekonsiliasi.

### Saldo Pembuka (Opening Balance)

Input saldo awal saat pertama kali menggunakan Meetra agar semua laporan akurat sejak hari pertama:

1. Buka **Finance → Akun → pilih akun**
2. Klik **Atur Saldo Pembuka**
3. Isi tanggal dan nominal saldo
4. Simpan

### Kas Masuk

Untuk penerimaan uang di luar pembayaran invoice (misal: pinjaman masuk, setoran modal, pendapatan lain):
1. Buka **Finance → Kas Masuk → Tambah**
2. Pilih akun tujuan, isi tanggal, nominal, dan kategori
3. Lampirkan bukti jika ada
4. Simpan

### Kas Keluar

Untuk pengeluaran yang tidak berasal dari purchase supplier (misal: gaji, sewa, listrik, bensin):
1. Buka **Finance → Kas Keluar → Tambah**
2. Pilih akun sumber, isi tanggal, nominal, dan kategori
3. Lampirkan nota atau bukti pembayaran
4. Simpan

### Transfer Antar Rekening

Untuk memindahkan dana antara dua akun (misal: dari BCA ke Mandiri, atau dari Bank ke Kas):
1. Buka **Finance → Transfer**
2. Pilih akun asal dan akun tujuan
3. Isi tanggal dan nominal
4. Konfirmasi — kedua akun diperbarui sekaligus

### Kategori Transaksi

Setiap kas masuk/keluar bisa diberi kategori untuk memudahkan analisis. Kelola daftar kategori di **Finance → Kategori**.

Contoh kategori: Gaji & Tunjangan, Sewa, Utilitas, Marketing, Perjalanan Dinas, Perlengkapan Kantor.

---

## Cashbook View

Tampilan **Cashbook** menampilkan riwayat transaksi suatu rekening dalam urutan kronologis — mirip buku tabungan digital:

- Tanggal transaksi
- Keterangan
- Nominal masuk (debit)
- Nominal keluar (kredit)
- Saldo setelah transaksi

Buka di **Finance → Akun → pilih akun → Riwayat**.

---

## Auto Journal

Setiap transaksi kas — baik dari modul Sales, Purchases, Payments, maupun Finance sendiri — otomatis menghasilkan jurnal akuntansi. Anda tidak perlu input jurnal secara manual untuk transaksi rutin.

Untuk melihat jurnal dari suatu transaksi, buka detail transaksi dan klik **Lihat Jurnal**.

---

## Period Lock

Setelah periode akuntansi selesai dan laporan sudah diverifikasi, kunci periode agar tidak ada transaksi baru yang bisa diposting mundur ke tanggal tersebut.

**Mengunci periode:**
1. Buka **Finance → Period Lock**
2. Pilih tanggal akhir periode yang ingin dikunci
3. Konfirmasi

Setelah dikunci, setiap percobaan membuat atau mengedit transaksi dengan tanggal di periode tersebut akan ditolak sistem.

---

## Approval Flow

Untuk aksi berisiko — seperti void transaksi, edit nominal yang sudah diposting, atau perubahan tanggal — Meetra menerapkan **approval flow**:

1. User mengajukan permintaan perubahan
2. Sistem membuat notifikasi ke approver (user dengan role yang punya izin approve)
3. Approver menyetujui atau menolak
4. Aksi hanya dieksekusi setelah disetujui

Approval flow ini tersedia di **Mode Advanced** dan bisa dikonfigurasi di **Pengaturan → Approval**.

---

## Lampiran Bukti Transaksi

Semua transaksi Finance mendukung lampiran file. Simpan foto nota, screenshot transfer, atau kuitansi langsung di transaksi terkait agar mudah ditemukan saat audit atau rekonsiliasi.
