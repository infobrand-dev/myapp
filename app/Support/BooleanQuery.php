<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class BooleanQuery
{
    public static function apply(Builder $query, string $column, bool $value = true): Builder
    {
        if (self::isPgsql($query)) {
            $expression = self::expression($query, $column);

            return $query->whereRaw($expression . ($value ? ' is true' : ' is false'));
        }

        return $query->where($column, $value);
    }

    public static function jsonFlag(Builder $query, string $column, string $key, bool $value = true): Builder
    {
        if (self::isPgsql($query)) {
            $qualified = $query->qualifyColumn($column);

            return $query->whereRaw(
                "(COALESCE(($qualified->>?), 'false'))::boolean is " . ($value ? 'true' : 'false'),
                [$key]
            );
        }

        return $query->where($column . '->' . $key, $value);
    }

    private static function isPgsql(Builder $query): bool
    {
        return DB::connection($query->getModel()->getConnectionName())->getDriverName() === 'pgsql';
    }

    private static function expression(Builder $query, string $column): string
    {
        if (str_contains($column, '.') || str_contains($column, '->')) {
            return $column;
        }

        return $query->qualifyColumn($column);
    }
}
