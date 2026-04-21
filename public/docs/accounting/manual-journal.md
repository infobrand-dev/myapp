# Jurnal Manual

Jurnal manual digunakan untuk mencatat transaksi atau penyesuaian yang tidak bisa dilakukan melalui modul Sales, Purchases, atau Finance secara langsung — misalnya koreksi saldo, biaya yang belum ada modulnya, atau penyesuaian akhir periode.

> Fitur ini tersedia di **Mode Advanced**.

---

## Kapan Menggunakan Jurnal Manual?

- Koreksi pencatatan yang salah
- Mencatat biaya di luar transaksi rutin (contoh: biaya penyusutan aset)
- Penyesuaian saldo akhir periode (accrual, prepaid)
- Pencatatan transaksi non-kas seperti hutang modal atau piutang lain-lain

Untuk transaksi rutin seperti penjualan, pembayaran, dan pembelian, **gunakan modul yang sesuai** — jangan input manual. Jurnal otomatis dari modul-modul tersebut lebih akurat dan terintegrasi.

---

## Membuat Jurnal Manual

1. Buka **Finance → Jurnal Manual**
2. Klik **Buat Jurnal Baru**
3. Isi informasi jurnal:
   - **Tanggal** — tanggal efektif jurnal, bukan tanggal input
   - **Referensi** — nomor referensi opsional (misal: nomor memo internal)
   - **Keterangan** — deskripsi singkat tujuan jurnal ini
4. Tambahkan baris debit dan kredit:
   - Pilih **Akun** dari daftar Chart of Accounts
   - Isi **Nominal** di kolom Debit atau Kredit
   - Tambahkan keterangan per baris jika perlu
5. Pastikan **Total Debit = Total Kredit** sebelum menyimpan
6. Klik **Simpan sebagai Draft** atau langsung **Post**

---

## Status Jurnal

### Draft
Jurnal sudah tersimpan tapi belum dikunci. Anda masih bisa mengubah semua baris, nominal, dan tanggal. Jurnal draft **belum memengaruhi** laporan keuangan.

### Posted
Jurnal sudah dikunci dan mulai memengaruhi saldo akun serta laporan keuangan. Jurnal yang sudah diposting **tidak bisa diedit**.

---

## Aturan Debit & Kredit

| Jenis Akun | Saldo Normal | Menambah dengan | Mengurangi dengan |
|------------|-------------|-----------------|-------------------|
| Aset | Debit | Debit | Kredit |
| Kewajiban | Kredit | Kredit | Debit |
| Ekuitas | Kredit | Kredit | Debit |
| Pendapatan | Kredit | Kredit | Debit |
| Beban | Debit | Debit | Kredit |

**Contoh:** Mencatat beban sewa bulan ini sebesar Rp 5.000.000

| # | Akun | Debit | Kredit |
|---|------|-------|--------|
| 1 | Beban Sewa | 5.000.000 | — |
| 2 | Kas / Bank | — | 5.000.000 |

---

## Melihat Riwayat Jurnal

Semua jurnal — baik otomatis maupun manual — bisa dilihat di **Finance → Jurnal**. Gunakan filter untuk mempersempit berdasarkan:

- Rentang tanggal
- Status (draft / posted)
- Akun yang terlibat
- Referensi atau keterangan

---

## Pertanyaan Umum

**Bisakah jurnal yang sudah diposting dibatalkan?**
Jurnal posted tidak bisa diedit atau dihapus langsung. Jika ada kesalahan, buat jurnal koreksi dengan entri yang berlawanan (reversal) pada periode yang sama atau periode berikutnya.

**Apakah ada batas jumlah baris dalam satu jurnal?**
Tidak ada batas. Satu jurnal bisa memiliki banyak baris selama total debit dan kredit tetap seimbang.

**Kenapa tombol Post tidak muncul?**
Tombol Post hanya aktif jika total debit sama persis dengan total kredit. Periksa kembali nominal di setiap baris.
