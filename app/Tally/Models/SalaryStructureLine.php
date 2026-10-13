<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryStructureLine extends AccountingModel
{
    protected $fillable = [
        'salary_structure_id',
        'pay_head_id',
        'amount',
        'percent_of_basic',
        'is_earning',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'percent_of_basic' => 'decimal:2',
            'is_earning' => 'boolean',
        ];
    }

    public function structure(): BelongsTo
    {
        return $this->belongsTo(SalaryStructure::class, 'salary_structure_id');
    }

    public function payHead(): BelongsTo
    {
        return $this->belongsTo(PayHead::class);
    }
}
