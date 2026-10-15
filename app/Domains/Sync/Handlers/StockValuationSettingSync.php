<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Inventory\Models\StockValuationSetting;
use App\Domains\Sync\Support\BooksCompany;
use App\Domains\Sync\Support\SyncLinks;
use Illuminate\Database\Eloquent\Model;

/** Pushes DMS valuation method onto Books company.inventory_valuation_method. */
class StockValuationSettingSync
{
    public const KEY = 'stock_valuation_setting';

    public function sync(Model $model): void
    {
        if ($model instanceof StockValuationSetting) {
            $this->push($model);
        }
    }

    public function push(StockValuationSetting $setting): void
    {
        if (! $setting->is_active || ! $setting->company_id) {
            return;
        }

        $company = BooksCompany::forOrganization((int) $setting->company_id);
        if (! $company) {
            return;
        }

        $method = match (strtolower((string) $setting->method)) {
            'fifo', 'first_in_first_out' => 'fifo',
            default => 'weighted_average',
        };

        $company->update(['inventory_valuation_method' => $method]);
        SyncLinks::store(self::KEY, $setting, $company);
    }
}
