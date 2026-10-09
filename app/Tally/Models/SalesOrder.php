<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrder extends AccountingModel
{
    protected $fillable = [
        'company_id', 'branch_id', 'financial_year_id', 'number', 'order_date',
        'customer_ledger_id', 'reference_number', 'narration', 'subtotal',
        'discount_total', 'tax_total', 'grand_total', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'customer_ledger_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesOrderLine::class)->orderBy('line_number');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
