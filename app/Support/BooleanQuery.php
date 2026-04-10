<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;

class BooleanQuery
{
    public static function apply(Builder|Relation $query, string $column, bool $value = true): Builder|Relation
    {
        if (self::isPgsql($query)) {
            $expression = self::expression($query, $column);

            return $query->whereRaw($expression . ($value ? ' is true' : ' is false'));
        }

        return $query->where($column, $value);
    }

    public static function jsonFlag(Builder|Relation $query, string $column, string $key, bool $value = true): Builder|Relation
    {
        if (self::isPgsql($query)) {
            $qualified = self::toBuilder($query)->qualifyColumn($column);

            return $query->whereRaw(
                "(COALESCE(($qualified->>?), 'false'))::boolean is " . ($value ? 'true' : 'false'),
                [$key]
            );
        }

        return $query->where($column . '->' . $key, $value);
    }

    private static function isPgsql(Builder|Relation $query): bool
    {
        $builder = self::toBuilder($query);

        return DB::connection($builder->getModel()->getConnectionName())->getDriverName() === 'pgsql';
    }

    private static function expression(Builder|Relation $query, string $column): string
    {
        if (str_contains($column, '.') || str_contains($column, '->')) {
            return $column;
        }

        return self::toBuilder($query)->qualifyColumn($column);
    }

    private static function toBuilder(Builder|Relation $query): Builder
    {
        return $query instanceof Relation ? $query->getQuery() : $query;
    }
}
