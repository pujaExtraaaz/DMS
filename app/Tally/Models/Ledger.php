<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Tally\Accounting\Money;
use Tally\Accounting\OpeningBalanceType;
use Tally\Integration\Concerns\HasExternalReference;
use Tally\Tax\GstRegistrationType;
use Database\Factories\LedgerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Ledger extends AccountingModel
{
    /** @use HasFactory<LedgerFactory> */
    use HasExternalReference, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'account_group_id',
        'name',
        'code',
        'opening_balance',
        'opening_balance_type',
        'address',
        'state',
        'phone',
        'email',
        'gstin',
        'gst_registration_type',
        'pan',
        'credit_limit',
        'credit_days',
        'deduction_section_id',
        'is_active',
        'is_system',
        'cash_flow_class',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'opening_balance_type' => OpeningBalanceType::class,
            'credit_limit' => 'decimal:2',
            'credit_days' => 'integer',
            'gst_registration_type' => GstRegistrationType::class,
            'is_active' => 'boolean',
            'is_system' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function accountGroup(): BelongsTo
    {
        return $this->belongsTo(AccountGroup::class);
    }

    public function bankAccount(): HasOne
    {
        return $this->hasOne(BankAccount::class);
    }

    public function party(): HasOne
    {
        return $this->hasOne(Party::class);
    }

    public function deductionSection(): BelongsTo
    {
        return $this->belongsTo(DeductionSection::class);
    }

    public function openingBalanceLabel(): string
    {
        return number_format((float) $this->opening_balance, 2).' '.$this->opening_balance_type->label();
    }

    public function voucherEntries(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(VoucherEntry::class);
    }

    /**
     * Voucher lines, bills, and a non-zero opening balance keep the ledger.
     */
    public function hasAccountingEntries(): bool
    {
        if (Money::cents((string) $this->opening_balance) !== 0) {
            return true;
        }

        return $this->voucherEntries()->exists()
            || Bill::query()->where('ledger_id', $this->id)->exists();
    }

    public function canBeDeleted(): bool
    {
        return ! $this->is_system && ! $this->hasAccountingEntries();
    }

    /**
     * Cash and bank accounts, including ledgers under a subgroup of those groups.
     */
    public function isCashOrBank(): bool
    {
        return $this->belongsToGroup('CASH', 'BANK');
    }

    public function isBank(): bool
    {
        return $this->belongsToGroup('BANK');
    }

    public function isCustomer(): bool
    {
        return $this->belongsToGroup('DEBTORS');
    }

    public function isSupplier(): bool
    {
        return $this->belongsToGroup('CREDITORS');
    }

    public function isSalesAccount(): bool
    {
        return $this->belongsToGroup('SALES');
    }

    public function isPurchaseAccount(): bool
    {
        return $this->belongsToGroup('PURCHASE');
    }

    /**
     * Group membership follows the chart, including subgroups.
     * Party masters can later create ledgers in these groups without changing invoices.
     */
    public function belongsToGroup(string ...$codes): bool
    {
        $group = $this->accountGroup;
        $guard = 0;

        while ($group && $guard < 50) {
            if (in_array($group->code, $codes, true)) {
                return true;
            }

            $group = $group->parent;
            $guard++;
        }

        return false;
    }
}
