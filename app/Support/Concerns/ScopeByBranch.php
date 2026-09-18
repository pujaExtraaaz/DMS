<?php

namespace App\Support\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait ScopeByBranch
{
    public function scopeForUserBranch(Builder $query, ?User $user = null): Builder
    {
        $user ??= auth()->user();

        if (! $user || $user->hasRole('super-admin')) {
            return $query;
        }

        $ids = $user->allowedBranchIds();

        if ($ids === []) {
            return $query;
        }

        return $query->whereIn($this->getTable().'.branch_id', $ids);
    }
}
