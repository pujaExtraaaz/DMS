<?php

namespace Tally\Payables;

use Tally\Models\Company;
use Tally\Models\EmployeeAdvance;

class EmployeeAdvanceService
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function save(Company $company, array $input, ?EmployeeAdvance $advance = null): EmployeeAdvance
    {
        $payload = [
            'employee_id' => ($input['employee_id'] ?? null) ?: null,
            'employee_ledger_id' => (int) ($input['employee_ledger_id'] ?? 0),
            'advance_date' => (string) ($input['advance_date'] ?? now()->toDateString()),
            'amount' => round((float) ($input['amount'] ?? 0), 2),
            'settled_amount' => round((float) ($input['settled_amount'] ?? $advance?->settled_amount ?? 0), 2),
            'purpose' => ($input['purpose'] ?? null) ?: null,
            'status' => ($input['status'] ?? null) ?: ($advance?->status ?? 'open'),
            'payment_voucher_id' => ($input['payment_voucher_id'] ?? null) ?: null,
        ];

        if ($advance) {
            abort_unless($advance->company_id === $company->id, 404);
            $advance->update($payload);

            return $advance->fresh(['employeeLedger']);
        }

        return EmployeeAdvance::query()->create($payload + [
            'company_id' => $company->id,
        ])->fresh(['employeeLedger']);
    }
}
