<?php

namespace App\Domains\Inventory\Models;

use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProductBatch extends Model
{
    protected $fillable = [
        'product_id',
        'warehouse_id',
        'uom_id',
        'batch_no',
        'mfg_date',
        'expiry_date',
        'quantity',
        'unit_cost',
        'selling_price',
        'mrp',
        'source_type',
        'source_id',
    ];

    protected function casts(): array
    {
        return [
            'mfg_date' => 'date',
            'expiry_date' => 'date',
            'quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'selling_price' => 'decimal:2',
            'mrp' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
