# Products — Master Produk

Modul Products menyimpan semua data master produk dan layanan yang Anda jual. Data di sini menjadi referensi untuk modul Sales, Purchases, dan Inventory.

---

## Menambah Produk Baru

1. Buka **Products → Tambah Produk**
2. Isi informasi dasar:

   | Field | Keterangan |
   |-------|-----------|
   | **Nama Produk** | Nama yang tampil di invoice dan daftar |
   | **SKU / Kode** | Kode unik produk (bisa diisi otomatis) |
   | **Harga Jual** | Harga default saat membuat invoice |
   | **Harga Beli** | Harga pokok default saat membuat purchase |
   | **Kategori** | Pengelompokan produk |

3. Isi pengaturan lanjutan sesuai kebutuhan (lihat bagian di bawah)
4. Simpan

---

## Tracking Stok

Produk bisa dikonfigurasi sebagai **stockable** (barang fisik yang perlu dipantau stoknya) atau **non-stockable** (jasa atau produk tanpa tracking stok).

Untuk produk stockable:
- Aktifkan opsi **Lacak Stok** di form produk
- Stok akan bergerak otomatis mengikuti transaksi sales dan purchases
- Anda bisa melihat stok saat ini langsung dari halaman detail produk

---

## Margin Preview

Di halaman detail produk, Meetra menampilkan **margin preview** secara real-time berdasarkan harga jual dan harga beli yang diisi:

```
Margin = Harga Jual − Harga Beli
Margin % = (Margin ÷ Harga Jual) × 100
```

Ini membantu Anda memastikan harga jual sudah memberikan margin yang wajar sebelum produk aktif dijual.

---

## Minimum Stok & Reorder Point

Untuk setiap produk stockable, Anda bisa mengatur batas stok minimum:

| Setting | Fungsi |
|---------|--------|
| **Minimum Stok** | Stok terendah yang seharusnya ada. Produk ditandai jika stok sudah di bawah nilai ini |
| **Reorder Point** | Titik di mana Anda harus mulai memesan ke supplier |

Produk yang stoknya di bawah minimum ditandai di daftar produk dan bisa difilter untuk membuat purchase dengan cepat.

Atur di form produk bagian **Pengaturan Stok**.

---

## Supplier Default

Setiap produk bisa dikonfigurasi dengan **supplier default** — supplier yang paling sering digunakan untuk membeli produk ini.

Manfaat:
- Saat membuat purchase dan memilih produk, supplier terisi otomatis
- Harga beli default diambil dari harga terakhir ke supplier tersebut
- Mengurangi potensi salah pilih supplier

Atur di form produk bagian **Supplier Default**.

---

## Riwayat Harga

Meetra menyimpan riwayat semua perubahan harga — baik harga jual maupun harga beli — lengkap dengan tanggal perubahan.

Buka **Products → detail produk → Riwayat Harga** untuk melihat:
- Tanggal perubahan harga
- Harga lama dan harga baru
- Dari supplier mana harga beli tersebut berasal

Berguna untuk menelusuri kapan harga naik dan sebesar apa, atau membandingkan harga dari beberapa supplier.

---

## Stok Awal

Untuk produk yang sudah punya stok fisik sebelum mulai menggunakan Meetra, input stok awal melalui:

1. Halaman detail produk → klik **Atur Stok Awal**, atau
2. **Inventory → Stok Awal → Tambah**

Isi jumlah stok dan harga pokok per unit agar perhitungan moving average mulai dari nilai yang benar.

---

## Varian Produk

Jika satu produk tersedia dalam beberapa variasi (ukuran, warna, tipe), Anda bisa menggunakan fitur **varian**:

- Setiap varian punya SKU, harga, dan stok sendiri
- Di invoice, Anda memilih varian spesifik saat menambahkan item

---

## Import Bulk

Untuk menambahkan banyak produk sekaligus, gunakan fitur import:
1. Buka **Products → Import**
2. Download template Excel yang disediakan
3. Isi data produk di template
4. Upload file dan konfirmasi

Template sudah mencakup kolom untuk nama, SKU, harga jual, harga beli, kategori, dan pengaturan stok.
