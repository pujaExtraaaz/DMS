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
        'parent_ledger_id',
        'name',
        'mailing_name',
        'alias',
        'code',
        'opening_balance',
        'opening_balance_type',
        'address',
        'contact_person',
        'state',
        'country',
        'pincode',
        'phone',
        'email',
        'gstin',
        'gst_registration_type',
        'pan',
        'credit_limit',
        'credit_days',
        'maintain_bill_wise',
        'cost_centres_applicable',
        'calculate_interest',
        'interest_rate',
        'interest_style',
        'payment_term_id',
        'deduction_section_id',
        'is_active',
        'is_system',
        'is_control_account',
        'is_intercompany',
        'intercompany_company_id',
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
            'maintain_bill_wise' => 'boolean',
            'cost_centres_applicable' => 'boolean',
            'calculate_interest' => 'boolean',
            'interest_rate' => 'decimal:4',
            'gst_registration_type' => GstRegistrationType::class,
            'is_active' => 'boolean',
            'is_system' => 'boolean',
            'is_control_account' => 'boolean',
            'is_intercompany' => 'boolean',
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

    public function parentLedger(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_ledger_id');
    }

    public function subLedgers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(self::class, 'parent_ledger_id');
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

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
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

    public function maintainsBillWise(): bool
    {
        $value = $this->getAttribute('maintain_bill_wise');

        if ($value === null) {
            return true;
        }

        return ! in_array($value, [false, 0, '0', 'false'], true);
    }

    public function costCentresApplicable(): bool
    {
        $value = $this->getAttribute('cost_centres_applicable');

        if ($value === null) {
            return false;
        }

        return ! in_array($value, [false, 0, '0', 'false'], true);
    }

    public function calculatesInterest(): bool
    {
        return ! in_array($this->calculate_interest, [null, false, 0, '0', 'false'], true);
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
