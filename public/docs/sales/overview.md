# Sales — Penjualan & Invoice

Modul Sales mengelola seluruh siklus penjualan — dari membuat invoice, memantau status piutang, menerima pembayaran, hingga retur barang.

---

## Alur Penjualan

```
Buat Invoice  →  Finalize  →  Terima Pembayaran  →  Lunas
   (Draft)                        (Payments)
                    ↓
           Auto Journal di GL:
           Debit Piutang Usaha
           Kredit Pendapatan Penjualan
```

Invoice yang di-finalize langsung mencatat piutang dan membuat jurnal akuntansi secara otomatis.

---

## Membuat Invoice

1. Buka **Sales → Buat Invoice**
2. Pilih **customer** dari daftar Contacts, atau tambah customer baru langsung dari sini
3. Isi **tanggal invoice** dan **jatuh tempo** (due date)
4. Tambahkan item:
   - Pilih produk dari daftar atau ketik nama produk/jasa manual
   - Isi kuantitas dan harga satuan
   - Tambahkan diskon per item jika ada
5. Isi informasi tambahan (lihat bagian di bawah)
6. Pilih **Simpan Draft** atau langsung **Finalize**

---

## Diskon

Meetra mendukung diskon di dua level sekaligus:

**Diskon per item** — berlaku untuk satu baris produk. Isi di kolom Diskon saat menambahkan item. Bisa berupa persentase (%) atau nominal (Rp).

**Diskon header** — berlaku untuk total invoice sebelum pajak. Isi di bagian bawah form.

Urutan perhitungan:
```
Harga Item × Qty  →  Diskon Item  →  Subtotal
Subtotal semua item  →  Diskon Header  →  Sebelum Pajak
Sebelum Pajak  →  + Pajak  →  Total Invoice
```

---

## Pajak

Untuk menambahkan pajak ke invoice:
1. Pastikan master pajak sudah diatur di **Finance → Master Pajak**
2. Di form invoice, pilih pajak yang berlaku di bagian **Pajak**
3. Meetra menghitung dan menampilkan nominal pajak secara otomatis

Pajak berlaku di level header (semua item kena pajak yang sama) atau bisa berbeda per item.

---

## Catatan

Invoice mendukung dua jenis catatan:

| Jenis | Terlihat oleh | Gunakan untuk |
|-------|--------------|--------------|
| **Catatan untuk Customer** | Customer (muncul di invoice) | Instruksi pengiriman, syarat pembayaran, pesan khusus |
| **Catatan Internal** | Tim internal saja | Koordinasi internal, konteks order, reminder |

---

## Lampiran

Setiap invoice bisa dilampiri file pendukung (PDF, gambar, maksimal beberapa MB per file):
- PO dari customer
- Kontrak atau perjanjian
- Bukti pengiriman

Klik ikon **Lampiran** di halaman detail invoice untuk menambah atau melihat file.

---

## Status Invoice

| Status | Arti | Aksi yang Tersedia |
|--------|------|--------------------|
| **Draft** | Tersimpan, belum aktif | Edit, Finalize, Hapus |
| **Finalized** | Aktif, piutang tercatat | Terima Pembayaran, Buat Retur |
| **Partially Paid** | Dibayar sebagian | Terima Pembayaran Lanjutan |
| **Paid** | Lunas | Lihat Detail |
| **Overdue** | Lewat jatuh tempo, belum lunas | Terima Pembayaran, Kirim Pengingat |

---

## Memantau Piutang

### Indikator Overdue

Invoice yang melewati jatuh tempo ditandai label **Overdue** berwarna merah di daftar. Filter daftar invoice dengan status **Overdue** untuk fokus ke tagihan yang perlu dikejar.

### Due Date Otomatis

Jika customer memiliki **Payment Term** yang diatur di Contacts (misal: Net 30), jatuh tempo invoice akan terisi otomatis saat customer dipilih — tidak perlu isi manual setiap kali.

### Aging Piutang

Untuk melihat semua piutang per customer dikelompokkan berdasarkan umurnya, buka **Reports → Aging Piutang**.

---

## Retur Penjualan

Jika customer mengembalikan barang atau ada pembatalan sebagian:

1. Buka detail invoice yang bersangkutan
2. Klik **Buat Retur**
3. Pilih item yang diretur dan jumlahnya
4. Konfirmasi

Efek retur:
- Saldo piutang berkurang
- Stok barang bertambah kembali (jika produk stockable)
- Jurnal akuntansi dibuat otomatis untuk membalik pendapatan dan piutang

---

## Audit Trail

Setiap perubahan status invoice — dari draft ke finalized, dari unpaid ke paid, retur — dicatat di **Audit Trail** dengan timestamp dan nama user yang melakukan aksi. Buka di **Mode Advanced** untuk melihat detail lengkapnya.
