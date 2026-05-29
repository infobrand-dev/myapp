<?php

namespace Tests\Unit\Sales;

use App\Modules\Sales\Services\SaleSnapshotService;
use PHPUnit\Framework\TestCase;

class SaleSnapshotServiceTest extends TestCase
{
    public function test_guest_customer_payload_is_converted_to_snapshot(): void
    {
        $service = new SaleSnapshotService();

        $snapshot = $service->customerSnapshotFromPayload(null, [
            'customer_name' => 'Budi',
            'customer_email' => 'budi@example.com',
            'customer_phone' => '08123456789',
            'customer_address' => 'Jl. Mawar No. 1',
        ]);

        $this->assertSame('Budi', $snapshot['name']);
        $this->assertSame('budi@example.com', $snapshot['email']);
        $this->assertSame('08123456789', $snapshot['phone']);
        $this->assertSame('Jl. Mawar No. 1', $snapshot['address']);
        $this->assertSame('guest', $snapshot['payload']['type']);
    }

    public function test_guest_snapshot_can_preserve_existing_values_when_payload_is_empty(): void
    {
        $service = new SaleSnapshotService();

        $snapshot = $service->customerSnapshotFromPayload(null, [], [
            'name' => 'Customer Lama',
            'email' => 'lama@example.com',
            'phone' => '0800000000',
            'address' => 'Alamat lama',
            'tax_address' => 'Alamat pajak lama',
        ]);

        $this->assertSame('Customer Lama', $snapshot['name']);
        $this->assertSame('lama@example.com', $snapshot['email']);
        $this->assertSame('0800000000', $snapshot['phone']);
        $this->assertSame('Alamat lama', $snapshot['address']);
        $this->assertSame('Alamat pajak lama', $snapshot['tax_address']);
    }
}
