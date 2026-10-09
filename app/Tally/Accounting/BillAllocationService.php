<?php

namespace Tally\Accounting;

use Tally\Models\Bill;
use Tally\Models\BillAllocation;
use Tally\Models\Company;
use Tally\Models\Ledger;
use Tally\Models\Voucher;
use Tally\Support\Queries\DateRange;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Applies a receipt or payment to outstanding bills.
 * Any remainder is stored as an advance. Draft and cancelled vouchers
 * do not reduce the outstanding amount.
 */
class BillAllocationService
{
    public function __construct(private readonly BillService $bills) {}

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function sync(Voucher $voucher, array $rows): void
    {
        if (! in_array($voucher->voucher_type, [VoucherType::Receipt, VoucherType::Payment], true)) {
            return;
        }

        $voucher->loadMissing('entries.ledger.accountGroup');
        $available = $this->settleable($voucher);
        $parsed = $this->parse($voucher, $rows, $available);

        $voucher->billAllocations()->delete();

        foreach ($parsed as $row) {
            BillAllocation::query()->create([
                'company_id' => $voucher->company_id,
                'bill_id' => $row['bill_id'],
                'voucher_id' => $voucher->id,
                'ledger_id' => $row['ledger_id'],
                'amount' => Money::format($row['amount']),
            ]);
        }

        foreach ($available as $ledgerId => $cents) {
            $used = 0;

            foreach ($parsed as $row) {
                if ($row['ledger_id'] === $ledgerId && $row['bill_id'] !== null) {
                    $used += $row['amount'];
                }
            }

            $advance = $cents - $used;

            if ($advance > 0) {
                BillAllocation::query()->create([
                    'company_id' => $voucher->company_id,
                    'bill_id' => null,
                    'voucher_id' => $voucher->id,
                    'ledger_id' => $ledgerId,
                    'amount' => Money::format($advance),
                ]);
            }
        }
    }

    /**
     * @return Collection<int, Bill>
     */
    public function openBills(Company $company, string $asOf, ?int $ignoreVoucherId = null): Collection
    {
        $company->ledgers()->with('accountGroup')->orderBy('name')->get()->each(function (Ledger $ledger) {
            $this->bills->ensureOpening($ledger);
        });

        return Bill::query()
            ->with('ledger')
            ->where('company_id', $company->id)
            ->tap(fn ($query) => DateRange::apply($query, 'bill_date', null, $asOf))
            ->orderBy('bill_date')
            ->orderBy('bill_number')
            ->get()
            ->filter(fn (Bill $bill) => $bill->isActive())
            ->map(function (Bill $bill) use ($asOf, $ignoreVoucherId) {
                $bill->setAttribute('outstanding_cents', $this->outstandingCents($bill, $asOf, $ignoreVoucherId));
                $bill->setAttribute('paid_cents', Money::cents((string) $bill->original_amount) - $bill->outstanding_cents);

                return $bill;
            })
            ->filter(fn (Bill $bill) => $bill->outstanding_cents > 0)
            ->values();
    }

    public function outstandingCents(Bill $bill, string $asOf, ?int $ignoreVoucherId = null): int
    {
        if (! $bill->isActive() || $bill->bill_date->toDateString() > $asOf) {
            return 0;
        }

        $paid = $this->postedAllocations($bill->company_id, $asOf, $ignoreVoucherId)
            ->where('bill_id', $bill->id)
            ->sum(fn (BillAllocation $allocation) => Money::cents((string) $allocation->amount));

        return max(0, Money::cents((string) $bill->original_amount) - $paid);
    }

    /**
     * @return array<int, int> ledger id => cents
     */
    private function settleable(Voucher $voucher): array
    {
        $amounts = [];

        foreach ($voucher->entries as $entry) {
            $ledger = $entry->ledger;

            if (! $ledger) {
                continue;
            }

            $cents = $voucher->voucher_type === VoucherType::Receipt
                ? ($ledger->isCustomer() ? Money::cents((string) $entry->credit) : 0)
                : ($ledger->isSupplier() ? Money::cents((string) $entry->debit) : 0);

            if ($cents === 0) {
                continue;
            }

            $amounts[$ledger->id] = ($amounts[$ledger->id] ?? 0) + $cents;
        }

        return $amounts;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array<int, int>  $available
     * @return list<array{bill_id: int, ledger_id: int, amount: int}>
     */
    private function parse(Voucher $voucher, array $rows, array $available): array
    {
        $side = $voucher->voucher_type === VoucherType::Receipt
            ? OpeningBalanceType::Debit
            : OpeningBalanceType::Credit;
        $parsed = [];
        $errors = [];
        $usedOnBill = [];

        foreach (array_values($rows) as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $billId = (int) ($row['bill_id'] ?? 0);
            $raw = trim((string) ($row['amount'] ?? ''));

            if ($billId === 0 && ($raw === '' || $raw === '0' || $raw === '0.00')) {
                continue;
            }

            try {
                $amount = Money::cents($raw === '' ? '0' : $raw);
            } catch (\InvalidArgumentException) {
                $errors["allocations.$index.amount"] = 'Enter an allocation with up to 2 decimal places.';

                continue;
            }

            if ($billId === 0 || $amount <= 0) {
                $errors["allocations.$index.bill_id"] = 'Select a bill and enter an amount, or leave the row blank.';

                continue;
            }

            $bill = Bill::query()->with('entry.voucher')->where('company_id', $voucher->company_id)->whereKey($billId)->first();

            if (! $bill || ! $bill->isActive() || $bill->side !== $side) {
                $errors["allocations.$index.bill_id"] = 'Select an outstanding bill from the current company.';

                continue;
            }

            if (! array_key_exists($bill->ledger_id, $available)) {
                $errors["allocations.$index.bill_id"] = 'That bill is not for a party on this voucher.';

                continue;
            }

            $usedOnBill[$bill->id] = ($usedOnBill[$bill->id] ?? 0) + $amount;
            $outstanding = $this->outstandingCents($bill, '9999-12-31', $voucher->id);

            if ($usedOnBill[$bill->id] > $outstanding) {
                $errors["allocations.$index.amount"] = 'The allocation is more than the outstanding on '.$bill->bill_number.' ('.Money::format($outstanding).').';

                continue;
            }

            $parsed[] = [
                'bill_id' => $bill->id,
                'ledger_id' => $bill->ledger_id,
                'amount' => $amount,
            ];
        }

        $byLedger = [];

        foreach ($parsed as $row) {
            $byLedger[$row['ledger_id']] = ($byLedger[$row['ledger_id']] ?? 0) + $row['amount'];
        }

        foreach ($byLedger as $ledgerId => $cents) {
            if ($cents > ($available[$ledgerId] ?? 0)) {
                $errors['allocations'] = 'Allocations cannot exceed the party amount on this voucher.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $parsed;
    }

    /**
     * @return Collection<int, BillAllocation>
     */
    private function postedAllocations(int $companyId, string $asOf, ?int $ignoreVoucherId): Collection
    {
        return BillAllocation::query()
            ->where('company_id', $companyId)
            ->whereNotNull('bill_id')
            ->when($ignoreVoucherId, fn ($query) => $query->where('voucher_id', '!=', $ignoreVoucherId))
            ->whereHas('voucher', function ($query) use ($asOf) {
                $query->where('status', VoucherStatus::Posted)->tap(fn ($query) => DateRange::apply($query, 'voucher_date', null, $asOf));
            })
            ->get();
    }
}
