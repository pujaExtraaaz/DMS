<?php

namespace Tally\Inventory;

use Tally\Accounting\Money;
use Tally\Accounting\VoucherEngine;
use Tally\Accounting\VoucherStatus;
use Tally\Accounting\VoucherType;
use Tally\Invoicing\InvoiceKind;
use Tally\Models\Branch;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Models\Invoice;
use Tally\Models\StockMovement;
use Tally\Models\StockTransaction;
use App\Models\User;
use Tally\Models\Voucher;

/**
 * Companion journals for inventory value.
 * The commercial invoice voucher is left unchanged.
 * Purchases of stock leave the purchase account and sit on an inventory ledger.
 * Sales debit cost of goods sold and credit inventory at average cost.
 */
class InventoryPosting
{
    public const OPENING = 'inv-open';

    public function __construct(
        private readonly InventoryAccounts $accounts,
        private readonly VoucherEngine $vouchers,
    ) {}

    public static function invoiceReference(int $invoiceId): string
    {
        return 'inv-invoice-'.$invoiceId;
    }

    public static function stockReference(int $transactionId): string
    {
        return 'inv-stock-'.$transactionId;
    }

    public function ensureOpening(Company $company, Branch $branch, FinancialYear $year, User $user): void
    {
        $this->syncOpening($company, $branch, $year, $user);
    }

    public function syncOpening(Company $company, Branch $branch, FinancialYear $year, User $user): void
    {
        $entries = $this->openingEntries($company);
        $existing = $this->find($company, self::OPENING);

        if ($existing && $this->sameLines($existing, $entries)) {
            return;
        }

        if ($existing) {
            $branch = $existing->branch ?? $branch;
            $year = $existing->financialYear ?? $year;
            $this->vouchers->cancel($existing, true);
        }

        if ($entries === []) {
            return;
        }

        $this->vouchers->save($company, $branch, $year, $user, VoucherType::Journal, [
            'voucher_date' => $year->start_date->toDateString(),
            'reference_number' => self::OPENING,
            'narration' => 'Opening stock brought into the books.',
            'entries' => $entries,
        ], true);
    }

    public function syncInvoice(Invoice $invoice, User $user): void
    {
        $invoice->loadMissing(['company', 'branch', 'financialYear']);
        $this->ensureOpening($invoice->company, $invoice->branch, $invoice->financialYear, $user);

        if ($this->find($invoice->company, self::invoiceReference($invoice->id))) {
            return;
        }

        $byLedger = $this->movementValues($invoice->company, $invoice);

        if ($byLedger === []) {
            return;
        }

        $entries = [];
        $total = 0;

        $goodsOut = $invoice->kind->stockLeaves();

        if ($invoice->kind->usesCostOfGoods()) {
            foreach ($byLedger as $ledgerId => $cents) {
                $amount = abs($cents);

                if ($amount === 0) {
                    continue;
                }

                $entries[] = $goodsOut
                    ? $this->line($ledgerId, 0, $amount)
                    : $this->line($ledgerId, $amount, 0);
                $total += $amount;
            }

            if ($total === 0) {
                return;
            }

            $cogs = $this->line($this->accounts->cogs($invoice->company)->id, $goodsOut ? $total : 0, $goodsOut ? 0 : $total);

            if ($goodsOut) {
                array_unshift($entries, $cogs);
            } else {
                $entries[] = $cogs;
            }

            $narration = ($goodsOut ? 'Cost of goods sold for ' : 'Cost of goods returned on ').$invoice->invoice_number;
        } else {
            foreach ($byLedger as $ledgerId => $cents) {
                $amount = abs($cents);

                if ($amount === 0) {
                    continue;
                }

                $entries[] = $goodsOut
                    ? $this->line($ledgerId, 0, $amount)
                    : $this->line($ledgerId, $amount, 0);
                $total += $amount;
            }

            if ($total === 0) {
                return;
            }

            $account = $this->line($invoice->account_ledger_id, $goodsOut ? $total : 0, $goodsOut ? 0 : $total);

            if ($goodsOut) {
                array_unshift($entries, $account);
            } else {
                $entries[] = $account;
            }

            $narration = ($goodsOut ? 'Stock returned on ' : 'Stock purchased on ').$invoice->invoice_number;
        }

        $this->vouchers->save($invoice->company, $invoice->branch, $invoice->financialYear, $user, VoucherType::Journal, [
            'voucher_date' => $invoice->invoice_date->toDateString(),
            'reference_number' => self::invoiceReference($invoice->id),
            'narration' => $narration,
            'entries' => $entries,
        ], true);
    }

    public function cancelInvoice(Invoice $invoice): void
    {
        $this->cancelReference($invoice->company, self::invoiceReference($invoice->id));
    }

    public function syncStockTransaction(StockTransaction $transaction, User $user): void
    {
        $transaction->loadMissing(['company', 'branch', 'financialYear']);
        $this->ensureOpening($transaction->company, $transaction->branch, $transaction->financialYear, $user);

        if ($this->find($transaction->company, self::stockReference($transaction->id))) {
            return;
        }

        $byLedger = $this->movementValues($transaction->company, $transaction);
        $entries = [];
        $net = 0;

        foreach ($byLedger as $ledgerId => $cents) {
            if ($cents > 0) {
                $entries[] = $this->line($ledgerId, $cents, 0);
            } elseif ($cents < 0) {
                $entries[] = $this->line($ledgerId, 0, abs($cents));
            }

            $net += $cents;
        }

        if ($entries === []) {
            return;
        }

        if ($net > 0) {
            $entries[] = $this->line($this->accounts->adjustment($transaction->company)->id, 0, $net);
        } elseif ($net < 0) {
            array_unshift($entries, $this->line($this->accounts->adjustment($transaction->company)->id, abs($net), 0));
        }

        $this->vouchers->save($transaction->company, $transaction->branch, $transaction->financialYear, $user, VoucherType::Journal, [
            'voucher_date' => $transaction->transaction_date->toDateString(),
            'reference_number' => self::stockReference($transaction->id),
            'narration' => $transaction->type->label().' '.$transaction->number,
            'entries' => $entries,
        ], true);
    }

    public function cancelStockTransaction(StockTransaction $transaction): void
    {
        $this->cancelReference($transaction->company, self::stockReference($transaction->id));
    }

    /**
     * @return list<array{ledger_id: int, debit: string, credit: string}>
     */
    private function openingEntries(Company $company): array
    {
        $byLedger = [];

        foreach ($company->products()->with('productGroup')->get() as $product) {
            $cents = Money::cents((string) $product->opening_value);

            if ($cents <= 0) {
                continue;
            }

            $ledgerId = $this->accounts->ledgerFor($product)->id;
            $byLedger[$ledgerId] = ($byLedger[$ledgerId] ?? 0) + $cents;
        }

        if ($byLedger === []) {
            return [];
        }

        $entries = [];
        $total = 0;

        foreach ($byLedger as $ledgerId => $cents) {
            $entries[] = $this->line($ledgerId, $cents, 0);
            $total += $cents;
        }

        $entries[] = $this->line($this->accounts->openingReserve($company)->id, 0, $total);

        return $entries;
    }

    /**
     * @param  list<array{ledger_id: int, debit: string, credit: string}>  $entries
     */
    private function sameLines(Voucher $voucher, array $entries): bool
    {
        $voucher->loadMissing('entries');
        $current = $voucher->entries->map(fn ($entry) => $entry->ledger_id.':'.Money::cents((string) $entry->debit).':'.Money::cents((string) $entry->credit))->sort()->values()->all();
        $desired = collect($entries)->map(fn (array $entry) => $entry['ledger_id'].':'.Money::cents($entry['debit']).':'.Money::cents($entry['credit']))->sort()->values()->all();

        return $current === $desired;
    }

    /**
     * @return array<int, int>
     */
    private function movementValues(Company $company, Invoice|StockTransaction $document): array
    {
        $totals = [];
        $movements = StockMovement::query()
            ->with('product.productGroup')
            ->where('company_id', $company->id)
            ->where('reference_type', $document->getMorphClass())
            ->where('reference_id', $document->id)
            ->where('is_reversal', false)
            ->get();

        foreach ($movements as $movement) {
            if (! $movement->product) {
                continue;
            }

            $cents = Money::cents((string) $movement->value);

            if ($cents === 0) {
                continue;
            }

            $ledgerId = $this->accounts->ledgerFor($movement->product)->id;
            $totals[$ledgerId] = ($totals[$ledgerId] ?? 0) + $cents;
        }

        return $totals;
    }

    private function cancelReference(Company $company, string $reference): void
    {
        $voucher = $this->find($company, $reference);

        if ($voucher) {
            $this->vouchers->cancel($voucher, true);
        }
    }

    private function find(Company $company, string $reference): ?Voucher
    {
        return Voucher::query()
            ->where('company_id', $company->id)
            ->where('reference_number', $reference)
            ->where('status', VoucherStatus::Posted)
            ->first();
    }

    /**
     * @return array{ledger_id: int, debit: string, credit: string}
     */
    private function line(int $ledgerId, int $debit, int $credit): array
    {
        return [
            'ledger_id' => $ledgerId,
            'debit' => Money::format($debit),
            'credit' => Money::format($credit),
        ];
    }
}
