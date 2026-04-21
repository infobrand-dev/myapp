# Jurnal Manual

Jurnal manual digunakan untuk mencatat transaksi atau penyesuaian yang tidak bisa dilakukan melalui modul operasional — misalnya koreksi saldo, biaya penyusutan, atau penyesuaian akhir periode.

> Fitur ini tersedia di **Mode Advanced** → **Finance → Jurnal Manual**.

---

## Kapan Menggunakan Jurnal Manual?

Gunakan jurnal manual untuk:
- Koreksi pencatatan yang salah (tanpa menghapus jurnal lama)
- Biaya yang tidak punya modul tersendiri: penyusutan aset, amortisasi
- Penyesuaian akhir periode: accrued expense, prepaid, deferred revenue
- Pencatatan non-kas: hutang modal, piutang lain-lain, setoran modal

**Jangan** gunakan jurnal manual untuk transaksi rutin seperti penjualan, pembelian, atau pembayaran — gunakan modul yang sesuai agar integrasi antar modul tetap benar.

---

## Membuat Jurnal Manual

1. Buka **Finance → Jurnal Manual → Buat Jurnal Baru**
2. Isi header jurnal:

   | Field | Keterangan |
   |-------|-----------|
   | **Tanggal** | Tanggal efektif, bukan tanggal input |
   | **Referensi** | Nomor memo internal (opsional) |
   | **Keterangan** | Deskripsi singkat tujuan jurnal |

3. Tambahkan baris entri — minimal 2 baris (satu debit, satu kredit):

   | Field | Keterangan |
   |-------|-----------|
   | **Akun** | Pilih dari Chart of Accounts |
   | **Keterangan baris** | Deskripsi spesifik baris ini (opsional) |
   | **Debit** | Isi nominal jika baris ini debit |
   | **Kredit** | Isi nominal jika baris ini kredit |

4. Pastikan **Total Debit = Total Kredit** — tombol Post baru aktif jika sudah seimbang
5. Pilih **Simpan Draft** atau langsung **Post**

---

## Status Jurnal

### Draft
- Tersimpan tapi belum dikunci
- Masih bisa diedit: akun, nominal, tanggal, keterangan
- **Belum memengaruhi** saldo akun dan laporan keuangan
- Cocok untuk menyimpan pekerjaan yang belum selesai atau menunggu review

### Posted
- Dikunci dan mulai memengaruhi saldo akun serta semua laporan
- **Tidak bisa diedit** — untuk koreksi, buat jurnal baru dengan entri berlawanan
- Dicatat di audit trail dengan timestamp dan user yang memposting

---

## Aturan Debit & Kredit

| Jenis Akun | Untuk Menambah | Untuk Mengurangi |
|------------|---------------|-----------------|
| **Aset** (kas, piutang, persediaan) | Debit | Kredit |
| **Kewajiban** (hutang) | Kredit | Debit |
| **Ekuitas** (modal) | Kredit | Debit |
| **Pendapatan** | Kredit | Debit |
| **Beban** (biaya) | Debit | Kredit |

---

## Contoh Jurnal

### Mencatat Beban Sewa Bulanan (dibayar tunai)

| Akun | Debit | Kredit |
|------|-------|--------|
| Beban Sewa | 5.000.000 | — |
| Kas | — | 5.000.000 |

### Mencatat Penyusutan Kendaraan

| Akun | Debit | Kredit |
|------|-------|--------|
| Beban Penyusutan | 2.500.000 | — |
| Akumulasi Penyusutan Kendaraan | — | 2.500.000 |

### Koreksi Salah Kategori Beban

Misalnya beban operasional Rp 1.000.000 terlanjur masuk ke akun yang salah:

**Jurnal koreksi (membalik entri lama):**

| Akun | Debit | Kredit |
|------|-------|--------|
| Beban yang Salah | — | 1.000.000 |
| Beban yang Benar | 1.000.000 | — |

---

## Melihat Semua Jurnal

Buka **Finance → Jurnal** untuk melihat seluruh jurnal — baik otomatis maupun manual.

Filter yang tersedia:
- **Status** — Draft atau Posted
- **Periode** — rentang tanggal
- **Sumber** — manual, dari Sales, dari Purchases, dll

Klik baris jurnal untuk melihat detail entri dan link ke dokumen sumber jika ada.

---

## Pertanyaan Umum

**Bisakah jurnal posted dibatalkan?**
Tidak bisa dihapus, tapi bisa dibuat jurnal koreksi (reversal) dengan entri yang berlawanan. Ini cara yang benar secara akuntansi karena menjaga audit trail tetap bersih.

**Kenapa tombol Post tidak aktif?**
Total debit dan kredit belum sama. Periksa kembali setiap baris — pastikan tidak ada angka yang tertukar antara kolom debit dan kredit.

**Apakah ada batas jumlah baris?**
Tidak ada batas. Satu jurnal bisa memiliki banyak baris selama total debit = total kredit.
