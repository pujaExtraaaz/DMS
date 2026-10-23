<?php

namespace Tally\Accounting;

use Tally\Models\AccountGroup;
use Tally\Models\Branch;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Models\InterestCharge;
use Tally\Models\Ledger;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Posts one journal for the interest report. Receivable interest debits the
 * party and credits Interest Received. Payable interest debits Interest Paid
 * and credits the party. The same bill is not charged twice for one as-on date.
 */
final class InterestVoucher
{
    public function __construct(private readonly VoucherEngine $vouchers) {}

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function post(Company $company, Branch $branch, FinancialYear $year, User $user, string $asOn, array $rows): \Tally\Models\Voucher
    {
        $charged = InterestCharge::query()
            ->where('company_id', $company->id)
            ->whereDate('as_on', $asOn)
            ->pluck('bill_id')
            ->map(fn ($id) => (string) $id)
            ->all();
        $interestVouchers = InterestCharge::query()
            ->where('company_id', $company->id)
            ->pluck('voucher_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $centreId = $company->usesCostCentres()
            ? $company->costCentres()->where('is_active', true)->orderBy('id')->value('id')
            : null;

        $debits = [];
        $credits = [];
        $charges = [];

        foreach ($rows as $row) {
            $billId = (string) ($row['bill_id'] ?? '');
            $ledgerId = (int) ($row['ledger_id'] ?? 0);
            $voucherId = (int) ($row['entry_voucher_id'] ?? 0);

            if ($billId === '' || $ledgerId === 0 || in_array($billId, $charged, true)) {
                continue;
            }

            if ($voucherId !== 0 && in_array($voucherId, $interestVouchers, true)) {
                continue;
            }

            try {
                $cents = Money::cents((string) ($row['interest'] ?? '0'));
            } catch (\InvalidArgumentException) {
                continue;
            }

            if ($cents <= 0) {
                continue;
            }

            if (($row['kind'] ?? '') === 'payable') {
                $credits[$ledgerId] = ($credits[$ledgerId] ?? 0) + $cents;
            } else {
                $debits[$ledgerId] = ($debits[$ledgerId] ?? 0) + $cents;
            }

            $charges[] = [
                'bill_id' => (int) $billId,
                'ledger_id' => $ledgerId,
                'kind' => ($row['kind'] ?? '') === 'payable' ? 'payable' : 'receivable',
                'amount' => Money::format($cents),
            ];
        }

        if ($charges === []) {
            throw ValidationException::withMessages([
                'interest' => 'There is no interest left to post for this date.',
            ]);
        }

        $income = $this->ledger($company, 'INT_REC', 'Interest Received', 'INDIRECT_INCOMES', OpeningBalanceType::Credit);
        $expense = $this->ledger($company, 'INT_PAID', 'Interest Paid', 'INDIRECT_EXPENSES', OpeningBalanceType::Debit);
        $lines = [];

        foreach ($debits as $ledgerId => $cents) {
            $lines[] = $this->line($ledgerId, Money::format($cents), '0', $centreId);
        }

        $debitTotal = array_sum($debits);
        $creditTotal = array_sum($credits);

        if ($debitTotal > 0) {
            $lines[] = $this->line($income->id, '0', Money::format($debitTotal), null);
        }

        if ($creditTotal > 0) {
            $lines[] = $this->line($expense->id, Money::format($creditTotal), '0', null);
        }

        foreach ($credits as $ledgerId => $cents) {
            $lines[] = $this->line($ledgerId, '0', Money::format($cents), $centreId);
        }

        return DB::transaction(function () use ($company, $branch, $year, $user, $asOn, $lines, $charges) {
            $voucher = $this->vouchers->save($company, $branch, $year, $user, VoucherType::Journal, [
                'voucher_date' => $asOn,
                'narration' => 'Interest up to '.$asOn,
                'entries' => $lines,
            ], true);

            foreach ($charges as $charge) {
                InterestCharge::query()->create([
                    'company_id' => $company->id,
                    'bill_id' => $charge['bill_id'],
                    'ledger_id' => $charge['ledger_id'],
                    'voucher_id' => $voucher->id,
                    'as_on' => $asOn,
                    'kind' => $charge['kind'],
                    'amount' => $charge['amount'],
                ]);
            }

            return $voucher;
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function line(int $ledgerId, string $debit, string $credit, mixed $centreId): array
    {
        return [
            'ledger_id' => $ledgerId,
            'debit' => $debit,
            'credit' => $credit,
            'cost_centre_id' => $centreId,
        ];
    }

    private function ledger(Company $company, string $code, string $name, string $groupCode, OpeningBalanceType $side): Ledger
    {
        $group = AccountGroup::query()->where('company_id', $company->id)->where('code', $groupCode)->first();

        if (! $group) {
            throw ValidationException::withMessages([
                'interest' => 'Account group '.$groupCode.' is missing, so interest cannot be posted.',
            ]);
        }

        $ledger = $company->ledgers()->where('code', $code)->first();

        if ($ledger) {
            if ($ledger->is_system && $ledger->maintainsBillWise()) {
                $ledger->update(['maintain_bill_wise' => false]);
            }

            return $ledger;
        }

        return $company->ledgers()->create([
            'account_group_id' => $group->id,
            'name' => $name,
            'code' => $code,
            'opening_balance' => '0.00',
            'opening_balance_type' => $side,
            'maintain_bill_wise' => false,
            'is_active' => true,
            'is_system' => true,
        ]);
    }
}