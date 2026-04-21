# Payments — Pembayaran

Modul Payments mengelola semua pencatatan penerimaan dan pengeluaran pembayaran — baik dari customer (untuk invoice) maupun ke supplier (untuk purchase).

---

## Dua Jenis Pembayaran

| Jenis | Arah | Modul Sumber |
|-------|------|-------------|
| **Receive Payment** | Masuk dari customer | Sales (invoice) |
| **Pay Supplier** | Keluar ke supplier | Purchases |

---

## Menerima Pembayaran dari Customer

1. Buka invoice di **Sales**, atau langsung ke **Payments → Receive Payment**
2. Pilih customer dan invoice yang dibayar
3. Isi:
   - **Tanggal pembayaran**
   - **Nominal yang diterima**
   - **Akun penerima** (rekening bank atau kas yang menerima dana)
   - **Metode pembayaran** (transfer, tunai, dll)
4. Upload **bukti bayar** (screenshot transfer, slip setoran)
5. Simpan

---

## Alokasi Pembayaran

### Pembayaran Penuh (Full Payment)
Customer membayar satu invoice secara penuh — alokasi otomatis ke invoice tersebut.

### Pembayaran Sebagian (Partial Payment)
Customer membayar sebagian dari total invoice. Sisa piutang tetap tercatat dan invoice statusnya menjadi **Partially Paid**.

### Satu Pembayaran untuk Beberapa Invoice
Customer mengirim satu transfer untuk melunasi beberapa invoice sekaligus. Anda bisa mengalokasikan satu pembayaran ke beberapa invoice:

1. Di form Receive Payment, pilih beberapa invoice dari daftar
2. Isi alokasi nominal untuk masing-masing
3. Pastikan total alokasi = nominal pembayaran
4. Simpan

### Edit Alokasi Setelah Disimpan

Jika perlu mengubah alokasi yang sudah dibuat:
1. Buka detail payment
2. Klik **Edit Alokasi**
3. Sesuaikan distribusi nominal ke invoice-invoice terkait
4. Simpan

---

## Overpayment & Underpayment

### Overpayment
Customer membayar lebih dari total invoice.

Meetra mencatat selisih lebih sebagai **kredit customer** yang bisa digunakan untuk:
- Melunasi invoice berikutnya
- Dikembalikan ke customer

### Underpayment
Customer membayar kurang dari total. Invoice statusnya **Partially Paid**, sisa piutang tetap tercatat, dan bisa dilunasi kapan saja dengan pembayaran lanjutan.

---

## Bukti Pembayaran

Setiap payment mendukung lampiran bukti bayar. Upload:
- Screenshot transfer bank
- Foto slip setoran tunai
- Bukti QRIS atau e-wallet

Bukti ini tersimpan di payment dan bisa dilihat kapan saja untuk keperluan rekonsiliasi atau audit.

---

## Reconciliation Status

Setiap payment memiliki **reconciliation status** yang menunjukkan apakah pembayaran sudah dicocokkan dengan mutasi rekening bank:

| Status | Arti |
|--------|------|
| **Unreconciled** | Belum dicocokkan dengan mutasi bank |
| **Reconciled** | Sudah cocok dengan mutasi rekening |

Gunakan status ini saat melakukan rekonsiliasi bank bulanan — filter payment dengan status Unreconciled, cocokkan satu per satu dengan rekening koran.

---

## Membayar Hutang ke Supplier

1. Buka purchase di **Purchases**, atau langsung ke **Payments → Pay Supplier**
2. Pilih supplier dan purchase yang dibayar
3. Isi tanggal, nominal, akun sumber, dan metode pembayaran
4. Upload bukti jika ada
5. Simpan

Alokasi parsial dan multi-purchase dalam satu pembayaran bekerja dengan cara yang sama seperti receive payment dari customer.

---

## Jurnal Otomatis

Setiap payment otomatis menghasilkan jurnal:

| Transaksi | Debit | Kredit |
|-----------|-------|--------|
| Terima bayar dari customer | Kas / Bank | Piutang Usaha |
| Bayar ke supplier | Hutang Usaha | Kas / Bank |
