<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Tally\Accounting\OpeningBalanceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bill extends AccountingModel
{
    /**
     * A customer debit or supplier credit raised from a posted voucher,
     * or from the ledger opening balance. Paid and outstanding amounts
     * are calculated from posted allocations.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'branch_id',
        'financial_year_id',
        'ledger_id',
        'voucher_entry_id',
        'bill_number',
        'bill_date',
        'due_date',
        'original_amount',
        'side',
        'is_opening',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bill_date' => 'date',
            'due_date' => 'date',
            'original_amount' => 'decimal:2',
            'side' => OpeningBalanceType::class,
            'is_opening' => 'boolean',
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

    public function ledger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class);
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(VoucherEntry::class, 'voucher_entry_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(BillAllocation::class);
    }

    public function isActive(): bool
    {
        if ($this->is_opening) {
            return true;
        }

        $this->loadMissing('entry.voucher');

        return (bool) $this->entry?->voucher?->isPosted();
    }
}
