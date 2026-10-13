<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockCount extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'godown_id',
        'number',
        'count_date',
        'status',
        'narration',
        'adjustment_voucher_id',
    ];

    protected function casts(): array
    {
        return [
            'count_date' => 'date',
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

    public function godown(): BelongsTo
    {
        return $this->belongsTo(Godown::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StockCountLine::class);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }
}
