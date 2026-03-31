<?php

namespace App\Support;

class TenantRoleCatalog
{
    /**
     * @return array<string, array{description: string, sort_order: int}>
     */
    public static function definitions(): array
    {
        return [
            'Super-admin' => [
                'description' => 'Akses penuh ke seluruh workspace, pengaturan, user, dan semua fitur aktif.',
                'sort_order' => 10,
            ],
            'Admin' => [
                'description' => 'Mengelola operasional workspace dan fitur utama tanpa akses penuh ke kontrol platform.',
                'sort_order' => 20,
            ],
            'Customer Service' => [
                'description' => 'Menangani inbox, live chat, WhatsApp, email, contact, dan tindak lanjut CRM harian.',
                'sort_order' => 30,
            ],
            'Sales' => [
                'description' => 'Fokus pada percakapan prospek, CRM pipeline, contact, dan aktivitas penjualan.',
                'sort_order' => 40,
            ],
            'Cashier' => [
                'description' => 'Fokus pada POS, transaksi kasir, shift, checkout, dan cetak struk.',
                'sort_order' => 50,
            ],
            'Inventory Staff' => [
                'description' => 'Mengelola produk, stok, pembelian, mutasi, dan proses gudang.',
                'sort_order' => 60,
            ],
            'Finance Staff' => [
                'description' => 'Mengelola pembayaran, pencatatan keuangan, dan kategori transaksi.',
                'sort_order' => 70,
            ],
        ];
    }

    public static function description(string $roleName): ?string
    {
        return self::definitions()[$roleName]['description'] ?? null;
    }

    public static function sortOrder(string $roleName): int
    {
        return self::definitions()[$roleName]['sort_order'] ?? 999;
    }
}
