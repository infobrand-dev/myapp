# Sales — Penjualan

Modul Sales mengelola seluruh proses penjualan — mulai dari membuat invoice, memantau status piutang, hingga menerima pembayaran dari customer.

---

## Alur Penjualan di Meetra

```
Buat Invoice (Draft)
      ↓
  Finalize Invoice
      ↓
  Terima Pembayaran
      ↓
  Lunas / Tutup
```

Invoice yang di-finalize otomatis membuat jurnal akuntansi dan mencatat piutang ke customer.

---

## Invoice Penjualan

### Membuat Invoice Baru

1. Buka **Sales → Buat Invoice**
2. Pilih **customer** (atau tambah baru langsung dari sini)
3. Isi **tanggal invoice** dan **jatuh tempo**
4. Tambahkan item:
   - Pilih produk dari daftar, atau ketik nama produk/jasa secara manual
   - Isi kuantitas dan harga
5. Tambahkan **diskon** di level item atau di level total invoice
6. Tambahkan **pajak** jika dikenakan
7. Isi **catatan internal** (tidak muncul di invoice) atau **catatan untuk customer**
8. Lampirkan dokumen pendukung jika ada (PO customer, kontrak, dll)
9. Klik **Simpan Draft** atau langsung **Finalize**

### Status Invoice

| Status | Artinya |
|--------|---------|
| **Draft** | Invoice tersimpan, belum dikirim, belum memengaruhi piutang |
| **Finalized** | Invoice aktif, piutang tercatat, menunggu pembayaran |
| **Partially Paid** | Sudah ada pembayaran tapi belum lunas |
| **Paid** | Lunas |
| **Overdue** | Sudah lewat jatuh tempo, belum lunas |

---

## Diskon

Meetra mendukung dua jenis diskon:

**Diskon per item** — berlaku hanya untuk baris item tertentu. Isi di kolom Diskon saat menambah item.

**Diskon header** — berlaku untuk total invoice sebelum pajak. Isi di bagian bawah form invoice.

Keduanya bisa dipakai sekaligus. Urutan perhitungan: harga item → diskon item → subtotal → diskon header → pajak → total.

---

## Pajak di Invoice

Jika bisnis Anda memungut PPN atau pajak lain:
1. Pastikan master pajak sudah diatur di **Finance → Pajak**
2. Di form invoice, pilih pajak yang berlaku
3. Meetra akan menghitung dan menambahkan nominal pajak ke total invoice

---

## Memantau Piutang

### Indikator Overdue

Invoice yang sudah melewati jatuh tempo ditandai dengan label **Overdue** berwarna merah di daftar invoice. Anda bisa filter daftar invoice berdasarkan status untuk fokus ke yang perlu ditagih.

### Aging Piutang

Untuk melihat ringkasan piutang semua customer berdasarkan umur piutang (0-30 hari, 31-60 hari, dst), buka **Reports → Aging Piutang**.

---

## Retur Penjualan

Jika customer mengembalikan barang:
1. Buka invoice yang bersangkutan
2. Klik **Buat Retur**
3. Pilih item yang dikembalikan dan jumlahnya
4. Konfirmasi

Retur akan otomatis mengurangi saldo piutang dan membalikkan jurnal pendapatan untuk item yang diretur.

---

## Lampiran Dokumen

Setiap invoice bisa dilampiri file pendukung (PDF, gambar). Lampiran berguna untuk menyimpan PO dari customer, bukti pengiriman, atau kontrak yang relevan. Klik ikon **Lampiran** di halaman detail invoice.

---

## Tips

- Isi **jatuh tempo** di setiap invoice agar sistem bisa mendeteksi piutang yang sudah lewat waktu
- Gunakan **catatan internal** untuk komunikasi dengan tim, bukan untuk customer
- Jika customer sering membeli, simpan terlebih dahulu di **Contacts** dengan payment term defaultnya agar jatuh tempo terisi otomatis
