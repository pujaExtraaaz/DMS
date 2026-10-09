<?php

namespace Tally\Support\Queries;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Inclusive date filters that compare the column directly.
 * whereDate() wraps the column and prevents an index from being used.
 */
final class DateRange
{
    public static function apply(EloquentBuilder|QueryBuilder $query, string $column, ?string $from, ?string $to, bool $dateTime = false): void
    {
        if ($from !== null && $from !== '') {
            $query->where($column, '>=', $dateTime ? $from.' 00:00:00' : $from);
        }

        if ($to !== null && $to !== '') {
            if ($dateTime) {
                $query->where($column, '<', date('Y-m-d', strtotime($to.' +1 day')).' 00:00:00');
            } else {
                $query->where($column, '<=', $to);
            }
        }
    }
}
