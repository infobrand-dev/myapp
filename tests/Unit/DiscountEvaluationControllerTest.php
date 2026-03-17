<?php

namespace Tests\Unit;

use App\Modules\Discounts\Actions\EvaluateDiscountsAction;
use App\Modules\Discounts\Actions\RecordDiscountUsageAction;
use App\Modules\Discounts\Http\Controllers\DiscountEvaluationController;
use App\Modules\Discounts\Http\Requests\RecordDiscountUsageRequest;
use App\Modules\Discounts\Support\Engine\DiscountEvaluationResult;
use Mockery;
use Tests\TestCase;

class DiscountEvaluationControllerTest extends TestCase
{
    public function test_record_endpoint_re_evaluates_payload_server_side(): void
    {
        $payload = [
            'usage_reference_type' => 'sales',
            'usage_reference_id' => 'SO-1',
            'items' => [
                ['product_id' => 1, 'quantity' => 1, 'unit_price' => 100000, 'subtotal' => 100000],
            ],
            'evaluation' => [
                'discount_total' => 999999,
            ],
        ];

        $result = new DiscountEvaluationResult(
            100000.0,
            10000.0,
            90000.0,
            [['discount_id' => 1, 'discount_amount' => 10000.0, 'line_discounts' => []]],
            [],
            []
        );

        $evaluateAction = Mockery::mock(EvaluateDiscountsAction::class);
        $evaluateAction->shouldReceive('execute')->once()->with($payload)->andReturn($result);

        $recordAction = Mockery::mock(RecordDiscountUsageAction::class);
        $recordAction->shouldReceive('execute')->once()->with($payload, $result)->andReturn([]);

        $request = Mockery::mock(RecordDiscountUsageRequest::class);
        $request->shouldReceive('validated')->once()->andReturn($payload);

        $controller = new DiscountEvaluationController($evaluateAction, $recordAction);
        $response = $controller->record($request);
        $decoded = $response->getData(true);

        $this->assertEquals(10000.0, $decoded['evaluation']['discount_total']);
        $this->assertSame('Discount usage berhasil direkam.', $decoded['message']);
    }
}
