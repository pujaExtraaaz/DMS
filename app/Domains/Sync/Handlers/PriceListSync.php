<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\PriceMaster;
use App\Domains\Master\Models\Product;
use App\Domains\Sync\Support\BooksCompany;
use App\Domains\Sync\Support\SyncLinks;
use Illuminate\Database\Eloquent\Model;
use Tally\Models\Party;
use Tally\Models\PartyPriceList;
use Tally\Models\PriceList;
use Tally\Models\PriceListLine;

/**
 * CustomerType → Books PriceList; PriceMaster rows → PriceListLine.
 * When a PriceMaster saves, rebuilds that type's list lines.
 */
class PriceListSync
{
    public const KEY = 'price_list';

    public function __construct(private readonly ProductSync $products) {}

    public function sync(Model $model): void
    {
        if ($model instanceof CustomerType) {
            $this->pushType($model);

            return;
        }

        if ($model instanceof PriceMaster) {
            $model->loadMissing('customerType');
            if ($model->customerType) {
                $this->pushType($model->customerType);
            }
        }
    }

    public function remove(Model $model): void
    {
        if ($model instanceof CustomerType) {
            $sync = SyncLinks::first(self::KEY, $model);
            if ($sync) {
                PriceList::query()->where('id', $sync->target_id)->delete();
                $sync->delete();
            }

            return;
        }

        if ($model instanceof PriceMaster) {
            $customerType = $model->customerType ?: CustomerType::find($model->customer_type_id);
            if ($customerType) {
                $this->pushType($customerType);
            }
        }
    }

    public function pushType(CustomerType $type): void
    {
        $type->loadMissing(['priceMasters.product', 'customers']);
        $product = $type->priceMasters->first()?->product
            ?? Product::query()->whereHas('priceMasters', fn ($q) => $q->where('customer_type_id', $type->id))->first();

        $organizationId = (int) ($product?->company_id
            ?: $type->customers->first()?->company_id);
        if (! $organizationId) {
            return;
        }

        $company = BooksCompany::forOrganization($organizationId);
        if (! $company) {
            return;
        }

        $list = PriceList::query()->find(SyncLinks::acctId(self::KEY, $type));
        $attributes = [
            'company_id' => $company->id,
            'name' => $type->name,
            'is_active' => (bool) $type->is_active,
        ];
        $list ? $list->update($attributes) : $list = PriceList::query()->create($attributes);
        SyncLinks::store(self::KEY, $type, $list);

        $list->lines()->delete();
        foreach ($type->priceMasters as $row) {
            if (! $row->product) {
                continue;
            }
            $this->products->push($row->product);
            $productId = SyncLinks::acctId(ProductSync::KEY, $row->product);
            if (! $productId) {
                continue;
            }
            PriceListLine::query()->create([
                'price_list_id' => $list->id,
                'product_id' => $productId,
                'rate' => $row->rate,
            ]);
        }

        foreach ($type->customers as $customer) {
            $partyId = SyncLinks::acctId(CustomerSync::KEY, $customer);
            if (! $partyId) {
                continue;
            }
            PartyPriceList::query()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'party_id' => $partyId,
                    'price_list_id' => $list->id,
                ],
                ['is_active' => true]
            );
        }
    }
}
