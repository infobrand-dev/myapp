<?php

namespace App\Contracts;

interface CommercePendingOrderExpirer
{
    /**
     * @return array{matched:int,expired:int}
     */
    public function expirePending(bool $dryRun = false, ?callable $reporter = null): array;
}
