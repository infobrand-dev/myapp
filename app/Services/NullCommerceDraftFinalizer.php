<?php

namespace App\Services;

use App\Contracts\CommerceDraftFinalizer;

class NullCommerceDraftFinalizer implements CommerceDraftFinalizer
{
    public function finalize(object $target, array $attributes = []): object
    {
        return $target;
    }
}
