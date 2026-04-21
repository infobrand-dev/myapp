# Kas & Bank

Panduan ini menjelaskan cara mengelola kas dan rekening bank di Meetra secara efektif.

---

## Menyiapkan Akun di Awal

Sebelum mulai mencatat transaksi, pastikan semua rekening bank dan kas Anda sudah terdaftar di sistem:

1. Buka **Finance → Akun**
2. Tambahkan setiap rekening bank yang aktif digunakan (contoh: BCA Operasional, Mandiri Tabungan, BRI Giro)
3. Tambahkan akun Kas jika Anda juga mengelola uang tunai
4. Isi **saldo awal** masing-masing rekening sesuai posisi saldo saat mulai menggunakan Meetra

> Saldo awal hanya perlu diinput sekali. Setelah itu saldo akan bergerak otomatis mengikuti transaksi.

---

## Saldo Pembuka

Saldo pembuka adalah saldo rekening Anda pada tanggal mulai menggunakan Meetra. Ini penting agar laporan keuangan mencerminkan kondisi nyata sejak awal.

Cara mengatur saldo pembuka:
1. Buka **Finance → Akun → pilih akun**
2. Klik **Atur Saldo Pembuka**
3. Isi tanggal dan nominal saldo
4. Simpan

---

## Memantau Saldo Kas

### Cashbook View

Tampilan Cashbook menampilkan semua transaksi kas masuk dan keluar dari rekening tertentu dalam urutan kronologis, mirip seperti buku tabungan:

- Tanggal
- Keterangan transaksi
- Debit (uang masuk)
- Kredit (uang keluar)
- Saldo setelah transaksi (running balance)

Buka **Finance → Akun → klik akun → Riwayat Transaksi** untuk melihat cashbook view.

### Mencocokkan dengan Rekening Koran

Gunakan cashbook view untuk mencocokkan transaksi di Meetra dengan mutasi rekening koran bank:

1. Export riwayat transaksi akun ke Excel
2. Bandingkan dengan mutasi rekening koran dari bank
3. Tandai transaksi yang sudah cocok
4. Jika ada yang belum ada di Meetra, catat transaksi yang terlewat

---

## Pembayaran dari Customer

Pembayaran yang diterima dari customer dicatat melalui modul **Payments**, bukan langsung di Finance. Dengan cara ini, pembayaran otomatis ter-link ke invoice terkait.

Lihat panduan [Menerima Pembayaran](../payments/overview.md) untuk detailnya.

---

## Pembayaran ke Supplier

Pembayaran hutang ke supplier juga dilakukan melalui modul **Payments** agar terintegrasi dengan purchase order yang bersangkutan.

---

## Pengeluaran Operasional

Pengeluaran yang tidak terkait dengan hutang supplier dicatat langsung di **Finance → Kas Keluar**, misalnya:
- Gaji karyawan
- Sewa tempat usaha
- Tagihan listrik, air, internet
- Pembelian perlengkapan kecil
- Biaya transport dan perjalanan dinas

---

## Tips Manajemen Kas

**Pisahkan rekening bisnis dan pribadi**
Gunakan rekening bank yang berbeda untuk bisnis agar pembukuan lebih bersih dan mudah diaudit.

**Catat pengeluaran kecil sekalipun**
Pengeluaran kecil yang tidak dicatat bisa menimbulkan selisih kas yang sulit dilacak. Biasakan mencatat semua pengeluaran, bahkan yang dibayar tunai.

**Rekonsiliasi rutin**
Lakukan rekonsiliasi kas minimal sekali sebulan — cocokkan saldo di Meetra dengan saldo rekening koran dan uang kas fisik. Deteksi selisih lebih awal jauh lebih mudah daripada menyelidiki 3 bulan ke belakang.

**Simpan bukti semua transaksi**
Gunakan fitur lampiran untuk menyimpan foto nota atau screenshot bukti transfer langsung di transaksi yang bersangkutan.
