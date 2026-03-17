<?php

namespace App\Modules\Discounts\Actions;

use App\Modules\Discounts\Repositories\DiscountRepository;
use App\Modules\Discounts\Services\DiscountEngine;
use App\Modules\Discounts\Services\DiscountReferenceService;
use App\Modules\Discounts\Support\Engine\DiscountEvaluationContext;
use App\Modules\Discounts\Support\Engine\DiscountEvaluationResult;

class EvaluateDiscountsAction
{
    public function __construct(
        private readonly DiscountRepository $repository,
        private readonly DiscountReferenceService $referenceService,
        private readonly DiscountEngine $engine,
    ) {
    }

    public function execute(array $payload): DiscountEvaluationResult
    {
        $payload['items'] = $this->referenceService->hydrateCartItems($payload['items'] ?? []);
        $context = DiscountEvaluationContext::fromArray($payload);
        $discounts = $this->repository->activeForEvaluation($context);

        return $this->engine->evaluate($context, $discounts);
    }
}
