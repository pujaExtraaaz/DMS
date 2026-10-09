<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Sync\Support\BooksCompany;
use App\Domains\Sync\Support\SyncLinks;
use App\Domains\Sync\Support\SyncNames;
use Illuminate\Database\Eloquent\Model;
use Tally\Models\Product as BooksProduct;
use Tally\Models\Unit;

class ProductSync
{
    public const KEY = 'product';

    public function __construct(private readonly UomSync $uoms) {}

    public function sync(Model $model): void
    {
        if ($model instanceof Product) {
            $this->push($model);

            return;
        }

        if ($model instanceof BooksProduct) {
            $this->pull($model);
        }
    }

    public function push(Product $product): void
    {
        if (! $product->company_id) {
            return;
        }

        $company = BooksCompany::forOrganization((int) $product->company_id);
        if (! $company) {
            return;
        }

        $uom = Uom::query()->find($product->base_uom_id);
        if (! $uom) {
            throw new \RuntimeException('Product '.$product->id.' has no unit of measure.');
        }

        $unit = $this->uoms->pushToCompany($uom, $company);
        $books = BooksProduct::query()->find(SyncLinks::acctId(self::KEY, $product));
        $name = SyncNames::unique(
            BooksProduct::query()->where('company_id', $company->id),
            'name',
            $product->name,
            $books?->id
        );
        $code = SyncNames::unique(
            BooksProduct::query()->where('company_id', $company->id),
            'code',
            SyncNames::code($product->sku ?: ('P'.$product->id)),
            $books?->id
        );

        $attributes = [
            'company_id' => $company->id,
            'product_group_id' => ($books?->product_group_id) ?: BooksCompany::generalGroup($company)->id,
            'primary_unit_id' => $unit->id,
            'name' => $name,
            'code' => $code,
            'purchase_rate' => $product->purchase_price ?? 0,
            'sales_rate' => $product->selling_price ?? 0,
            'minimum_stock' => $product->min_stock ?? 0,
            'reorder_level' => $product->reorder_level ?? 0,
            'is_active' => (bool) $product->is_active,
        ];

        $books ? $books->update($attributes) : $books = BooksProduct::query()->create($attributes);
        SyncLinks::store(self::KEY, $product, $books);
    }

    public function pull(BooksProduct $books): void
    {
        $organizationId = BooksCompany::organizationId($books->company_id);
        if (! $organizationId) {
            return;
        }

        $unit = Unit::query()->find($books->primary_unit_id);
        if (! $unit) {
            throw new \RuntimeException('Books product '.$books->id.' has no unit.');
        }

        $this->uoms->pull($unit);
        $uomId = SyncLinks::dmsId($this->uoms->entityKey($books->company_id), $unit);
        $product = Product::query()->find(SyncLinks::dmsId(self::KEY, $books));
        $sku = SyncNames::unique(
            Product::query(),
            'sku',
            SyncNames::code($books->code ?: ('B'.$books->id), 50),
            $product?->id,
            50
        );

        $attributes = [
            'company_id' => $organizationId,
            'name' => $books->name,
            'sku' => $sku,
            'serial_no' => $product?->serial_no ?: mb_substr('B'.$books->id, 0, 30),
            'base_uom_id' => $uomId,
            'purchase_price' => $books->purchase_rate ?? 0,
            'selling_price' => $books->sales_rate ?? 0,
            'min_stock' => $books->minimum_stock ?? 0,
            'reorder_level' => $books->reorder_level ?? 0,
            'is_active' => (bool) $books->is_active,
        ];

        $product ? $product->update($attributes) : $product = Product::query()->create($attributes);
        SyncLinks::store(self::KEY, $product, $books);
    }
}
