<?php

namespace App\Modules\Payments\Database\Seeders;

use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\PaymentMethod;
use App\Support\SampleDataUserResolver;
use Illuminate\Database\Seeder;

class PaymentSampleSeeder extends Seeder
{
    private const TENANT_ID = 1;

    public function run(): void
    {
        $user = SampleDataUserResolver::resolve();
        $userId = optional($user)->id;

        $method = PaymentMethod::query()->where('tenant_id', self::TENANT_ID)->where('code', PaymentMethod::CODE_QRIS)->first()
            ?? PaymentMethod::query()->where('tenant_id', self::TENANT_ID)->where('code', PaymentMethod::CODE_CASH)->first();

        if (!$method) {
            $method = PaymentMethod::query()->create([
                'tenant_id' => self::TENANT_ID,
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
            ['tenant_id' => self::TENANT_ID, 'payment_number' => 'PAY-DEMO-001'],
            [
                'tenant_id' => self::TENANT_ID,
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




