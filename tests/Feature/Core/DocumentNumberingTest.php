<?php

namespace Tests\Feature\Core;

use App\Models\DocumentSetting;
use App\Modules\Sales\Services\SaleNumberService;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentNumberingTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_number_service_uses_document_settings_prefix_padding_and_counter(): void
    {
        TenantContext::setCurrentId(1);
        CompanyContext::setCurrentId(1);
        BranchContext::setCurrentId(null);

        DocumentSetting::query()->create([
            'tenant_id' => 1,
            'company_id' => 1,
            'branch_id' => null,
            'invoice_prefix' => 'INV',
            'invoice_padding' => 4,
            'invoice_next_number' => 7,
            'invoice_last_period' => '2026-03',
            'invoice_reset_period' => 'monthly',
        ]);

        $service = app(SaleNumberService::class);

        $first = $service->generate(new \DateTimeImmutable('2026-03-20 10:00:00'), 1, null);
        $second = $service->generate(new \DateTimeImmutable('2026-03-20 11:00:00'), 1, null);

        $this->assertSame('INV-0007', $first);
        $this->assertSame('INV-0008', $second);
        $this->assertDatabaseHas('document_settings', [
            'tenant_id' => 1,
            'company_id' => 1,
            'branch_id' => null,
            'invoice_next_number' => 9,
            'invoice_last_period' => '2026-03',
        ]);
    }
}
