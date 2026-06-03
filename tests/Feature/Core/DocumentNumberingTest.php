<?php

namespace Tests\Feature\Core;

use App\Models\DocumentNumberingRule;
use App\Modules\Sales\Services\SaleNumberService;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentNumberingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', [
            '--path' => 'app/Modules/Sales/Database/Migrations/2026_03_17_130000_create_sale_sequences_table.php',
            '--realpath' => false,
        ])->run();
    }

    public function test_sale_number_service_uses_document_settings_prefix_padding_and_counter(): void
    {
        TenantContext::setCurrentId(1);
        CompanyContext::setCurrentId(1);
        BranchContext::setCurrentId(null);

        DocumentNumberingRule::query()->create([
            'tenant_id' => 1,
            'company_id' => 1,
            'branch_id' => null,
            'scope_key' => DocumentNumberingRule::scopeKeyFor(),
            'document_type' => 'sale',
            'prefix' => 'INV',
            'number_format' => '{PREFIX}-{SEQ}',
            'padding' => 4,
            'next_number' => 7,
            'last_period' => '2026-03',
            'reset_period' => DocumentNumberingRule::RESET_MONTHLY,
        ]);

        $service = app(SaleNumberService::class);

        $first = $service->generate(new \DateTimeImmutable('2026-03-20 10:00:00'), 1, null);
        $second = $service->generate(new \DateTimeImmutable('2026-03-20 11:00:00'), 1, null);

        $this->assertSame('INV-0007', $first);
        $this->assertSame('INV-0008', $second);
        $this->assertDatabaseHas('document_numbering_rules', [
            'tenant_id' => 1,
            'company_id' => 1,
            'branch_id' => null,
            'document_type' => 'sale',
            'next_number' => 9,
            'last_period' => '2026-03',
        ]);
    }
}
