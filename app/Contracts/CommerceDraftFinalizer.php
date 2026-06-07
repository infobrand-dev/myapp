<?php

namespace App\Contracts;

interface CommerceDraftFinalizer
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function finalize(object $target, array $attributes = []): object;
}
