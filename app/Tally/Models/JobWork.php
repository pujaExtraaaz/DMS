<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobWork extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'number',
        'issue_date',
        'vendor_ledger_id',
        'product_id',
        'issued_qty',
        'received_qty',
        'job_charges',
        'status',
        'narration',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'issued_qty' => 'decimal:4',
            'received_qty' => 'decimal:4',
            'job_charges' => 'decimal:2',
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

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'vendor_ledger_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function balanceQty(): string
    {
        return number_format((float) $this->issued_qty - (float) $this->received_qty, 4, '.', '');
    }
}
