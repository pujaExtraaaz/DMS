<?php

namespace Tally\Banking;

use Tally\Accounting\Money;
use Tally\Accounting\VoucherStatus;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Models\Ledger;
use Tally\Models\VoucherEntry;
use Tally\Support\Queries\DateRange;
use Illuminate\Support\Collection;

/**
 * Posted voucher lines on bank ledgers. Draft and cancelled vouchers are omitted.
 */
class BankBook
{
    public function __construct(private readonly BankAccountService $accounts) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function lines(
        Company $company,
        FinancialYear $year,
        ?int $branchId,
        ?int $ledgerId,
        string $from,
        string $to,
        string $search = '',
    ): array {
        $bankIds = $this->bankLedgerIds($company);

        if ($ledgerId) {
            $bankIds = in_array($ledgerId, $bankIds, true) ? [$ledgerId] : [];
        }

        if ($bankIds === []) {
            return [];
        }

        $like = '%'.addcslashes($search, '%_\\').'%';
        $entries = VoucherEntry::query()
            ->with(['ledger.bankAccount', 'voucher', 'reconciliation'])
            ->whereIn('ledger_id', $bankIds)
            ->whereHas('voucher', function ($query) use ($company, $year, $branchId, $from, $to) {
                $query->where('company_id', $company->id)
                    ->where('financial_year_id', $year->id)
                    ->where('status', VoucherStatus::Posted)
                    ->tap(fn ($query) => DateRange::apply($query, 'voucher_date', $from, $to))
                    ->when($branchId, fn ($query) => $query->where('branch_id', $branchId));
            })
            ->when($search !== '', function ($query) use ($like) {
                $query->where(function ($query) use ($like) {
                    $query->where('narration', 'like', $like)
                        ->orWhere('reference', 'like', $like)
                        ->orWhereHas('voucher', function ($query) use ($like) {
                            $query->where('voucher_number', 'like', $like)
                                ->orWhere('narration', 'like', $like)
                                ->orWhere('reference_number', 'like', $like);
                        })
                        ->orWhereHas('reconciliation', fn ($query) => $query->where('reference', 'like', $like));
                });
            })
            ->get()
            ->sortBy(fn (VoucherEntry $entry) => $entry->voucher->voucher_date->toDateString().'-'.str_pad((string) $entry->id, 8, '0', STR_PAD_LEFT))
            ->values();

        return $entries->map(fn (VoucherEntry $entry) => $this->row($entry))->all();
    }

    /**
     * @return array{cents: int, side: string, amount: string}
     */
    public function bookAmount(VoucherEntry $entry): array
    {
        $debit = Money::cents((string) $entry->debit);
        $credit = Money::cents((string) $entry->credit);
        $cents = $debit > 0 ? $debit : $credit;

        return [
            'cents' => $cents,
            'side' => $debit > 0 ? 'Dr' : 'Cr',
            'amount' => Money::format($cents),
        ];
    }

    /**
     * @return list<int>
     */
    public function bankLedgerIds(Company $company): array
    {
        $groupIds = $this->accounts->bankGroupIds($company);

        if ($groupIds === []) {
            return [];
        }

        return $company->ledgers()
            ->whereIn('account_group_id', $groupIds)
            ->orderBy('name')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @return Collection<int, Ledger>
     */
    public function bankLedgers(Company $company): Collection
    {
        $ids = $this->bankLedgerIds($company);

        return $company->ledgers()
            ->with('bankAccount')
            ->whereIn('id', $ids === [] ? [0] : $ids)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function row(VoucherEntry $entry): array
    {
        $book = $this->bookAmount($entry);
        $reconciliation = $entry->reconciliation;
        $bankCents = $reconciliation ? Money::cents((string) $reconciliation->bank_amount) : null;

        return [
            'entry' => $entry,
            'date' => $entry->voucher->voucher_date->format('d M Y'),
            'number' => $entry->voucher->voucher_number,
            'type' => $entry->voucher->voucher_type->label(),
            'ledger' => $entry->ledger->name,
            'narration' => $entry->narration ?: $entry->voucher->narration,
            'book_amount' => $book['amount'],
            'book_side' => $book['side'],
            'bank_amount' => $reconciliation?->bank_amount,
            'difference' => $bankCents === null ? null : Money::format($book['cents'] - $bankCents),
            'reference' => $reconciliation?->reference,
            'transaction_date' => $reconciliation?->transaction_date?->toDateString() ?? $entry->voucher->voucher_date->toDateString(),
            'status' => $reconciliation?->status ?? ReconciliationStatus::Unreconciled,
            'reconciled_on' => $reconciliation?->reconciled_on?->toDateString(),
            'url' => tally_route('vouchers.show', $entry->voucher),
        ];
    }
}
