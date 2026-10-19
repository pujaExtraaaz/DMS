<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Sync\Support\BooksCompany;
use App\Domains\Sync\Support\SyncLinks;
use App\Domains\Sync\Support\SyncNames;
use Illuminate\Database\Eloquent\Model;
use Tally\Models\ProductGroup;

class BrandSync
{
    public const KEY = 'brand';

    public function sync(Model $model): void
    {
        if ($model instanceof Brand) {
            $this->push($model);
        }
    }

    public function push(Brand $brand): void
    {
        if (! $brand->company_id) {
            return;
        }

        $company = BooksCompany::forOrganization((int) $brand->company_id);
        if (! $company) {
            return;
        }

        $group = ProductGroup::query()->find(SyncLinks::acctId(self::KEY, $brand));
        $name = SyncNames::unique(
            ProductGroup::query()->where('company_id', $company->id),
            'name',
            $brand->name,
            $group?->id
        );
        $code = SyncNames::unique(
            ProductGroup::query()->where('company_id', $company->id),
            'code',
            SyncNames::code($brand->name),
            $group?->id
        );

        $attributes = [
            'company_id' => $company->id,
            'parent_id' => null,
            'name' => $name,
            'code' => $code,
            'is_active' => (bool) $brand->is_active,
        ];

        $group ? $group->update($attributes) : $group = ProductGroup::query()->create($attributes);
        SyncLinks::store(self::KEY, $brand, $group);
    }

    public function remove(Model $source): void
    {
        if (! $source instanceof Brand) {
            return;
        }
        $group = ProductGroup::query()->find(SyncLinks::acctId(self::KEY, $source));
        if ($group && ! $group->products()->exists()) {
            $group->delete();
        }
        SyncLinks::forget(self::KEY, $source);
    }
}
