<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Tally\Accounting\Money;
use Tally\Banking\ReconciliationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankReconciliation extends AccountingModel
{
    /**
     * A mark against an existing posted voucher line.
     * Reconciling does not post another accounting entry.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'ledger_id',
        'voucher_entry_id',
        'reference',
        'transaction_date',
        'book_amount',
        'bank_amount',
        'status',
        'reconciled_on',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'book_amount' => 'decimal:2',
            'bank_amount' => 'decimal:2',
            'status' => ReconciliationStatus::class,
            'reconciled_on' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function ledger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class);
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(VoucherEntry::class, 'voucher_entry_id');
    }

    public function difference(): string
    {
        return Money::format(Money::cents((string) $this->book_amount) - Money::cents((string) $this->bank_amount));
    }
}
