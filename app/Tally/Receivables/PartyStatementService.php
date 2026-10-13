<?php

namespace Tally\Receivables;

use Tally\Accounting\BillAllocationService;
use Tally\Accounting\BillService;
use Tally\Accounting\Money;
use Tally\Accounting\OpeningBalanceType;
use Tally\Models\Bill;
use Tally\Models\BillAllocation;
use Tally\Models\Company;
use Tally\Models\Ledger;
use Tally\Support\Queries\DateRange;

class PartyStatementService
{
    public function __construct(
        private readonly BillService $bills,
        private readonly BillAllocationService $allocations,
    ) {}

    /**
     * @return array{party: Ledger, opening: string, rows: list<array<string, string>>, closing: string, as_on: string}
     */
    public function billWise(Company $company, Ledger $party, string $from, string $to): array
    {
        $this->bills->ensureOpening($party);

        $rows = [];
        $running = 0;

        $openingBills = Bill::query()
            ->where('company_id', $company->id)
            ->where('ledger_id', $party->id)
            ->where('side', OpeningBalanceType::Debit)
            ->where('bill_date', '<', $from)
            ->get();

        foreach ($openingBills as $bill) {
            $running += max(0, $this->allocations->outstandingCents($bill, date('Y-m-d', strtotime($from.' -1 day'))));
        }

        $opening = $running;

        $periodBills = Bill::query()
            ->where('company_id', $company->id)
            ->where('ledger_id', $party->id)
            ->where('side', OpeningBalanceType::Debit)
            ->tap(fn ($q) => DateRange::apply($q, 'bill_date', $from, $to))
            ->orderBy('bill_date')
            ->get();

        foreach ($periodBills as $bill) {
            $amount = Money::cents((string) $bill->original_amount);
            $running += $amount;
            $rows[] = [
                'date' => $bill->bill_date->format('d M Y'),
                'particulars' => 'Invoice '.$bill->bill_number,
                'debit' => Money::format($amount),
                'credit' => '',
                'balance' => Money::format($running),
            ];
        }

        $allocations = BillAllocation::query()
            ->with('voucher')
            ->where('company_id', $company->id)
            ->where('ledger_id', $party->id)
            ->whereHas('voucher', fn ($q) => $q->tap(fn ($q) => DateRange::apply($q, 'voucher_date', $from, $to)))
            ->get();

        foreach ($allocations as $allocation) {
            $amount = Money::cents((string) $allocation->amount);
            $running -= $amount;
            $rows[] = [
                'date' => $allocation->voucher?->voucher_date?->format('d M Y') ?? '',
                'particulars' => 'Receipt '.($allocation->voucher?->voucher_number ?? ''),
                'debit' => '',
                'credit' => Money::format($amount),
                'balance' => Money::format($running),
            ];
        }

        usort($rows, fn ($a, $b) => strcmp($a['date'], $b['date']));

        return [
            'party' => $party,
            'opening' => Money::format($opening),
            'rows' => $rows,
            'closing' => Money::format($running),
            'as_on' => $to,
            'from' => $from,
        ];
    }

    /**
     * @return array{party: Ledger, opening: string, rows: list<array<string, string>>, closing: string, as_on: string}
     */
    public function supplierBillWise(Company $company, Ledger $party, string $from, string $to): array
    {
        $this->bills->ensureOpening($party);

        $rows = [];
        $running = 0;

        $openingBills = Bill::query()
            ->where('company_id', $company->id)
            ->where('ledger_id', $party->id)
            ->where('side', OpeningBalanceType::Credit)
            ->where('bill_date', '<', $from)
            ->get();

        foreach ($openingBills as $bill) {
            $running += max(0, $this->allocations->outstandingCents($bill, date('Y-m-d', strtotime($from.' -1 day'))));
        }

        $opening = $running;

        $periodBills = Bill::query()
            ->where('company_id', $company->id)
            ->where('ledger_id', $party->id)
            ->where('side', OpeningBalanceType::Credit)
            ->tap(fn ($q) => DateRange::apply($q, 'bill_date', $from, $to))
            ->orderBy('bill_date')
            ->get();

        foreach ($periodBills as $bill) {
            $amount = Money::cents((string) $bill->original_amount);
            $running += $amount;
            $rows[] = [
                'date' => $bill->bill_date->format('d M Y'),
                'particulars' => 'Invoice '.$bill->bill_number,
                'debit' => '',
                'credit' => Money::format($amount),
                'balance' => Money::format($running),
            ];
        }

        $allocations = BillAllocation::query()
            ->with('voucher')
            ->where('company_id', $company->id)
            ->where('ledger_id', $party->id)
            ->whereHas('voucher', fn ($q) => $q->tap(fn ($q) => DateRange::apply($q, 'voucher_date', $from, $to)))
            ->get();

        foreach ($allocations as $allocation) {
            $amount = Money::cents((string) $allocation->amount);
            $running -= $amount;
            $rows[] = [
                'date' => $allocation->voucher?->voucher_date?->format('d M Y') ?? '',
                'particulars' => 'Payment '.($allocation->voucher?->voucher_number ?? ''),
                'debit' => Money::format($amount),
                'credit' => '',
                'balance' => Money::format($running),
            ];
        }

        usort($rows, fn ($a, $b) => strcmp($a['date'], $b['date']));

        return [
            'party' => $party,
            'opening' => Money::format($opening),
            'rows' => $rows,
            'closing' => Money::format($running),
            'as_on' => $to,
            'from' => $from,
        ];
    }
}
