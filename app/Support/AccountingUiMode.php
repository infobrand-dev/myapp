<?php

namespace App\Support;

use Illuminate\Http\Request;

class AccountingUiMode
{
    public const STANDARD = FeatureMode::STANDARD;
    public const ADVANCED = FeatureMode::ADVANCED;
    public const SESSION_KEY = FeatureMode::SESSION_KEY;

    public function __construct(private readonly FeatureMode $featureMode)
    {
    }

    public function current(?Request $request = null): string
    {
        return $this->featureMode->current($request, 'accounting');
    }

    public function isAdvanced(?Request $request = null): bool
    {
        return $this->featureMode->isAdvanced($request, 'accounting');
    }

    public function set(Request $request, string $mode): string
    {
        return $this->featureMode->set($request, $mode, 'accounting');
    }
}
