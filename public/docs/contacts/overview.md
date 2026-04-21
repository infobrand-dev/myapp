# Contacts — Customer & Supplier

Modul Contacts menyimpan data semua pihak eksternal yang berhubungan dengan bisnis Anda — customer, supplier, atau keduanya sekaligus.

---

## Jenis Kontak

**Customer** — pihak yang membeli dari Anda. Digunakan saat membuat invoice penjualan.

**Supplier** — pihak yang Anda beli darinya. Digunakan saat membuat purchase.

Satu kontak bisa berstatus customer dan supplier sekaligus — misalnya mitra bisnis yang kadang membeli dari Anda, kadang Anda beli darinya.

---

## Menambah Kontak Baru

1. Buka **Contacts → Tambah Kontak**
2. Isi informasi dasar:
   - **Nama** — nama perusahaan atau perorangan
   - **Tipe** — Customer, Supplier, atau keduanya
   - **Email** dan **nomor telepon**
3. Isi informasi tambahan sesuai kebutuhan:
   - Alamat penagihan (billing address)
   - Alamat pengiriman (shipping address) jika berbeda
   - NPWP dan data pajak (untuk customer/supplier PKP)
   - Segmen atau tag
4. Simpan

---

## Payment Term

Payment term menentukan berapa hari jatuh tempo pembayaran untuk kontak ini. Jika diisi, setiap invoice atau purchase yang dibuat untuk kontak ini akan otomatis mengisi jatuh tempo sesuai payment term.

**Contoh:** Payment term "Net 30" artinya jatuh tempo 30 hari setelah tanggal invoice.

Atur di form kontak, bagian **Payment Term**.

---

## Credit Limit

Credit limit adalah batas maksimal nilai piutang yang diperbolehkan untuk customer tertentu. Meetra akan menampilkan peringatan jika Anda membuat invoice baru yang akan melampaui credit limit customer tersebut.

Atur di form kontak, bagian **Credit Limit**.

---

## Contact Person

Untuk kontak perusahaan, Anda bisa menyimpan beberapa contact person (orang yang bisa dihubungi):

1. Buka detail kontak
2. Scroll ke bagian **Contact Person**
3. Tambahkan nama, jabatan, email, dan nomor telepon masing-masing

---

## Alamat Penagihan & Pengiriman

Setiap kontak bisa memiliki:
- **Alamat penagihan** (billing address) — muncul di invoice
- **Alamat pengiriman** (shipping address) — muncul di dokumen pengiriman

Jika alamat pengiriman berbeda dengan penagihan, centang opsi **Alamat pengiriman berbeda** di form kontak.

---

## Segmen & Tag

Anda bisa mengelompokkan kontak menggunakan segmen atau tag:

- **Contoh segmen:** Retail, Grosir, Distributor, Online, Offline
- **Contoh tag:** VIP, Langganan, Baru

Segmen dan tag berguna untuk filter laporan per kelompok customer atau supplier.

---

## Melihat Riwayat Transaksi Kontak

Di halaman detail kontak, Anda bisa melihat:
- Semua invoice penjualan ke customer ini
- Semua purchase dari supplier ini
- Total nilai transaksi dan status pembayaran

Ini berguna untuk menilai hubungan bisnis dengan kontak tertentu atau menegosiasikan diskon berdasarkan riwayat pembelian.

---

## Lihat Juga

- [Profil Pajak & NPWP](tax-profile.md)
