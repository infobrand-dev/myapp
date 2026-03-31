<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

trait NormalizesPgsqlBooleanAttributes
{
    public function setAttribute($key, $value)
    {
        if ($value !== null
            && method_exists($this, 'hasCast')
            && $this->hasCast($key, ['bool', 'boolean'])
            && DB::connection($this->getConnectionName())->getDriverName() === 'pgsql') {
            $normalized = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

            $value = ($normalized ?? false) ? 'true' : 'false';
        }

        return parent::setAttribute($key, $value);
    }
}
