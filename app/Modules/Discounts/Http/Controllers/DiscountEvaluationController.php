<?php

namespace App\Modules\Discounts\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Discounts\Actions\EvaluateDiscountsAction;
use App\Modules\Discounts\Actions\RecordDiscountUsageAction;
use App\Modules\Discounts\Http\Requests\EvaluateDiscountRequest;
use App\Modules\Discounts\Http\Requests\RecordDiscountUsageRequest;
use Illuminate\Http\JsonResponse;

class DiscountEvaluationController extends Controller
{
    public function __construct(
        private readonly EvaluateDiscountsAction $evaluateAction,
        private readonly RecordDiscountUsageAction $recordUsageAction,
    ) {
    }

    public function evaluate(EvaluateDiscountRequest $request): JsonResponse
    {
        $result = $this->evaluateAction->execute($request->validated());

        return response()->json($result->toArray());
    }

    public function record(RecordDiscountUsageRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $result = $this->evaluateAction->execute($payload);
        $records = $this->recordUsageAction->execute($payload, $result);

        return response()->json([
            'message' => 'Discount usage berhasil direkam.',
            'evaluation' => $result->toArray(),
            'records' => $records,
        ]);
    }
}
