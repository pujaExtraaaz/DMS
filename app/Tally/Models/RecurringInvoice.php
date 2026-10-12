<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringInvoice extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'name',
        'customer_ledger_id',
        'account_ledger_id',
        'frequency',
        'day_of_month',
        'start_date',
        'end_date',
        'next_run_on',
        'invoice_category',
        'narration',
        'auto_post',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'day_of_month' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'next_run_on' => 'date',
            'auto_post' => 'boolean',
            'is_active' => 'boolean',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'customer_ledger_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'account_ledger_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(RecurringInvoiceLine::class)->orderBy('line_number');
    }
}
