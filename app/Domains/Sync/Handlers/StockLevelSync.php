<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Inventory\Models\StockLevel;
use Illuminate\Database\Eloquent\Model;

/** On stock level save, ensure warehouse opening snapshot exists in Books. */
class StockLevelSync
{
    public const KEY = 'stock_level';

    public function __construct(private readonly StockOpeningSync $openings) {}

    public function sync(Model $model): void
    {
        if ($model instanceof StockLevel) {
            $model->loadMissing('warehouse');
            if ($model->warehouse) {
                $this->openings->pushWarehouse($model->warehouse);
            }
        }
    }
}
