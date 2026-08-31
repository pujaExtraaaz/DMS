<?php

namespace App\Domains\Delivery\Models;

use App\Domains\Logistics\Models\LoadSheet;
use App\Domains\Master\Models\Customer;
use App\Domains\Sales\Models\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Delivery extends Model
{
    protected $fillable = [
        'load_sheet_id',
        'customer_id',
        'invoice_id',
        'status',
    ];

    public function loadSheet(): BelongsTo
    {
        return $this->belongsTo(LoadSheet::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryItem::class);
    }
}
