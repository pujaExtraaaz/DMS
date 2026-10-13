<?php

namespace Tally\Reporting;

use Tally\Accounting\Money;
use Tally\Models\Company;
use Tally\Models\CostCentre;

/**
 * Stub: stores an allocation note and returns allocated rows by cost centre.
 */
class CostAllocationService
{
    /**
     * @param  list<array{cost_centre_id: int, amount: mixed, note?: string}>  $allocations
     * @return array{note: string, rows: list<array{cost_centre_id: int, name: string, amount: string, note: string}>}
     */
    public function allocate(Company $company, string $note, array $allocations): array
    {
        $rows = [];
        foreach ($allocations as $row) {
            $centre = CostCentre::query()
                ->where('company_id', $company->id)
                ->whereKey((int) $row['cost_centre_id'])
                ->first();

            $rows[] = [
                'cost_centre_id' => (int) $row['cost_centre_id'],
                'name' => $centre?->name ?? '—',
                'amount' => Money::format(Money::cents((string) ($row['amount'] ?? 0))),
                'note' => (string) ($row['note'] ?? $note),
            ];
        }

        return [
            'note' => $note,
            'rows' => $rows,
        ];
    }
}
