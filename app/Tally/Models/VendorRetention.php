<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorRetention extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'supplier_ledger_id',
        'invoice_id',
        'retention_percent',
        'retention_amount',
        'released_amount',
        'retention_date',
        'release_due_date',
        'kind',
        'status',
        'hold_voucher_id',
        'release_voucher_id',
        'retention_ledger_id',
    ];

    protected function casts(): array
    {
        return [
            'retention_percent' => 'decimal:2',
            'retention_amount' => 'decimal:2',
            'released_amount' => 'decimal:2',
            'retention_date' => 'date',
            'release_due_date' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'supplier_ledger_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function retentionLedger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'retention_ledger_id');
    }

    public function holdVoucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'hold_voucher_id');
    }

    public function releaseVoucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'release_voucher_id');
    }
}
