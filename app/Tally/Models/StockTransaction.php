<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Tally\Accounting\VoucherStatus;
use Tally\Inventory\StockTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class StockTransaction extends AccountingModel
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'branch_id',
        'financial_year_id',
        'type',
        'number',
        'transaction_date',
        'source_godown_id',
        'destination_godown_id',
        'narration',
        'status',
        'created_by',
        'cancelled_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => StockTransactionType::class,
            'transaction_date' => 'date',
            'status' => VoucherStatus::class,
            'cancelled_at' => 'datetime',
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

    public function sourceGodown(): BelongsTo
    {
        return $this->belongsTo(Godown::class, 'source_godown_id');
    }

    public function destinationGodown(): BelongsTo
    {
        return $this->belongsTo(Godown::class, 'destination_godown_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StockTransactionLine::class)->orderBy('line_number');
    }

    public function movements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    public function isPosted(): bool
    {
        return $this->status === VoucherStatus::Posted;
    }

    public function isCancelled(): bool
    {
        return $this->status === VoucherStatus::Cancelled;
    }
}
