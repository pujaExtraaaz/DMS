<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tally\Accounting\VoucherType;

class RecurringVoucher extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'name',
        'voucher_type',
        'frequency',
        'day_of_month',
        'start_date',
        'end_date',
        'next_run_on',
        'narration',
        'auto_post',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'voucher_type' => VoucherType::class,
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

    public function lines(): HasMany
    {
        return $this->hasMany(RecurringVoucherLine::class)->orderBy('line_number');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
