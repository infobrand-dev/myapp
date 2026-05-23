<?php

namespace App\Http\Requests\Concerns;

use App\Support\FeatureMode;

trait InteractsWithFeatureMode
{
    protected function featureModeProductLine(): string
    {
        return 'accounting';
    }

    protected function featureModeResolver(): FeatureMode
    {
        return app(FeatureMode::class);
    }

    protected function isAdvancedMode(): bool
    {
        return $this->featureModeResolver()->isAdvanced($this, $this->featureModeProductLine(), $this->user());
    }
}
