<?php

namespace App\Domains\Purchasing\Models;

use App\Domains\Master\Models\Customer;
use App\Domains\Organization\Models\Warehouse;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseInward extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'warehouse_id',
        'supplier_id',
        'inward_no',
        'inward_date',
        'supplier_challan_no',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'inward_date' => 'date',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'supplier_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseInwardItem::class);
    }
}
