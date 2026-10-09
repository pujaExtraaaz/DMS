<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'financial_year_id',
        'number',
        'order_date',
        'supplier_ledger_id',
        'purchase_ledger_id',
        'reference_number',
        'narration',
        'subtotal',
        'discount_total',
        'delivery_charge',
        'tax_total',
        'grand_total',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'delivery_charge' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'supplier_ledger_id');
    }

    public function purchaseLedger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'purchase_ledger_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class)->orderBy('line_number');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
