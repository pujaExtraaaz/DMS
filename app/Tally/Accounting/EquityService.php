<?php

namespace Tally\Accounting;

use Tally\Models\Company;
use Tally\Models\EquityTransaction;

class EquityService
{
    /**
     * @param  array{kind: string, txn_date: string, amount: mixed, ledger_id?: ?int, voucher_id?: ?int, narration?: ?string}  $data
     */
    public function record(Company $company, array $data): EquityTransaction
    {
        return EquityTransaction::query()->create([
            'company_id' => $company->id,
            'kind' => $data['kind'],
            'txn_date' => $data['txn_date'],
            'amount' => (string) $data['amount'],
            'ledger_id' => $data['ledger_id'] ?? null,
            'voucher_id' => $data['voucher_id'] ?? null,
            'narration' => $data['narration'] ?? null,
        ]);
    }
}
