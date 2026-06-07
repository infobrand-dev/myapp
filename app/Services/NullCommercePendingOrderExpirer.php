<?php

namespace App\Services;

use App\Contracts\CommercePendingOrderExpirer;

class NullCommercePendingOrderExpirer implements CommercePendingOrderExpirer
{
    public function expirePending(bool $dryRun = false, ?callable $reporter = null): array
    {
        return [
            'matched' => 0,
            'expired' => 0,
        ];
    }
}
