<?php

namespace App\Domains\Purchasing\Models;

use App\Domains\Inventory\Models\StockMovement;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\Warehouse;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ReceiptNoteReturn extends Model
{
    protected $fillable = [
        'company_id',
        'receipt_note_id',
        'return_no',
        'return_date',
        'warehouse_id',
        'reference_no',
        'reason',
        'remarks',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'return_date' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function receiptNote(): BelongsTo
    {
        return $this->belongsTo(ReceiptNote::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReceiptNoteReturnItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function inventoryMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }
}
