<?php

namespace Tally\Purchasing;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tally\Accounting\Money;
use Tally\Accounting\VoucherEngine;
use Tally\Accounting\VoucherType;
use Tally\Inventory\InventoryAccounts;
use Tally\Inventory\StockTransactionService;
use Tally\Inventory\StockTransactionType;
use Tally\Invoicing\InvoiceKind;
use Tally\Invoicing\InvoiceService;
use Tally\Models\Branch;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Models\GoodsReceipt;
use Tally\Models\Invoice;
use Tally\Models\Ledger;
use Tally\Models\PurchaseOrder;

class GoodsReceiptService
{
    public function __construct(
        private readonly StockTransactionService $stockTransactions,
        private readonly InventoryAccounts $inventoryAccounts,
        private readonly VoucherEngine $vouchers,
        private readonly InvoiceService $invoices,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function save(Company $company, Branch $branch, FinancialYear $year, User $user, array $input, ?GoodsReceipt $receipt = null): GoodsReceipt
    {
        $wantPost = (($input['status'] ?? null) === 'posted');

        $saved = DB::transaction(function () use ($company, $branch, $year, $user, $input, $receipt, $wantPost) {
            $poId = ($input['purchase_order_id'] ?? null) ?: null;
            $supplierId = (int) ($input['supplier_ledger_id'] ?? 0);

            if ($poId) {
                $po = PurchaseOrder::query()
                    ->where('company_id', $company->id)
                    ->whereKey($poId)
                    ->first();
                if (! $po) {
                    throw ValidationException::withMessages([
                        'purchase_order_id' => 'Purchase order not found.',
                    ]);
                }
                $supplierId = $supplierId ?: (int) $po->supplier_ledger_id;
            }

            if ($supplierId <= 0) {
                throw ValidationException::withMessages([
                    'supplier_ledger_id' => 'Select a supplier.',
                ]);
            }

            $payload = [
                'receipt_date' => (string) ($input['receipt_date'] ?? now()->toDateString()),
                'purchase_order_id' => $poId,
                'supplier_ledger_id' => $supplierId,
                'godown_id' => ($input['godown_id'] ?? null) ?: null,
                'receipt_kind' => ($input['receipt_kind'] ?? null) ?: 'goods',
                // Keep draft until post() runs so re-entry validation stays correct.
                'status' => $wantPost ? 'draft' : (($input['status'] ?? null) ?: ($receipt?->status ?? 'draft')),
                'narration' => ($input['narration'] ?? null) ?: null,
            ];

            if ($receipt) {
                if (in_array($receipt->status, ['posted', 'cancelled'], true)) {
                    throw ValidationException::withMessages([
                        'status' => 'Posted or cancelled goods receipts cannot be changed.',
                    ]);
                }
                $receipt->update($payload);
                $receipt->lines()->delete();
            } else {
                $count = GoodsReceipt::query()
                    ->where('company_id', $company->id)
                    ->where('financial_year_id', $year->id)
                    ->count() + 1;
                $receipt = GoodsReceipt::query()->create($payload + [
                    'company_id' => $company->id,
                    'branch_id' => $branch->id,
                    'financial_year_id' => $year->id,
                    'number' => 'GRN-'.str_pad((string) $count, 6, '0', STR_PAD_LEFT),
                    'created_by' => $user->id,
                ]);
            }

            $lineNumber = 1;
            foreach ($input['lines'] ?? [] as $line) {
                if (! is_array($line) || trim((string) ($line['item_name'] ?? '')) === '') {
                    continue;
                }
                $received = (float) ($line['received_qty'] ?? $line['quantity'] ?? 0);
                $rate = (float) ($line['rate'] ?? 0);
                $receipt->lines()->create([
                    'line_number' => $lineNumber++,
                    'product_id' => ($line['product_id'] ?? null) ?: null,
                    'item_name' => trim((string) $line['item_name']),
                    'ordered_qty' => $line['ordered_qty'] ?? 0,
                    'received_qty' => $received,
                    'rate' => $rate,
                    'line_total' => round($received * $rate, 2),
                ]);
            }

            return $receipt->fresh(['lines', 'supplier', 'purchaseOrder', 'company', 'branch', 'financialYear']);
        });

        if ($wantPost) {
            return $this->post($saved, $user);
        }

        return $saved;
    }

    public function post(GoodsReceipt $receipt, User $user): GoodsReceipt
    {
        return DB::transaction(function () use ($receipt, $user) {
            $receipt = GoodsReceipt::query()->whereKey($receipt->id)->lockForUpdate()->firstOrFail();
            $receipt->loadMissing(['lines', 'company', 'branch', 'financialYear', 'purchaseOrder', 'supplier']);

            if (! in_array($receipt->status, ['draft', 'received'], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Only draft or received goods receipts can be posted.',
                ]);
            }

            if ($receipt->lines->isEmpty()) {
                throw ValidationException::withMessages([
                    'lines' => 'Add at least one line before posting.',
                ]);
            }

            $company = $receipt->company;
            $branch = $receipt->branch;
            $year = $receipt->financialYear;

            $productLines = $receipt->lines->filter(
                fn ($line) => $receipt->receipt_kind === 'goods'
                    && (int) ($line->product_id ?? 0) > 0
                    && (float) $line->received_qty > 0
            );

            $nonStockLines = $receipt->lines->filter(function ($line) use ($receipt, $productLines) {
                if ((float) $line->received_qty <= 0) {
                    return false;
                }

                return $receipt->receipt_kind === 'service' || ! $productLines->contains('id', $line->id);
            });

            if ($productLines->isNotEmpty()) {
                $godownId = (int) ($receipt->godown_id ?? 0);
                if ($godownId <= 0) {
                    throw ValidationException::withMessages([
                        'godown_id' => 'Select a godown before posting stock for this goods receipt.',
                    ]);
                }

                $stock = $this->stockTransactions->save(
                    $company,
                    $branch,
                    $year,
                    $user,
                    StockTransactionType::In,
                    [
                        'transaction_date' => $receipt->receipt_date->toDateString(),
                        'narration' => 'Goods receipt '.$receipt->number.($receipt->narration ? ' — '.$receipt->narration : ''),
                        'lines' => $productLines->map(fn ($line) => [
                            'product_id' => $line->product_id,
                            'godown_id' => $godownId,
                            'quantity' => rtrim(rtrim(number_format((float) $line->received_qty, 4, '.', ''), '0'), '.') ?: '0',
                            'rate' => number_format((float) $line->rate, 2, '.', ''),
                        ])->values()->all(),
                    ],
                );

                if (Schema::hasColumn($receipt->getTable(), 'stock_transaction_id')) {
                    $receipt->stock_transaction_id = $stock->id;
                }
            }

            // Service / non-product lines: optional GRNI accrual (skip when ledgers missing).
            // Product stock lines already get inventory journals via InventoryPosting.
            if ($nonStockLines->isNotEmpty()) {
                $this->postGrniJournal($receipt, $user, $nonStockLines);
            }

            $receipt->status = 'posted';
            $receipt->is_invoiced = false;
            $receipt->save();

            return $receipt->fresh(['lines', 'supplier', 'purchaseOrder', 'stockTransaction']);
        });
    }

    /**
     * Create a draft purchase bill from a posted goods receipt.
     */
    public function createPurchaseBill(GoodsReceipt $receipt, User $user, ?int $accountLedgerId = null): Invoice
    {
        $receipt->loadMissing(['lines', 'company', 'branch', 'financialYear', 'supplier']);

        if ($receipt->status !== 'posted') {
            throw ValidationException::withMessages([
                'status' => 'Post the goods receipt before creating a purchase bill.',
            ]);
        }

        if ($receipt->lines->isEmpty()) {
            throw ValidationException::withMessages([
                'lines' => 'Goods receipt has no lines to bill.',
            ]);
        }

        $existing = Invoice::query()
            ->where('company_id', $receipt->company_id)
            ->where('goods_receipt_id', $receipt->id)
            ->where('kind', InvoiceKind::Purchase)
            ->first();
        if ($existing) {
            return $existing->load(['lines', 'party', 'account']);
        }

        $accountId = $accountLedgerId
            ?: $receipt->company->ledgers()
                ->whereHas('accountGroup', fn ($q) => $q->where('code', 'PURCHASE'))
                ->value('id');

        if (! $accountId) {
            throw ValidationException::withMessages([
                'account_ledger_id' => 'Create a Purchase ledger before billing this GRN.',
            ]);
        }

        $invoice = $this->invoices->save(
            $receipt->company,
            $receipt->branch,
            $receipt->financialYear,
            $user,
            InvoiceKind::Purchase,
            [
                'invoice_date' => $receipt->receipt_date->toDateString(),
                'party_ledger_id' => $receipt->supplier_ledger_id,
                'account_ledger_id' => $accountId,
                'reference_number' => $receipt->number,
                'narration' => 'Bill for '.$receipt->number.($receipt->narration ? ' — '.$receipt->narration : ''),
                'goods_receipt_id' => $receipt->id,
                'lines' => $receipt->lines->map(fn ($line) => [
                    'item_name' => $line->item_name,
                    'product_id' => $line->product_id,
                    'quantity' => (string) $line->received_qty,
                    'rate' => number_format((float) $line->rate, 2, '.', ''),
                    'discount' => '0',
                    'tax_amount' => '0',
                ])->all(),
            ],
            false,
        );

        if (Schema::hasColumn($receipt->getTable(), 'is_invoiced')) {
            $receipt->update(['is_invoiced' => true]);
        }

        return $invoice;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \Tally\Models\GoodsReceiptLine>  $lines
     */
    private function postGrniJournal(GoodsReceipt $receipt, User $user, $lines): void
    {
        $totalCents = 0;
        foreach ($lines as $line) {
            $totalCents += Money::cents((string) $line->line_total);
        }

        if ($totalCents <= 0) {
            return;
        }

        $debit = $this->resolveDebitLedger($receipt);
        $credit = $this->resolveGrniLedger($receipt->company);

        if (! $debit || ! $credit || $debit->id === $credit->id) {
            return;
        }

        $amount = Money::format($totalCents);

        $this->vouchers->save(
            $receipt->company,
            $receipt->branch,
            $receipt->financialYear,
            $user,
            VoucherType::Journal,
            [
                'voucher_date' => $receipt->receipt_date->toDateString(),
                'reference_number' => 'grn-'.$receipt->id,
                'narration' => 'GRNI accrual for goods receipt '.$receipt->number,
                'entries' => [
                    ['ledger_id' => $debit->id, 'debit' => $amount, 'credit' => '0.00'],
                    ['ledger_id' => $credit->id, 'debit' => '0.00', 'credit' => $amount],
                ],
            ],
            true,
        );
    }

    private function resolveDebitLedger(GoodsReceipt $receipt): ?Ledger
    {
        $po = $receipt->purchaseOrder;
        if ($po && (int) ($po->purchase_ledger_id ?? 0) > 0) {
            $ledger = Ledger::query()
                ->where('company_id', $receipt->company_id)
                ->whereKey($po->purchase_ledger_id)
                ->first();
            if ($ledger) {
                return $ledger;
            }
        }

        if ($receipt->receipt_kind === 'service') {
            $purchase = $receipt->company->ledgers()
                ->whereHas('accountGroup', fn ($q) => $q->where('code', 'PURCHASE'))
                ->orderBy('id')
                ->first();
            if ($purchase) {
                return $purchase;
            }

            $expense = $receipt->company->ledgers()
                ->whereHas('accountGroup', fn ($q) => $q->whereIn('code', ['DIRECT_EXPENSES', 'INDIRECT_EXPENSES']))
                ->orderBy('id')
                ->first();

            return $expense;
        }

        return $this->inventoryAccounts->stock($receipt->company);
    }

    private function resolveGrniLedger(Company $company): ?Ledger
    {
        $byCode = $company->ledgers()->where('code', 'GRNI')->first();
        if ($byCode) {
            return $byCode;
        }

        return $company->ledgers()
            ->where(function ($q) {
                $q->where('name', 'like', '%Goods Received Not Invoiced%')
                    ->orWhere('name', 'like', '%GR/IR%')
                    ->orWhere('name', 'like', '%GRNI%');
            })
            ->orderBy('id')
            ->first();
    }

    public function createFromPurchaseOrder(
        Company $company,
        Branch $branch,
        FinancialYear $year,
        User $user,
        PurchaseOrder $purchaseOrder,
    ): GoodsReceipt {
        abort_unless($purchaseOrder->company_id === $company->id, 404);
        $purchaseOrder->loadMissing('lines');

        if ($purchaseOrder->lines->isEmpty()) {
            throw ValidationException::withMessages([
                'purchase_order_id' => 'This purchase order has no lines to receive.',
            ]);
        }

        $receiptDate = $purchaseOrder->order_date?->toDateString() ?: $year->start_date->toDateString();
        if ($receiptDate < $year->start_date->toDateString() || $receiptDate > $year->end_date->toDateString()) {
            $receiptDate = $year->start_date->toDateString();
        }

        return $this->save($company, $branch, $year, $user, [
            'receipt_date' => $receiptDate,
            'purchase_order_id' => $purchaseOrder->id,
            'supplier_ledger_id' => $purchaseOrder->supplier_ledger_id,
            'status' => 'draft',
            'receipt_kind' => 'goods',
            'narration' => 'Receipt against '.$purchaseOrder->number,
            'lines' => $purchaseOrder->lines->map(fn ($line) => [
                'item_name' => $line->item_name,
                'product_id' => $line->product_id,
                'ordered_qty' => $line->quantity,
                'received_qty' => $line->quantity,
                'rate' => $line->rate,
            ])->all(),
        ]);
    }
}
