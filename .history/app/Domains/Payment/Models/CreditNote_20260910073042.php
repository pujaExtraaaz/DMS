<?php

namespace App\Domains\Payment\Models;

use App\Domains\Master\Models\Customer;
use App\Domains\Sales\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditNote extends Model
{
    protected $fillable = [
        'credit_note_no',
        'customer_id',
        'invoice_id',
        'credit_note_date',
        'reason',
        'status',
        'subtotal',
        'tax_amount',
        'grand_total',
        'affects_stock',
        'notes',
        'created_by_name',
        'approved_by',
        'approved_by_name',
        'approved_at',
        'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'credit_note_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'affects_stock' => 'boolean',
            'approved_at' => 'datetime',
            'posted_at' => 'datetime',
        ];
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
        return $this->hasMany(CreditNoteItem::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
