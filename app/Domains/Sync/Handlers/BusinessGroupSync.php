<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Organization\Models\BusinessGroup;
use App\Domains\Sync\Support\BooksCompany;
use App\Domains\Sync\Support\SyncLinks;
use Illuminate\Database\Eloquent\Model;
use Tally\Models\ConsolidationMap;

/**
 * Sister concerns → Books ConsolidationMap between provisioned member companies.
 * First linked company is treated as parent; others as subsidiaries at 100%.
 */
class BusinessGroupSync
{
    public const KEY = 'business_group';

    public function sync(Model $model): void
    {
        if ($model instanceof BusinessGroup) {
            $this->push($model);
        }
    }

    public function push(BusinessGroup $group): void
    {
        $group->loadMissing('companies');
        $acctIds = [];
        foreach ($group->companies as $org) {
            $books = BooksCompany::forOrganization((int) $org->id);
            if ($books) {
                $acctIds[] = (int) $books->id;
            }
        }
        $acctIds = array_values(array_unique($acctIds));
        if (count($acctIds) < 2) {
            return;
        }

        $parentId = array_shift($acctIds);
        foreach ($acctIds as $subId) {
            $map = ConsolidationMap::query()->updateOrCreate(
                [
                    'parent_company_id' => $parentId,
                    'subsidiary_company_id' => $subId,
                ],
                [
                    'ownership_percent' => 100,
                    'reporting_currency' => 'INR',
                    'is_active' => (bool) $group->is_active,
                ]
            );
            // Store last map against the group for link-all visibility.
            SyncLinks::store(self::KEY, $group, $map);
        }
    }
}
