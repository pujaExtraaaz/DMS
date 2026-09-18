<?php

namespace App\Domains\Inventory\Models;

use App\Domains\Organization\Models\Warehouse;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalDeliveryChallan extends Model
{
    protected $fillable = [
        'challan_no',
        'challan_date',
        'from_warehouse_id',
        'to_warehouse_id',
        'stock_transfer_id',
        'purpose',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'challan_date' => 'date',
        ];
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
