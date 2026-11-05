<?php

namespace App\Domains\Inventory\Models;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Purchasing\Models\PurchaseInwardItem;
use App\Domains\Sales\Models\Invoice;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

class ProductSerial extends Model
{
    protected $fillable = [
        'product_id',
        'warehouse_id',
        'serial_number',
        'status',
        'source_type',
        'source_id',
        'purchase_inward_item_id',
        'reserved_for_type',
        'reserved_for_id',
        'reservation_note',
        'sold_at',
        'delivered_at',
        'returned_at',
        'notes',
        'warranty_label',
        'warranty_months',
        'warranty_start_on',
        'warranty_expires_on',
        'customer_id',
        'sales_invoice_id',
    ];

    protected function casts(): array
    {
        return [
            'sold_at' => 'datetime',
            'delivered_at' => 'datetime',
            'returned_at' => 'datetime',
            'warranty_months' => 'integer',
            'warranty_start_on' => 'date',
            'warranty_expires_on' => 'date',
        ];
    }

    /**
     * Live status. The expiry date is the last covered day.
     */
    public function warrantyStatus(?CarbonInterface $on = null): ?string
    {
        if ($this->warranty_expires_on === null) {
            return null;
        }

        $day = Carbon::parse($on ?? now())->startOfDay();
        $expiry = Carbon::parse($this->warranty_expires_on)->startOfDay();

        return $day->lte($expiry) ? 'Under Warranty' : 'Out of Warranty';
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function reservedFor(): MorphTo
    {
        return $this->morphTo();
    }

    public function inwardItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseInwardItem::class, 'purchase_inward_item_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'sales_invoice_id');
    }
}
