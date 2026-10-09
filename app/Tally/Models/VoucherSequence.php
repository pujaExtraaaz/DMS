<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Tally\Accounting\VoucherType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherSequence extends AccountingModel
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'branch_id',
        'financial_year_id',
        'voucher_type',
        'prefix',
        'next_number',
        'padding',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'voucher_type' => VoucherType::class,
            'next_number' => 'integer',
            'padding' => 'integer',
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

    public function format(int $number): string
    {
        return sprintf('%s-%0'.$this->padding.'d', $this->prefix, $number);
    }
}
