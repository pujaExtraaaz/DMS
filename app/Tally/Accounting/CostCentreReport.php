<?php

namespace Tally\Accounting;

use Tally\Models\Company;
use Tally\Models\VoucherEntry;
use Tally\Support\Queries\DateRange;

class CostCentreReport
{
    /**
     * @return list<array<string, string>>
     */
    public function rows(Company $company, string $from, string $to, ?int $branchId, ?int $categoryId = null): array
    {
        $entries = VoucherEntry::query()
            ->with(['costCentre.category', 'ledger'])
            ->whereHas('voucher', function ($query) use ($company, $from, $to, $branchId) {
                $query->where('company_id', $company->id)
                    ->where('status', VoucherStatus::Posted)
                    ->tap(fn ($query) => DateRange::apply($query, 'voucher_date', $from, $to))
                    ->when($branchId, fn ($query) => $query->where('branch_id', $branchId));
            })
            ->whereNotNull('cost_centre_id')
            ->when($categoryId, fn ($query) => $query->whereHas(
                'costCentre',
                fn ($centre) => $centre->where('cost_category_id', $categoryId)
            ))
            ->get();

        $grouped = [];

        foreach ($entries as $entry) {
            $key = (string) $entry->cost_centre_id;
            $grouped[$key] ??= [
                'centre' => $entry->costCentre?->name ?? '',
                'category' => $entry->costCentre?->category?->name ?? '—',
                'debit' => 0,
                'credit' => 0,
            ];
            $grouped[$key]['debit'] += Money::cents((string) $entry->debit);
            $grouped[$key]['credit'] += Money::cents((string) $entry->credit);
        }

        $rows = [];

        foreach ($grouped as $row) {
            $rows[] = [
                'centre' => $row['centre'],
                'category' => $row['category'],
                'debit' => Money::format($row['debit']),
                'credit' => Money::format($row['credit']),
                'net' => Money::format($row['debit'] - $row['credit']),
            ];
        }

        return $rows;
    }
}
