# Inventory Module

## Boundary
- `Products` menyimpan master produk: nama, SKU, barcode, brand, kategori, unit, harga dasar, varian, dan flag stockable.
- `Inventory` menyimpan lokasi stok, saldo stok, mutasi stok, opening stock, adjustment, transfer, stock card, low stock, dan audit trail.
- `Discounts`, `PointOfSale`, `Sales`, `Purchases`, `Returns`, dan `Reports` hanya menjadi sumber transaksi atau konsumen data inventory, bukan sumber kebenaran saldo stok.

## Required Dependencies
- `products`
- `users` dari core app

## Optional Dependencies
- `outlets` / `branches` / `warehouses`
- `pointofsale`
- `sales`
- `purchases`
- `returns`
- `reports`

## Products Cleanup
- Hapus `product_stocks`, `stock_locations`, `initial_stock`, `min_stock`, dan `alert_low_stock` dari `Products`.
- Filter stok dan tampilan saldo stok dipindah ke `Inventory`.
- `track_stock` tetap di `Products` sebagai penanda produk stockable atau non-stockable.
