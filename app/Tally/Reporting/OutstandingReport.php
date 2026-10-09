<?php

namespace Tally\Reporting;

use Tally\Accounting\BillAllocationService;
use Tally\Accounting\BillService;
use Tally\Accounting\Money;
use Tally\Accounting\OpeningBalanceType;
use Tally\Accounting\VoucherStatus;
use Tally\Models\Bill;
use Tally\Models\BillAllocation;
use Tally\Models\Company;
use Tally\Models\Ledger;
use Tally\Support\Queries\DateRange;

class OutstandingReport
{
    public function __construct(
        private readonly BillService $bills,
        private readonly BillAllocationService $allocations,
    ) {}

    /**
     * @return array{rows: list<array<string, string>>, total: string, advances: string, net: string}
     */
    public function receivables(Company $company, string $asOf, ?int $branchId = null, ?int $ledgerId = null): array
    {
        return $this->report($company, $asOf, $branchId, true, $ledgerId);
    }

    /**
     * @return array{rows: list<array<string, string>>, total: string, advances: string, net: string}
     */
    public function payables(Company $company, string $asOf, ?int $branchId = null, ?int $ledgerId = null): array
    {
        return $this->report($company, $asOf, $branchId, false, $ledgerId);
    }

    /**
     * @return array{rows: list<array<string, string>>, total: string, advances: string, net: string}
     */
    private function report(Company $company, string $asOf, ?int $branchId, bool $receivable, ?int $ledgerId = null): array
    {
        $ledgers = $company->ledgers()->with('accountGroup.parent')->orderBy('name')
            ->when($ledgerId, fn ($query) => $query->whereKey($ledgerId))
            ->get();
        $ledgers->each(function (Ledger $ledger) {
            $this->bills->ensureOpening($ledger);
        });

        $side = $receivable ? OpeningBalanceType::Debit : OpeningBalanceType::Credit;
        $rows = [];
        $total = 0;
        $overdue = 0;

        $bills = Bill::query()
            ->with(['ledger.accountGroup.parent', 'entry.voucher'])
            ->where('company_id', $company->id)
            ->where('side', $side)
            ->when($ledgerId, fn ($query) => $query->where('ledger_id', $ledgerId))
            ->tap(fn ($query) => DateRange::apply($query, 'bill_date', null, $asOf))
            ->when($branchId, fn ($query) => $query->where(function ($query) use ($branchId) {
                $query->whereNull('branch_id')->orWhere('branch_id', $branchId);
            }))
            ->orderBy('bill_date')
            ->orderBy('bill_number')
            ->get();

        foreach ($bills as $bill) {
            if (! $bill->isActive() || ! $this->matchesParty($bill->ledger, $receivable)) {
                continue;
            }

            $outstanding = $this->allocations->outstandingCents($bill, $asOf);
            $original = Money::cents((string) $bill->original_amount);
            $paid = $original - $outstanding;

            if ($outstanding <= 0) {
                continue;
            }

            $total += $outstanding;

            if ($bill->due_date && $bill->due_date->toDateString() < $asOf) {
                $overdue += $outstanding;
            }

            $rows[] = [
                'bill_id' => (string) $bill->id,
                'party' => $bill->ledger->name,
                'bill_number' => $bill->bill_number,
                'bill_date' => $bill->bill_date->format('d M Y'),
                'due_date' => $bill->due_date->format('d M Y'),
                'original' => $bill->original_amount,
                'paid' => Money::format($paid),
                'outstanding' => Money::format($outstanding),
                'side' => $bill->side->label(),
            ];
        }

        $advances = $this->advances($company, $asOf, $branchId, $receivable);

        return [
            'rows' => $rows,
            'total' => Money::format($total),
            'advances' => Money::format($advances),
            'net' => Money::format($total - $advances),
            'overdue' => Money::format($overdue),
        ];
    }

    private function matchesParty(Ledger $ledger, bool $receivable): bool
    {
        return $receivable ? $ledger->isCustomer() : $ledger->isSupplier();
    }

    private function advances(Company $company, string $asOf, ?int $branchId, bool $receivable): int
    {
        $allocations = BillAllocation::query()
            ->with(['ledger.accountGroup.parent', 'bill.entry.voucher', 'voucher'])
            ->where('company_id', $company->id)
            ->whereHas('voucher', function ($query) use ($asOf, $branchId) {
                $query->where('status', VoucherStatus::Posted)
                    ->tap(fn ($query) => DateRange::apply($query, 'voucher_date', null, $asOf))
                    ->when($branchId, fn ($query) => $query->where('branch_id', $branchId));
            })
            ->get();

        $total = 0;

        foreach ($allocations as $allocation) {
            if (! $this->matchesParty($allocation->ledger, $receivable)) {
                continue;
            }

            $againstActiveBill = $allocation->bill && $allocation->bill->isActive();

            if ($againstActiveBill) {
                continue;
            }

            $total += Money::cents((string) $allocation->amount);
        }

        $company->ledgers()->with('accountGroup.parent')->get()->each(function (Ledger $ledger) use (&$total, $receivable) {
            if (! $this->matchesParty($ledger, $receivable)) {
                return;
            }

            $opening = Money::cents((string) $ledger->opening_balance);

            if ($opening === 0) {
                return;
            }

            $advanceOpening = $receivable
                ? $ledger->opening_balance_type === OpeningBalanceType::Credit
                : $ledger->opening_balance_type === OpeningBalanceType::Debit;

            if ($advanceOpening) {
                $total += $opening;
            }
        });

        return $total;
    }
}
