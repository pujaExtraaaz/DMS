<?php

namespace Tally\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentBatch extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'number',
        'batch_date',
        'bank_ledger_id',
        'total_amount',
        'status',
        'approved_by',
        'approved_at',
        'narration',
    ];

    protected function casts(): array
    {
        return [
            'batch_date' => 'date',
            'total_amount' => 'decimal:2',
            'approved_at' => 'datetime',
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

    public function bankLedger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'bank_ledger_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PaymentBatchLine::class);
    }
}
