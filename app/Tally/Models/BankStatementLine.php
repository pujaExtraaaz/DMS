<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankStatementLine extends AccountingModel
{
    protected $fillable = [
        'company_id', 'bank_account_id', 'statement_date', 'value_date', 'narration',
        'debit', 'credit', 'reference', 'transaction_id', 'fingerprint', 'voucher_entry_id',
    ];

    protected function casts(): array
    {
        return [
            'statement_date' => 'date',
            'value_date' => 'date',
            'debit' => 'decimal:2',
            'credit' => 'decimal:2',
        ];
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function voucherEntry(): BelongsTo
    {
        return $this->belongsTo(VoucherEntry::class);
    }
}
