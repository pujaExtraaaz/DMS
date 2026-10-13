<?php

namespace Tally\Loans;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tally\Accounting\Money;
use Tally\Accounting\VoucherEngine;
use Tally\Accounting\VoucherType;
use Tally\Models\BankAccount;
use Tally\Models\Branch;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Models\Ledger;
use Tally\Models\Loan;
use Tally\Models\LoanSchedule;
use Tally\Models\Voucher;

class LoanService
{
    public function __construct(
        private readonly VoucherEngine $vouchers,
    ) {}

    /**
     * @param  array{
     *   loan_number: string,
     *   lender_name: string,
     *   loan_type?: string,
     *   is_long_term?: bool,
     *   disbursement_date: string,
     *   principal: mixed,
     *   interest_rate?: mixed,
     *   tenure_months: int,
     *   loan_ledger_id?: ?int,
     *   interest_ledger_id?: ?int,
     *   bank_account_id?: ?int,
     *   overdraft_limit?: mixed,
     *   status?: string
     * }  $data
     */
    public function create(Company $company, array $data): Loan
    {
        return DB::transaction(function () use ($company, $data) {
            $principal = Money::cents((string) $data['principal']);
            $rate = (float) ($data['interest_rate'] ?? 0);
            $tenure = max(1, (int) $data['tenure_months']);
            $emi = $this->emiCents($principal, $rate, $tenure);

            $loan = Loan::query()->create([
                'company_id' => $company->id,
                'loan_number' => $data['loan_number'],
                'lender_name' => $data['lender_name'],
                'loan_type' => $data['loan_type'] ?? 'term',
                'is_long_term' => (bool) ($data['is_long_term'] ?? true),
                'disbursement_date' => $data['disbursement_date'],
                'principal' => Money::format($principal),
                'interest_rate' => $rate,
                'tenure_months' => $tenure,
                'emi_amount' => Money::format($emi),
                'outstanding_principal' => Money::format($principal),
                'loan_ledger_id' => $data['loan_ledger_id'] ?? null,
                'interest_ledger_id' => $data['interest_ledger_id'] ?? null,
                'bank_account_id' => $data['bank_account_id'] ?? null,
                'overdraft_limit' => $data['overdraft_limit'] ?? null,
                'status' => $data['status'] ?? 'active',
            ]);

            $this->generateSchedule($loan);

            return $loan->load('schedules');
        });
    }

    public function generateSchedule(Loan $loan): void
    {
        $loan->schedules()->delete();

        $principal = Money::cents((string) $loan->principal);
        $rate = (float) $loan->interest_rate;
        $tenure = max(1, (int) $loan->tenure_months);
        $emi = Money::cents((string) $loan->emi_amount);
        if ($emi <= 0) {
            $emi = $this->emiCents($principal, $rate, $tenure);
            $loan->update(['emi_amount' => Money::format($emi)]);
        }

        $monthlyRate = ($rate / 100) / 12;
        $outstanding = $principal;
        $start = Carbon::parse($loan->disbursement_date)->startOfDay();

        for ($i = 1; $i <= $tenure; $i++) {
            $interest = $monthlyRate > 0 ? (int) round($outstanding * $monthlyRate) : 0;
            $principalPart = $emi - $interest;

            if ($i === $tenure || $principalPart > $outstanding) {
                $principalPart = $outstanding;
                $emiThis = $principalPart + $interest;
            } else {
                $emiThis = $emi;
            }

            $outstanding = max(0, $outstanding - $principalPart);

            LoanSchedule::query()->create([
                'loan_id' => $loan->id,
                'installment_no' => $i,
                'due_date' => $start->copy()->addMonths($i)->toDateString(),
                'principal_component' => Money::format($principalPart),
                'interest_component' => Money::format($interest),
                'emi_amount' => Money::format($emiThis),
                'outstanding_after' => Money::format($outstanding),
                'is_paid' => false,
            ]);
        }
    }

    /**
     * Post loan proceeds: Dr bank, Cr loan ledger (Journal).
     */
    public function disburse(Loan $loan, User $user): Voucher
    {
        $loan->loadMissing(['company', 'bankAccount.ledger', 'loanLedger']);

        if ($loan->disbursement_voucher_id) {
            throw ValidationException::withMessages([
                'loan' => 'Loan has already been disbursed.',
            ]);
        }

        if (! $loan->loan_ledger_id) {
            throw ValidationException::withMessages([
                'loan_ledger_id' => 'Loan ledger is required before disbursement.',
            ]);
        }

        $company = $loan->company;
        $bankLedger = $this->resolveBankLedger($loan);
        $principal = Money::cents((string) $loan->principal);
        if ($principal <= 0) {
            throw ValidationException::withMessages([
                'principal' => 'Loan principal must be greater than zero.',
            ]);
        }

        $date = $loan->disbursement_date?->toDateString() ?? now()->toDateString();
        [$branch, $year] = $this->postingContext($company, $date);
        $amount = Money::format($principal);

        return DB::transaction(function () use ($loan, $company, $branch, $year, $user, $date, $bankLedger, $amount) {
            $voucher = $this->vouchers->save(
                $company,
                $branch,
                $year,
                $user,
                VoucherType::Journal,
                [
                    'voucher_date' => $date,
                    'reference_number' => $loan->loan_number,
                    'narration' => 'Loan disbursement '.$loan->loan_number,
                    'entries' => [
                        ['ledger_id' => $bankLedger->id, 'debit' => $amount, 'credit' => '0.00'],
                        ['ledger_id' => $loan->loan_ledger_id, 'debit' => '0.00', 'credit' => $amount],
                    ],
                ],
                true
            );

            $loan->update(['disbursement_voucher_id' => $voucher->id]);

            return $voucher;
        });
    }

    /**
     * Pay one EMI installment: Payment Dr loan (principal) + interest, Cr bank.
     */
    public function payInstallment(LoanSchedule $schedule, User $user, ?int $bankLedgerId = null): Voucher
    {
        $schedule->loadMissing(['loan.company', 'loan.bankAccount.ledger', 'loan.loanLedger', 'loan.interestLedger']);
        $loan = $schedule->loan;

        if (! $loan) {
            throw ValidationException::withMessages([
                'schedule' => 'Schedule is not linked to a loan.',
            ]);
        }

        if ($schedule->is_paid || $schedule->payment_voucher_id) {
            throw ValidationException::withMessages([
                'schedule' => 'Installment is already paid.',
            ]);
        }

        if (! $loan->loan_ledger_id) {
            throw ValidationException::withMessages([
                'loan_ledger_id' => 'Loan ledger is required before EMI payment.',
            ]);
        }

        $principalCents = Money::cents((string) $schedule->principal_component);
        $interestCents = Money::cents((string) $schedule->interest_component);

        if ($interestCents > 0 && ! $loan->interest_ledger_id) {
            throw ValidationException::withMessages([
                'interest_ledger_id' => 'Interest ledger is required when the installment includes interest.',
            ]);
        }

        if ($principalCents + $interestCents <= 0) {
            throw ValidationException::withMessages([
                'schedule' => 'Installment amount must be greater than zero.',
            ]);
        }

        $company = $loan->company;
        $bankLedger = $this->resolveBankLedger($loan, $bankLedgerId);
        $date = $schedule->due_date?->toDateString() ?? now()->toDateString();
        [$branch, $year] = $this->postingContext($company, $date);

        $entries = [];
        if ($principalCents > 0) {
            $entries[] = [
                'ledger_id' => $loan->loan_ledger_id,
                'debit' => Money::format($principalCents),
                'credit' => '0.00',
            ];
        }
        if ($interestCents > 0) {
            $entries[] = [
                'ledger_id' => $loan->interest_ledger_id,
                'debit' => Money::format($interestCents),
                'credit' => '0.00',
            ];
        }
        $entries[] = [
            'ledger_id' => $bankLedger->id,
            'debit' => '0.00',
            'credit' => Money::format($principalCents + $interestCents),
        ];

        return DB::transaction(function () use ($schedule, $loan, $company, $branch, $year, $user, $date, $entries, $principalCents) {
            $voucher = $this->vouchers->save(
                $company,
                $branch,
                $year,
                $user,
                VoucherType::Payment,
                [
                    'voucher_date' => $date,
                    'reference_number' => $loan->loan_number.'-EMI-'.$schedule->installment_no,
                    'narration' => 'Loan EMI '.$loan->loan_number.' #'.$schedule->installment_no,
                    'entries' => $entries,
                ],
                true
            );

            $outstanding = max(0, Money::cents((string) $loan->outstanding_principal) - $principalCents);

            $schedule->update([
                'is_paid' => true,
                'paid_date' => $date,
                'payment_voucher_id' => $voucher->id,
            ]);

            $loan->update([
                'outstanding_principal' => Money::format($outstanding),
                'status' => $outstanding <= 0 ? 'closed' : $loan->status,
            ]);

            return $voucher;
        });
    }

    private function resolveBankLedger(Loan $loan, ?int $bankLedgerId = null): Ledger
    {
        if ($bankLedgerId) {
            $ledger = Ledger::query()
                ->where('company_id', $loan->company_id)
                ->whereKey($bankLedgerId)
                ->first();
            if ($ledger) {
                return $ledger;
            }
        }

        $loan->loadMissing('bankAccount.ledger');
        if ($loan->bankAccount?->ledger) {
            return $loan->bankAccount->ledger;
        }

        $fromAccount = BankAccount::query()
            ->with('ledger')
            ->where('company_id', $loan->company_id)
            ->orderBy('id')
            ->first();
        if ($fromAccount?->ledger) {
            return $fromAccount->ledger;
        }

        $bankGroup = $loan->company->accountGroups()->where('code', 'BANK')->first();
        if ($bankGroup) {
            $ledger = Ledger::query()
                ->where('company_id', $loan->company_id)
                ->where('account_group_id', $bankGroup->id)
                ->where('is_active', true)
                ->orderBy('id')
                ->first();
            if ($ledger) {
                return $ledger;
            }
        }

        throw ValidationException::withMessages([
            'bank_account_id' => 'A bank ledger is required for loan posting.',
        ]);
    }

    /**
     * @return array{0: Branch, 1: FinancialYear}
     */
    private function postingContext(Company $company, string $date): array
    {
        $year = FinancialYear::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first()
            ?? $company->financialYears()->where('is_active', true)->orderByDesc('start_date')->first();

        $branch = $company->branches()->where('is_active', true)->orderBy('id')->first();

        if (! $year || ! $branch) {
            throw ValidationException::withMessages([
                'loan' => 'Active branch and financial year are required to post loan vouchers.',
            ]);
        }

        return [$branch, $year];
    }

    private function emiCents(int $principalCents, float $annualRatePercent, int $tenureMonths): int
    {
        if ($tenureMonths <= 0 || $principalCents <= 0) {
            return 0;
        }

        if ($annualRatePercent <= 0) {
            return (int) round($principalCents / $tenureMonths);
        }

        $r = ($annualRatePercent / 100) / 12;
        $factor = pow(1 + $r, $tenureMonths);
        $emi = $principalCents * $r * $factor / ($factor - 1);

        return (int) round($emi);
    }
}
