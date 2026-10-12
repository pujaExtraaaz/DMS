<?php

namespace Tally\Receivables;

use Tally\Accounting\BillAllocationService;
use Tally\Accounting\BillService;
use Tally\Accounting\Money;
use Tally\Accounting\OpeningBalanceType;
use Tally\Models\Bill;
use Tally\Models\Company;
use Tally\Models\Ledger;
use Tally\Support\Queries\DateRange;

class AgeingReport
{
    public function __construct(
        private readonly BillService $bills,
        private readonly BillAllocationService $allocations,
    ) {}

    /**
     * @return array{rows: list<array<string, mixed>>, totals: array<string, string>}
     */
    public function receivables(Company $company, string $asOf, ?int $branchId = null): array
    {
        $company->ledgers()->with('accountGroup.parent')->get()->each(fn (Ledger $l) => $this->bills->ensureOpening($l));

        $buckets = ['current' => 0, 'b1_30' => 0, 'b31_60' => 0, 'b61_90' => 0, 'b90_plus' => 0];
        $rows = [];

        $bills = Bill::query()
            ->with('ledger')
            ->where('company_id', $company->id)
            ->where('side', OpeningBalanceType::Debit)
            ->tap(fn ($q) => DateRange::apply($q, 'bill_date', null, $asOf))
            ->when($branchId, fn ($q) => $q->where(fn ($q) => $q->whereNull('branch_id')->orWhere('branch_id', $branchId)))
            ->orderBy('due_date')
            ->get();

        foreach ($bills as $bill) {
            if (! $bill->isActive() || ! $bill->ledger?->isCustomer()) {
                continue;
            }

            $outstanding = $this->allocations->outstandingCents($bill, $asOf);
            if ($outstanding <= 0) {
                continue;
            }

            $days = max(0, (int) ((strtotime($asOf) - strtotime($bill->due_date?->toDateString() ?? $asOf)) / 86400));
            $key = match (true) {
                $days <= 0 => 'current',
                $days <= 30 => 'b1_30',
                $days <= 60 => 'b31_60',
                $days <= 90 => 'b61_90',
                default => 'b90_plus',
            };
            $buckets[$key] += $outstanding;

            $rows[] = [
                'bill_id' => (string) $bill->id,
                'party' => $bill->ledger->name,
                'bill_number' => $bill->bill_number,
                'due_date' => $bill->due_date?->format('d M Y'),
                'days_overdue' => (string) max(0, $days),
                'outstanding' => Money::format($outstanding),
                'bucket' => $key,
            ];
        }

        return [
            'rows' => $rows,
            'totals' => [
                'current' => Money::format($buckets['current']),
                'b1_30' => Money::format($buckets['b1_30']),
                'b31_60' => Money::format($buckets['b31_60']),
                'b61_90' => Money::format($buckets['b61_90']),
                'b90_plus' => Money::format($buckets['b90_plus']),
                'total' => Money::format(array_sum($buckets)),
            ],
        ];
    }
}
