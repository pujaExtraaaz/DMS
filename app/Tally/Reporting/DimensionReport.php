<?php

namespace Tally\Reporting;

use Tally\Accounting\Money;
use Tally\Accounting\VoucherStatus;
use Tally\Models\Company;
use Tally\Models\VoucherEntry;
use Tally\Support\Queries\DateRange;

class DimensionReport
{
    /**
     * @param  'business_unit_id'|'department_id'  $column
     * @return list<array{name: string, code: string, debit: string, credit: string, net: string}>
     */
    public function rows(Company $company, string $from, string $to, ?int $branchId, string $column): array
    {
        if (! in_array($column, ['business_unit_id', 'department_id'], true)) {
            return [];
        }

        $relation = $column === 'business_unit_id' ? 'businessUnit' : 'department';

        $entries = VoucherEntry::query()
            ->with([$relation, 'ledger'])
            ->whereHas('voucher', function ($query) use ($company, $from, $to, $branchId) {
                $query->where('company_id', $company->id)
                    ->where('status', VoucherStatus::Posted)
                    ->tap(fn ($query) => DateRange::apply($query, 'voucher_date', $from, $to))
                    ->when($branchId, fn ($query) => $query->where('branch_id', $branchId));
            })
            ->whereNotNull($column)
            ->get();

        $grouped = [];

        foreach ($entries as $entry) {
            $dim = $entry->{$relation};
            $key = (string) $entry->{$column};
            $grouped[$key] ??= [
                'name' => $dim?->name ?? '—',
                'code' => $dim?->code ?? '',
                'debit' => 0,
                'credit' => 0,
            ];
            $grouped[$key]['debit'] += Money::cents((string) $entry->debit);
            $grouped[$key]['credit'] += Money::cents((string) $entry->credit);
        }

        $rows = [];

        foreach ($grouped as $row) {
            $rows[] = [
                'name' => $row['name'],
                'code' => $row['code'],
                'debit' => Money::format($row['debit']),
                'credit' => Money::format($row['credit']),
                'net' => Money::format($row['debit'] - $row['credit']),
            ];
        }

        usort($rows, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        return $rows;
    }
}
