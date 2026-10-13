<?php

namespace Tally\Payroll;

use Tally\Models\Company;
use Tally\Models\PayrollProvision;

class PayrollProvisionService
{
    /**
     * @param  array{kind: string, period?: ?string, amount: mixed, expense_ledger_id?: ?int, liability_ledger_id?: ?int, status?: string}  $data
     */
    public function create(Company $company, array $data): PayrollProvision
    {
        return PayrollProvision::query()->create([
            'company_id' => $company->id,
            'kind' => $data['kind'],
            'period' => $data['period'] ?? null,
            'amount' => (string) $data['amount'],
            'expense_ledger_id' => $data['expense_ledger_id'] ?? null,
            'liability_ledger_id' => $data['liability_ledger_id'] ?? null,
            'status' => $data['status'] ?? 'open',
        ]);
    }
}
