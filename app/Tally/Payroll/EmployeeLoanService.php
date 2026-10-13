<?php

namespace Tally\Payroll;

use Tally\Accounting\Money;
use Tally\Models\Company;
use Tally\Models\EmployeeLoan;

class EmployeeLoanService
{
    /**
     * @param  array{employee_id: int, loan_date: string, principal: mixed, interest_rate?: mixed, tenure_months?: int, status?: string}  $data
     */
    public function create(Company $company, array $data): EmployeeLoan
    {
        $principal = Money::cents((string) $data['principal']);
        $rate = (float) ($data['interest_rate'] ?? 0);
        $tenure = max(1, (int) ($data['tenure_months'] ?? 12));
        $emi = $this->emiCents($principal, $rate, $tenure);

        return EmployeeLoan::query()->create([
            'company_id' => $company->id,
            'employee_id' => (int) $data['employee_id'],
            'loan_date' => $data['loan_date'],
            'principal' => Money::format($principal),
            'interest_rate' => $rate,
            'tenure_months' => $tenure,
            'emi_amount' => Money::format($emi),
            'outstanding' => Money::format($principal),
            'status' => $data['status'] ?? 'active',
        ]);
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
