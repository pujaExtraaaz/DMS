<?php

namespace Tally\Controls;

use Tally\Accounting\Money;
use Tally\Models\BalanceSheetReconciliation;
use Tally\Models\Company;

class BalanceSheetReconService
{
    /**
     * @param  array{ledger_id: int, as_of_date: string, gl_balance: mixed, substantiated_balance: mixed, evidence_note?: ?string, status?: string, reviewed_by?: ?int}  $data
     */
    public function upsert(Company $company, array $data): BalanceSheetReconciliation
    {
        $gl = Money::cents((string) $data['gl_balance']);
        $sub = Money::cents((string) $data['substantiated_balance']);
        $diff = $gl - $sub;

        return BalanceSheetReconciliation::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'ledger_id' => (int) $data['ledger_id'],
                'as_of_date' => $data['as_of_date'],
            ],
            [
                'gl_balance' => Money::format($gl),
                'substantiated_balance' => Money::format($sub),
                'difference' => Money::format($diff),
                'status' => $data['status'] ?? ($diff === 0 ? 'cleared' : 'open'),
                'evidence_note' => $data['evidence_note'] ?? null,
                'reviewed_by' => $data['reviewed_by'] ?? null,
            ]
        );
    }
}
