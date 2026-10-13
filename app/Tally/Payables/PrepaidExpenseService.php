<?php

namespace Tally\Payables;

use App\Models\User;
use Tally\Accounting\Money;
use Tally\Accounting\VoucherEngine;
use Tally\Accounting\VoucherType;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Models\PrepaidExpense;

class PrepaidExpenseService
{
    public function __construct(
        private readonly VoucherEngine $vouchers,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function save(Company $company, array $input, ?PrepaidExpense $expense = null): PrepaidExpense
    {
        $payload = [
            'branch_id' => ($input['branch_id'] ?? null) ?: null,
            'name' => (string) ($input['name'] ?? ''),
            'prepaid_ledger_id' => (int) ($input['prepaid_ledger_id'] ?? 0),
            'expense_ledger_id' => (int) ($input['expense_ledger_id'] ?? 0),
            'start_date' => (string) ($input['start_date'] ?? now()->toDateString()),
            'end_date' => (string) ($input['end_date'] ?? now()->toDateString()),
            'total_amount' => round((float) ($input['total_amount'] ?? 0), 2),
            'recognized_amount' => round((float) ($input['recognized_amount'] ?? $expense?->recognized_amount ?? 0), 2),
            'frequency' => ($input['frequency'] ?? null) ?: 'monthly',
            'status' => ($input['status'] ?? null) ?: ($expense?->status ?? 'active'),
            'source_voucher_id' => ($input['source_voucher_id'] ?? null) ?: null,
        ];

        if ($expense) {
            abort_unless($expense->company_id === $company->id, 404);
            $expense->update($payload);

            return $expense->fresh(['prepaidLedger', 'expenseLedger']);
        }

        return PrepaidExpense::query()->create($payload + [
            'company_id' => $company->id,
        ])->fresh(['prepaidLedger', 'expenseLedger']);
    }

    public function remainingAmount(PrepaidExpense $expense): string
    {
        return $expense->remainingAmount();
    }

    /**
     * Recognize one monthly slice for each active prepaid that is due on/before $asOn.
     *
     * @return list<PrepaidExpense>
     */
    public function recognizeDue(?string $asOn = null, ?User $user = null): array
    {
        $asOn ??= now()->toDateString();
        $user ??= User::query()->first();
        $done = [];

        $expenses = PrepaidExpense::query()
            ->with('company')
            ->where('status', 'active')
            ->where('start_date', '<=', $asOn)
            ->orderBy('id')
            ->get();

        foreach ($expenses as $expense) {
            if (Money::cents($expense->remainingAmount()) <= 0) {
                $expense->update(['status' => 'closed']);

                continue;
            }

            if ($this->nextRecognizeOn($expense) > $asOn) {
                continue;
            }

            $recognized = $this->recognizeOne($expense, $asOn, $user);
            if ($recognized) {
                $done[] = $recognized;
            }
        }

        return $done;
    }

    public function recognizeOne(PrepaidExpense $expense, ?string $asOn = null, ?User $user = null): ?PrepaidExpense
    {
        $asOn ??= now()->toDateString();
        $expense->loadMissing('company');

        $remaining = Money::cents($expense->remainingAmount());
        if ($remaining <= 0 || $expense->status !== 'active') {
            if ($remaining <= 0 && $expense->status === 'active') {
                $expense->update(['status' => 'closed']);
            }

            return null;
        }

        if (! $expense->prepaid_ledger_id || ! $expense->expense_ledger_id) {
            return null;
        }

        $slice = min($this->monthlySliceCents($expense), $remaining);
        if ($slice <= 0) {
            return null;
        }

        $company = $expense->company;
        $user ??= User::query()->first();
        $year = FinancialYear::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->where('start_date', '<=', $asOn)
            ->where('end_date', '>=', $asOn)
            ->first();

        $branch = $expense->branch_id
            ? $company->branches()->whereKey($expense->branch_id)->first()
            : null;
        $branch ??= $company->branches()->where('is_active', true)->orderBy('name')->first();

        if (! $year || ! $branch || ! $user) {
            return null;
        }

        $this->vouchers->save(
            $company,
            $branch,
            $year,
            $user,
            VoucherType::Journal,
            [
                'voucher_date' => $asOn,
                'narration' => 'Prepaid recognition: '.$expense->name,
                'entries' => [
                    ['ledger_id' => $expense->expense_ledger_id, 'debit' => Money::format($slice), 'credit' => '0.00'],
                    ['ledger_id' => $expense->prepaid_ledger_id, 'debit' => '0.00', 'credit' => Money::format($slice)],
                ],
            ],
            true
        );

        $recognized = Money::cents((string) $expense->recognized_amount) + $slice;
        $total = Money::cents((string) $expense->total_amount);
        $expense->update([
            'recognized_amount' => Money::format($recognized),
            'status' => $recognized >= $total ? 'closed' : 'active',
        ]);

        return $expense->fresh(['prepaidLedger', 'expenseLedger']);
    }

    private function monthlySliceCents(PrepaidExpense $expense): int
    {
        $months = max(1, (int) round(
            (strtotime($expense->end_date->toDateString()) - strtotime($expense->start_date->toDateString())) / (30 * 86400)
        ));

        $frequency = $expense->frequency ?: 'monthly';
        if ($frequency === 'quarterly') {
            $months = max(1, (int) ceil($months / 3));
        } elseif ($frequency === 'yearly') {
            $months = max(1, (int) ceil($months / 12));
        }

        return (int) round(Money::cents((string) $expense->total_amount) / $months);
    }

    private function nextRecognizeOn(PrepaidExpense $expense): string
    {
        $slice = max(1, $this->monthlySliceCents($expense));
        $recognized = Money::cents((string) $expense->recognized_amount);
        $slicesDone = intdiv($recognized, $slice);

        $stepMonths = match ($expense->frequency ?: 'monthly') {
            'quarterly' => 3,
            'yearly' => 12,
            default => 1,
        };

        return date('Y-m-d', strtotime('+'.($slicesDone * $stepMonths).' month', strtotime($expense->start_date->toDateString())));
    }
}
