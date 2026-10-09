<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Organization\Models\Warehouse;
use App\Domains\Sync\Support\BooksCompany;
use App\Domains\Sync\Support\SyncLinks;
use App\Domains\Sync\Support\SyncNames;
use Illuminate\Database\Eloquent\Model;
use Tally\Models\Godown;

class WarehouseSync
{
    public const KEY = 'warehouse';

    public function sync(Model $model): void
    {
        if ($model instanceof Warehouse) {
            $this->push($model);

            return;
        }

        if ($model instanceof Godown) {
            $this->pull($model);
        }
    }

    public function push(Warehouse $warehouse): void
    {
        $company = BooksCompany::forOrganization((int) $warehouse->company_id);
        if (! $company) {
            return;
        }

        $godown = Godown::query()->find(SyncLinks::acctId(self::KEY, $warehouse));
        $name = SyncNames::unique(
            Godown::query()->where('company_id', $company->id),
            'name',
            $warehouse->name,
            $godown?->id
        );
        $code = $warehouse->code
            ? SyncNames::unique(Godown::query()->where('company_id', $company->id), 'code', SyncNames::code($warehouse->code), $godown?->id)
            : null;

        $attributes = [
            'company_id' => $company->id,
            'name' => $name,
            'code' => $code,
            'address' => $warehouse->address,
            'is_active' => (bool) $warehouse->is_active,
        ];

        $godown ? $godown->update($attributes) : $godown = Godown::query()->create($attributes);
        SyncLinks::store(self::KEY, $warehouse, $godown);
    }

    public function pull(Godown $godown): void
    {
        $organizationId = BooksCompany::organizationId($godown->company_id);
        if (! $organizationId) {
            return;
        }

        $warehouse = Warehouse::query()->find(SyncLinks::dmsId(self::KEY, $godown));
        $code = SyncNames::unique(
            Warehouse::query(),
            'code',
            SyncNames::code($godown->code ?: $godown->name, 30),
            $warehouse?->id,
            30
        );

        $attributes = [
            'company_id' => $organizationId,
            'branch_id' => $warehouse?->branch_id,
            'name' => $godown->name,
            'code' => $code,
            'address' => $godown->address,
            'is_active' => (bool) $godown->is_active,
        ];

        if (! $warehouse) {
            $branchId = BooksCompany::branchId($organizationId);
            $dmsBranchId = null;
            $link = BooksCompany::linkRow($organizationId);
            if ($link && $link->settings) {
                $settings = is_string($link->settings) ? json_decode($link->settings, true) : (array) $link->settings;
                $dmsBranchId = array_search((int) $branchId, $settings['branches'] ?? [], true) ?: null;
            }
            $attributes['branch_id'] = $dmsBranchId ? (int) $dmsBranchId : \App\Domains\Organization\Models\Branch::query()
                ->where('company_id', $organizationId)
                ->value('id');
        }

        $warehouse ? $warehouse->update($attributes) : $warehouse = Warehouse::query()->create($attributes);
        SyncLinks::store(self::KEY, $warehouse, $godown);
    }
}
