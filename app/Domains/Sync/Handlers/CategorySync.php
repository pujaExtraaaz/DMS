<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Catalog\Models\Category;
use App\Domains\Sync\Support\BooksCompany;
use App\Domains\Sync\Support\SyncLinks;
use App\Domains\Sync\Support\SyncNames;
use Illuminate\Database\Eloquent\Model;
use Tally\Models\ProductGroup;

class CategorySync
{
    public const KEY = 'category';

    public function __construct(private readonly BrandSync $brands) {}

    public function sync(Model $model): void
    {
        if ($model instanceof Category) {
            $this->push($model);
        }
    }

    public function push(Category $category): void
    {
        $category->loadMissing('brand');
        $brand = $category->brand;
        if (! $brand?->company_id) {
            return;
        }

        $company = BooksCompany::forOrganization((int) $brand->company_id);
        if (! $company) {
            return;
        }

        $this->brands->push($brand);
        $parentId = SyncLinks::acctId(BrandSync::KEY, $brand);

        $group = ProductGroup::query()->find(SyncLinks::acctId(self::KEY, $category));
        $name = SyncNames::unique(
            ProductGroup::query()->where('company_id', $company->id),
            'name',
            $category->name,
            $group?->id
        );
        $code = SyncNames::unique(
            ProductGroup::query()->where('company_id', $company->id),
            'code',
            SyncNames::code($category->name),
            $group?->id
        );

        $attributes = [
            'company_id' => $company->id,
            'parent_id' => $parentId,
            'name' => $name,
            'code' => $code,
            'is_active' => (bool) $category->is_active,
        ];

        $group ? $group->update($attributes) : $group = ProductGroup::query()->create($attributes);
        SyncLinks::store(self::KEY, $category, $group);
    }
}
