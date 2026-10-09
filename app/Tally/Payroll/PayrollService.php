<?php

namespace Tally\Payroll;

use Tally\Accounting\Money;
use Tally\Accounting\VoucherEngine;
use Tally\Accounting\VoucherType;
use Tally\Models\Attendance;
use Tally\Models\Company;
use Tally\Models\Employee;
use Tally\Models\PayHead;
use Tally\Models\PayrollRun;
use App\Models\User;
use Tally\Preferences\PreferenceStore;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollService
{
    public function __construct(
        private readonly VoucherEngine $vouchers,
        private readonly PreferenceStore $preferences,
    ) {}

    public function process(Company $company, User $user, PayrollRun $run): PayrollRun
    {
        if (! $this->preferences->enabled($company, 'payroll.enabled')) {
            throw ValidationException::withMessages([
                'payroll' => 'Turn on Maintain payroll in Company Features before processing salary.',
            ]);
        }

        if ($run->status === 'processed') {
            throw ValidationException::withMessages(['status' => 'This payroll is already processed.']);
        }

        $run->load(['lines.employee.ledger', 'salaryLedger']);

        if ($run->lines->isEmpty()) {
            throw ValidationException::withMessages(['lines' => 'Add at least one employee line.']);
        }

        $entries = [];
        $debit = 0;
        $deduction = 0;

        foreach ($run->lines as $line) {
            $employee = $line->employee;

            if (! $employee?->ledger_id) {
                throw ValidationException::withMessages([
                    'lines' => $employee?->name.' has no payable ledger.',
                ]);
            }

            $earn = Money::cents((string) $line->earnings);
            $ded = Money::cents((string) $line->deductions);
            $net = $earn - $ded;

            if ($net < 0) {
                throw ValidationException::withMessages(['lines' => $employee->name.' has deductions above earnings.']);
            }

            $debit += $earn;
            $deduction += $ded;
            $debit += Money::cents((string) ($line->employer_pf_amount ?? 0)) + Money::cents((string) ($line->employer_esi_amount ?? 0));
            $deduction += Money::cents((string) ($line->employer_pf_amount ?? 0)) + Money::cents((string) ($line->employer_esi_amount ?? 0));
            $entries[] = [
                'ledger_id' => $employee->ledger_id,
                'debit' => '0.00',
                'credit' => Money::format($net),
            ];
        }

        $entries[] = [
            'ledger_id' => $run->salary_ledger_id,
            'debit' => Money::format($debit),
            'credit' => '0.00',
        ];

        if ($deduction > 0) {
            if (! $run->deduction_ledger_id) {
                throw ValidationException::withMessages(['deduction_ledger_id' => 'Choose a deduction ledger when lines have deductions.']);
            }

            $entries[] = [
                'ledger_id' => $run->deduction_ledger_id,
                'debit' => '0.00',
                'credit' => Money::format($deduction),
            ];
        }

        return DB::transaction(function () use ($company, $user, $run, $entries) {
            $voucher = $this->vouchers->save(
                $company,
                $run->branch,
                $run->financialYear,
                $user,
                VoucherType::Journal,
                [
                    'voucher_date' => $run->period_end->toDateString(),
                    'narration' => 'Payroll '.$run->period_start->format('d M Y').' to '.$run->period_end->format('d M Y'),
                    'entries' => $entries,
                ],
                true,
            );

            $run->update(['status' => 'processed', 'voucher_id' => $voucher->id]);

            return $run->fresh('voucher');
        });
    }

    /**
     * Earnings stay at the monthly figure when the employee has no attendance in the period.
     * PF and ESI are added only when those payroll features are on.
     *
     * @param  list<int>  $employeeIds
     * @return list<array<string, mixed>>
     */
    public function linesFromEmployees(Company $company, array $employeeIds, ?string $from = null, ?string $to = null): array
    {
        $heads = PayHead::query()->where('company_id', $company->id)->get();
        $pfOn = $this->preferences->enabled($company, 'payroll.pf');
        $esiOn = $this->preferences->enabled($company, 'payroll.esi');
        $pfRate = (float) $this->preferences->get($company, null, 'payroll.pf_rate');
        $esiRate = (float) $this->preferences->get($company, null, 'payroll.esi_rate');
        $employerPfRate = (float) $this->preferences->get($company, null, 'payroll.pf_employer_rate');
        $employerEsiRate = (float) $this->preferences->get($company, null, 'payroll.esi_employer_rate');
        $pfCeiling = Money::cents((string) $this->preferences->get($company, null, 'payroll.pf_ceiling'));
        $esiCeiling = Money::cents((string) $this->preferences->get($company, null, 'payroll.esi_ceiling'));
        $periodDays = ($from && $to) ? max(1, (int) ((strtotime($to) - strtotime($from)) / 86400) + 1) : 0;

        return Employee::query()
            ->where('company_id', $company->id)
            ->whereIn('id', $employeeIds)
            ->get()
            ->map(function (Employee $employee) use ($heads, $pfOn, $esiOn, $pfRate, $esiRate, $employerPfRate, $employerEsiRate, $pfCeiling, $esiCeiling, $from, $to, $periodDays) {
                $earn = Money::cents((string) $employee->monthly_earnings);
                $ded = Money::cents((string) $employee->monthly_deductions);
                $payable = $periodDays;

                if ($from && $to) {
                    $rows = Attendance::query()
                        ->where('employee_id', $employee->id)
                        ->whereBetween('attendance_date', [$from, $to])
                        ->get();

                    if ($rows->isNotEmpty()) {
                        $units = 0.0;

                        foreach ($rows as $row) {
                            $units += match ($row->attendance_type) {
                                'present', 'leave' => 1,
                                'half' => 0.5,
                                default => 0,
                            };
                        }

                        $payable = (int) round($units);
                        $earn = (int) round($earn * ($units / $periodDays));
                    }
                }

                foreach ($heads as $head) {
                    if ($head->employee_id && (int) $head->employee_id !== $employee->id) {
                        continue;
                    }

                    $figure = $head->calculation === 'percent'
                        ? (int) round($earn * ((float) $head->rate_or_amount) / 100)
                        : Money::cents((string) $head->rate_or_amount);

                    if ($head->nature === 'earning') {
                        $earn += $figure;
                    } else {
                        $ded += $figure;
                    }
                }

                $pf = 0;
                $esi = 0;
                $employerPf = 0;
                $employerEsi = 0;

                if ($pfOn) {
                    $pf = (int) round(min($earn, $pfCeiling) * $pfRate / 100);
                    $employerPf = (int) round(min($earn, $pfCeiling) * $employerPfRate / 100);
                }

                if ($esiOn && $earn <= $esiCeiling) {
                    $esi = (int) round($earn * $esiRate / 100);
                    $employerEsi = (int) round($earn * $employerEsiRate / 100);
                }

                $ded += $pf + $esi;

                return [
                    'employee_id' => $employee->id,
                    'earnings' => Money::format($earn),
                    'deductions' => Money::format($ded),
                    'pf_amount' => Money::format($pf),
                    'esi_amount' => Money::format($esi),
                    'employer_pf_amount' => Money::format($employerPf),
                    'employer_esi_amount' => Money::format($employerEsi),
                    'payable_days' => $payable,
                    'net' => Money::format($earn - $ded),
                ];
            })
            ->all();
    }
}
