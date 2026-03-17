<?php

namespace App\Modules\Payments\Database\Seeders;

use App\Models\User;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentSampleSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->where('email', 'superadmin@myapp.test')->first() ?? User::query()->first();
        $userId = optional($user)->id;

        $method = PaymentMethod::query()->where('code', PaymentMethod::CODE_QRIS)->first()
            ?? PaymentMethod::query()->where('code', PaymentMethod::CODE_CASH)->first();

        if (!$method) {
            $method = PaymentMethod::query()->create([
                'code' => PaymentMethod::CODE_MANUAL,
                'name' => 'Manual Demo',
                'type' => PaymentMethod::TYPE_MANUAL,
                'requires_reference' => false,
                'is_active' => true,
                'is_system' => false,
                'sort_order' => 99,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        }

        Payment::query()->updateOrCreate(
            ['payment_number' => 'PAY-DEMO-001'],
            [
                'payment_method_id' => $method->id,
                'amount' => 125000,
                'currency_code' => 'IDR',
                'paid_at' => now()->subDay(),
                'status' => Payment::STATUS_POSTED,
                'source' => Payment::SOURCE_MANUAL,
                'channel' => 'backoffice',
                'reference_number' => 'REF-DEMO-001',
                'notes' => 'Pembayaran contoh untuk modul Payments.',
                'meta' => ['seeded' => true],
                'received_by' => $userId,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]
        );
    }
}
