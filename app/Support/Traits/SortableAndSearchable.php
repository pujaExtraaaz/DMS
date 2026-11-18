<?php

namespace App\Support\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait SortableAndSearchable
{
    /**
     * Apply sorting to an Eloquent query with an allowed columns allowlist.
     *
     * @param Builder $query
     * @param Request $request
     * @param array $allowedSorts Associative [key => db_column|callback] or flat list [col1, col2]
     * @param string $defaultSort
     * @param string $defaultDirection 'asc' or 'desc'
     * @return array [sort, direction]
     */
    protected function applySorting(
        Builder $query,
        Request $request,
        array $allowedSorts,
        string $defaultSort = 'name',
        string $defaultDirection = 'asc'
    ): array {
        $sortParam = $request->input('sort');
        $directionParam = strtolower((string) $request->input('direction', ''));

        $isAllowed = false;
        $resolvedSort = $defaultSort;

        // Check if $allowedSorts is associative or sequential
        if (array_is_list($allowedSorts)) {
            if (in_array($sortParam, $allowedSorts, true)) {
                $isAllowed = true;
                $resolvedSort = $sortParam;
            }
        } else {
            if ($sortParam && array_key_exists($sortParam, $allowedSorts)) {
                $isAllowed = true;
                $resolvedSort = $sortParam;
            }
        }

        if (! $isAllowed) {
            $resolvedSort = $defaultSort;
        }

        $direction = in_array($directionParam, ['asc', 'desc'], true)
            ? $directionParam
            : $defaultDirection;

        // Apply ordering to the query
        if (! array_is_list($allowedSorts) && isset($allowedSorts[$resolvedSort])) {
            $handler = $allowedSorts[$resolvedSort];
            if (is_callable($handler)) {
                $handler($query, $direction);
            } elseif (is_string($handler)) {
                $this->applyOrderWithTieBreaker($query, $handler, $direction);
            }
        } else {
            $this->applyOrderWithTieBreaker($query, $resolvedSort, $direction);
        }

        return [
            'sort' => $resolvedSort,
            'direction' => $direction,
            0 => $resolvedSort,
            1 => $direction,
        ];
    }

    /**
     * Apply case-insensitive, whitespace-trimmed ordering for text columns,
     * standard ordering for dates/numerics, with a deterministic secondary tie-breaker.
     *
     * @param Builder $query
     * @param string $column
     * @param string $direction
     * @return void
     */
    protected function applyOrderWithTieBreaker(Builder $query, string $column, string $direction): void
    {
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';
        $pureCol = str_contains($column, '.') ? substr(strrchr($column, '.'), 1) : $column;
        $pureColLower = strtolower($pureCol);

        // Check if the column is typically numeric, date, or binary/boolean
        $isNonText = $pureColLower === 'id'
            || str_ends_with($pureColLower, '_id')
            || str_ends_with($pureColLower, '_at')
            || str_ends_with($pureColLower, '_date')
            || str_ends_with($pureColLower, '_on')
            || in_array($pureColLower, [
                'created_at', 'updated_at', 'deleted_at', 'starts_on', 'ends_on',
                'due_date', 'po_date', 'invoice_date', 'payment_date', 'date',
                'amount', 'total', 'grand_total', 'subtotal', 'rate', 'qty',
                'quantity', 'price', 'balance', 'discount', 'tax', 'tax_amount',
                'credit', 'debit', 'order_index', 'sort_order', 'priority', 'status',
                'is_active', 'active', 'is_current', 'is_closed', 'is_default'
            ], true)
            || (bool) preg_match('/(_amount|_total|_rate|_qty|_price|_count|_num)$/', $pureColLower);

        if (! $isNonText && preg_match('/^[a-zA-Z0-9_\.]+$/', $column)) {
            $query->orderByRaw("LOWER(TRIM({$column})) {$direction}");
        } else {
            $query->orderBy($column, $direction);
        }

        // Apply secondary tie-breaker if not already sorting by primary key
        try {
            $keyName = $query->getModel()->getQualifiedKeyName();
            if ($keyName && $column !== $keyName && $pureCol !== $query->getModel()->getKeyName()) {
                $query->orderBy($keyName, 'asc');
            }
        } catch (\Throwable $e) {
            // Ignore if model or key is not available
        }
    }

    /**
     * Apply case-insensitive multi-column prefix search with whitespace trimming.
     * Typing "S" matches values starting with S; "So" matches starting with So; and so on.
     *
     * @param  Builder  $query
     * @param  string|null  $term
     * @param  array  $columns  Direct columns on the main model
     * @param  array  $relationColumns  Relations mapped to their columns, e.g. ['customer' => ['name', 'phone']]
     */
    protected function applySearch(
        Builder $query,
        ?string $term,
        array $columns,
        array $relationColumns = []
    ): void {
        $clean = trim((string) $term);
        if ($clean === '') {
            return;
        }

        // Escape LIKE wildcards so user input is treated literally as a prefix.
        $prefix = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $clean).'%';

        $query->where(function (Builder $q) use ($prefix, $columns, $relationColumns) {
            $first = true;
            foreach ($columns as $column) {
                if ($first) {
                    $q->where($column, 'like', $prefix);
                    $first = false;
                } else {
                    $q->orWhere($column, 'like', $prefix);
                }
            }

            foreach ($relationColumns as $relation => $relCols) {
                $cols = (array) $relCols;
                if ($first) {
                    $q->whereHas($relation, function (Builder $rq) use ($prefix, $cols) {
                        $rq->where(function (Builder $subQ) use ($prefix, $cols) {
                            foreach ($cols as $idx => $c) {
                                if ($idx === 0) {
                                    $subQ->where($c, 'like', $prefix);
                                } else {
                                    $subQ->orWhere($c, 'like', $prefix);
                                }
                            }
                        });
                    });
                    $first = false;
                } else {
                    $q->orWhereHas($relation, function (Builder $rq) use ($prefix, $cols) {
                        $rq->where(function (Builder $subQ) use ($prefix, $cols) {
                            foreach ($cols as $idx => $c) {
                                if ($idx === 0) {
                                    $subQ->where($c, 'like', $prefix);
                                } else {
                                    $subQ->orWhere($c, 'like', $prefix);
                                }
                            }
                        });
                    });
                }
            }
        });
    }
}
