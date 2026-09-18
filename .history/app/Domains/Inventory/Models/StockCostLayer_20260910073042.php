<?php

namespace App\Domains\Inventory\Models;

use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockCostLayer extends Model
{
    protected $fillable = [
        'company_id',
        'warehouse_id',
        'product_id',
        'uom_id',
        'quantity_remaining',
        'unit_cost',
        'landed_unit_cost',
        'received_on',
        'source_type',
        'source_id',
        'batch_no',
    ];

    protected function casts(): array
    {
        return [
            'quantity_remaining' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'landed_unit_cost' => 'decimal:4',
            'received_on' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function consumptions(): HasMany
    {
        return $this->hasMany(StockCostConsumption::class);
    }

    public function effectiveUnitCost(): float
    {
        return (float) ($this->landed_unit_cost ?? $this->unit_cost);
    }
}
