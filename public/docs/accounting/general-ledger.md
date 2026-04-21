# Buku Besar (General Ledger)

Buku Besar menampilkan riwayat seluruh transaksi untuk setiap akun secara detail. Ini adalah sumber data utama untuk menelusuri asal-usul setiap angka dalam laporan keuangan.

---

## Cara Membuka Buku Besar

1. Buka **Reports → Buku Besar**
2. Pilih **akun** yang ingin ditelusuri
3. Tentukan **rentang tanggal**
4. Opsional: filter berdasarkan perusahaan atau cabang
5. Klik **Tampilkan**

---

## Kolom yang Ditampilkan

| Kolom | Keterangan |
|-------|-----------|
| Tanggal | Tanggal efektif transaksi |
| Referensi | Nomor jurnal atau nomor dokumen |
| Keterangan | Deskripsi transaksi |
| Debit | Nominal debit pada akun ini |
| Kredit | Nominal kredit pada akun ini |
| Saldo | Saldo berjalan akun setelah transaksi |

---

## Menelusuri Transaksi

Setiap baris di Buku Besar bisa diklik untuk membuka sumber transaksinya:

- Klik **nomor referensi** untuk membuka jurnal terkait
- Dari jurnal, Anda bisa lanjut ke dokumen asalnya (invoice, purchase, payment)

Ini memudahkan audit trail dari laporan keuangan sampai ke dokumen sumber.

---

## Filter Lanjutan

Gunakan filter berikut untuk mempersempit tampilan:

**Filter Akun** — Pilih satu akun spesifik atau beberapa akun sekaligus

**Filter Periode** — Tentukan rentang tanggal. Disarankan pilih per bulan agar lebih mudah dibaca

**Filter Perusahaan / Cabang** — Jika Anda mengelola beberapa entitas dalam satu workspace

---

## Contoh Penggunaan

**Memeriksa mutasi rekening bank**
Pilih akun Bank, filter bulan ini → tampil seluruh penerimaan dan pengeluaran dari rekening tersebut beserta running balance.

**Menelusuri piutang customer tertentu**
Pilih akun Piutang Usaha, cari berdasarkan nama customer di kolom keterangan → tampil semua invoice dan pembayaran yang terkait.

**Verifikasi saldo kas**
Pilih akun Kas → cocokkan saldo akhir di Buku Besar dengan jumlah uang fisik di tangan.

---

## Ekspor

Buku Besar bisa diekspor ke **Excel**. Klik tombol **Ekspor** untuk mengunduh data sesuai filter yang sedang aktif.
