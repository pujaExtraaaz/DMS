<?php

namespace App\Domains\Deal\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DealExpense extends Model
{
    protected $fillable = [
        'deal_id',
        'expense_type_id',
        'amount',
        'party_name',
        'notes',
        'status',
        'created_by_name',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function expenseType(): BelongsTo
    {
        return $this->belongsTo(ExpenseType::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(ExpenseApproval::class);
    }
}
