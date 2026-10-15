<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Catalog\Models\SubCategory;
use App\Domains\Sync\Support\BooksCompany;
use App\Domains\Sync\Support\SyncLinks;
use App\Domains\Sync\Support\SyncNames;
use Illuminate\Database\Eloquent\Model;
use Tally\Models\ProductGroup;

class SubCategorySync
{
    public const KEY = 'sub_category';

    public function __construct(private readonly CategorySync $categories) {}

    public function sync(Model $model): void
    {
        if ($model instanceof SubCategory) {
            $this->push($model);
        }
    }

    public function push(SubCategory $sub): void
    {
        $sub->loadMissing('category.brand');
        $brand = $sub->category?->brand;
        if (! $brand?->company_id || ! $sub->category) {
            return;
        }

        $company = BooksCompany::forOrganization((int) $brand->company_id);
        if (! $company) {
            return;
        }

        $this->categories->push($sub->category);
        $parentId = SyncLinks::acctId(CategorySync::KEY, $sub->category);

        $group = ProductGroup::query()->find(SyncLinks::acctId(self::KEY, $sub));
        $name = SyncNames::unique(
            ProductGroup::query()->where('company_id', $company->id),
            'name',
            $sub->name,
            $group?->id
        );
        $code = SyncNames::unique(
            ProductGroup::query()->where('company_id', $company->id),
            'code',
            SyncNames::code($sub->code ?: $sub->name),
            $group?->id
        );

        $attributes = [
            'company_id' => $company->id,
            'parent_id' => $parentId,
            'name' => $name,
            'code' => $code,
            'is_active' => (bool) $sub->is_active,
        ];

        $group ? $group->update($attributes) : $group = ProductGroup::query()->create($attributes);
        SyncLinks::store(self::KEY, $sub, $group);
    }
}
