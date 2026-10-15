<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Inventory\Models\ProductSerial;
use App\Domains\Sync\Support\BooksCompany;
use App\Domains\Sync\Support\SyncLinks;
use Illuminate\Database\Eloquent\Model;
use Tally\Models\StockSerial;

class ProductSerialSync
{
    public const KEY = 'product_serial';

    public function __construct(private readonly ProductSync $products) {}

    public function sync(Model $model): void
    {
        if ($model instanceof ProductSerial) {
            $this->push($model);
        }
    }

    public function push(ProductSerial $serial): void
    {
        $serial->loadMissing(['product', 'warehouse']);
        $product = $serial->product;
        if (! $product?->company_id) {
            return;
        }

        $company = BooksCompany::forOrganization((int) $product->company_id);
        if (! $company) {
            return;
        }

        $this->products->push($product);
        $productId = SyncLinks::acctId(ProductSync::KEY, $product);
        if (! $productId) {
            return;
        }

        $status = match (strtolower((string) $serial->status)) {
            'sold', 'delivered', 'issued' => 'sold',
            'damaged', 'scrapped' => 'scrapped',
            'reserved' => 'reserved',
            default => 'available',
        };

        $books = StockSerial::query()->find(SyncLinks::acctId(self::KEY, $serial));
        $attributes = [
            'company_id' => $company->id,
            'product_id' => $productId,
            'serial_number' => $serial->serial_number,
            'status' => $status,
        ];

        $books ? $books->update($attributes) : $books = StockSerial::query()->create($attributes);
        SyncLinks::store(self::KEY, $serial, $books);
    }

    public function remove(Model $source): void
    {
        if (! $source instanceof ProductSerial) {
            return;
        }
        $books = StockSerial::query()->find(SyncLinks::acctId(self::KEY, $source));
        $books?->delete();
        SyncLinks::forget(self::KEY, $source);
    }
}
