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
                $query->orderBy($handler, $direction);
            }
        } else {
            $query->orderBy($resolvedSort, $direction);
        }

        return [
            'sort' => $resolvedSort,
            'direction' => $direction,
            0 => $resolvedSort,
            1 => $direction,
        ];
    }

    /**
     * Apply case-insensitive multi-column search with whitespace trimming.
     *
     * @param Builder $query
     * @param string|null $term
     * @param array $columns Direct columns on the main model
     * @param array $relationColumns Relations mapped to their columns, e.g. ['customer' => ['name', 'phone']]
     * @return void
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

        $query->where(function (Builder $q) use ($clean, $columns, $relationColumns) {
            $first = true;
            foreach ($columns as $column) {
                if ($first) {
                    $q->where($column, 'like', "%{$clean}%");
                    $first = false;
                } else {
                    $q->orWhere($column, 'like', "%{$clean}%");
                }
            }

            foreach ($relationColumns as $relation => $relCols) {
                $cols = (array) $relCols;
                if ($first) {
                    $q->whereHas($relation, function (Builder $rq) use ($clean, $cols) {
                        $rq->where(function (Builder $subQ) use ($clean, $cols) {
                            foreach ($cols as $idx => $c) {
                                if ($idx === 0) {
                                    $subQ->where($c, 'like', "%{$clean}%");
                                } else {
                                    $subQ->orWhere($c, 'like', "%{$clean}%");
                                }
                            }
                        });
                    });
                    $first = false;
                } else {
                    $q->orWhereHas($relation, function (Builder $rq) use ($clean, $cols) {
                        $rq->where(function (Builder $subQ) use ($clean, $cols) {
                            foreach ($cols as $idx => $c) {
                                if ($idx === 0) {
                                    $subQ->where($c, 'like', "%{$clean}%");
                                } else {
                                    $subQ->orWhere($c, 'like', "%{$clean}%");
                                }
                            }
                        });
                    });
                }
            }
        });
    }
}
