<?php

namespace App\Domains\Sync\Support;

use Illuminate\Database\Eloquent\Builder;

final class SyncNames
{
    public static function unique(Builder $query, string $column, string $value, ?int $ignoreId = null, int $limit = 120): string
    {
        $base = trim($value) !== '' ? trim($value) : 'Untitled';
        $base = mb_substr($base, 0, $limit);
        $candidate = $base;
        $suffix = 2;

        while ($query->clone()
            ->where($column, $candidate)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $tail = ' '.$suffix;
            $candidate = mb_substr($base, 0, $limit - mb_strlen($tail)).$tail;
            $suffix++;
        }

        return $candidate;
    }

    public static function code(string $value, int $limit = 32): string
    {
        $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $value) ?: '');

        return mb_substr($code !== '' ? $code : 'NA', 0, $limit);
    }
}
