<?php

namespace Tally\Authorization;

use App\Models\User;
use Illuminate\Support\Facades\Schema;

final class CompanyAccess
{
    public static function allows(?User $user, int $companyId): bool
    {
        $ids = self::ids($user);

        return $ids === null || in_array($companyId, $ids, true);
    }

    /**
     * Null means the Super Admin may open every company.
     * An empty list means this user has no company assignment.
     *
     * @return list<int>|null
     */
    public static function ids(?User $user): ?array
    {
        if (! $user) {
            return [];
        }

        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['super-admin', 'client-admin'])) {
            return null;
        }

        $companyId = $user->company_id ?? null;
        if (! $companyId || ! Schema::hasTable('accounting_company_links')) {
            return [];
        }

        $acctId = \Illuminate\Support\Facades\DB::table('accounting_company_links')
            ->where('organization_company_id', $companyId)
            ->value('acct_company_id');

        return $acctId ? [(int) $acctId] : [];
    }
}
