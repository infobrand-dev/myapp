<?php

namespace Tests\Unit\Sales;

use App\Modules\Sales\Models\SaleReturn;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SaleReturnRefundRequiredScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->unsignedBigInteger('company_id')->default(1);
            $table->string('return_number')->nullable();
            $table->string('status')->default(SaleReturn::STATUS_DRAFT);
            $table->string('refund_status')->default(SaleReturn::REFUND_NOT_REQUIRED);
            $table->boolean('refund_required')->default(false);
            $table->decimal('grand_total', 18, 2)->default(0);
            $table->decimal('refunded_total', 18, 2)->default(0);
            $table->decimal('refund_balance', 18, 2)->default(0);
            $table->dateTime('return_date')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('sale_returns');

        parent::tearDown();
    }

    public function test_refund_required_scope_returns_only_rows_requiring_refund(): void
    {
        SaleReturn::query()->create([
            'tenant_id' => 1,
            'company_id' => 1,
            'return_number' => 'RET-YES',
            'status' => SaleReturn::STATUS_FINALIZED,
            'refund_status' => SaleReturn::REFUND_PENDING,
            'refund_required' => true,
        ]);

        SaleReturn::query()->create([
            'tenant_id' => 1,
            'company_id' => 1,
            'return_number' => 'RET-NO',
            'status' => SaleReturn::STATUS_FINALIZED,
            'refund_status' => SaleReturn::REFUND_NOT_REQUIRED,
            'refund_required' => false,
        ]);

        $numbers = SaleReturn::query()
            ->refundRequired()
            ->orderBy('return_number')
            ->pluck('return_number')
            ->all();

        $this->assertSame(['RET-YES'], $numbers);
    }
}
