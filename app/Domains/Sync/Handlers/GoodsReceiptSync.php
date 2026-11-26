<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Purchasing\Models\PurchaseInward;
use App\Domains\Purchasing\Models\ReceiptNote;
use App\Domains\Sync\Support\BooksCompany;
use App\Domains\Sync\Support\SyncFailureLogger;
use App\Domains\Sync\Support\SyncLinks;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Tally\Models\GoodsReceipt;
use Tally\Models\GoodsReceiptLine;
use Tally\Models\Party;
use Tally\Purchasing\GoodsReceiptService;

/**
 * Pushes DMS Receipt Notes (and legacy Purchase Inwards) to Books Goods Receipts,
 * then posts stock via GoodsReceiptService so StockMovementSync can skip those refs.
 */
class GoodsReceiptSync
{
    public const KEY = 'goods_receipt';

    public function __construct(
        private readonly CustomerSync $customers,
        private readonly ProductSync $products,
        private readonly WarehouseSync $warehouses,
        private readonly PurchaseOrderSync $purchaseOrders,
        private readonly GoodsReceiptService $goodsReceipts,
    ) {}

    public function sync(Model $model): void
    {
        if ($model instanceof ReceiptNote) {
            $this->pushReceiptNote($model);

            return;
        }

        if ($model instanceof PurchaseInward) {
            $this->pushInward($model);
        }
    }

    public function push(PurchaseInward $inward): void
    {
        $this->pushInward($inward);
    }

    public function pushReceiptNote(ReceiptNote $note): void
    {
        $note->loadMissing(['supplier', 'warehouse', 'items.product', 'purchaseOrder']);
        $organizationId = (int) ($note->company_id ?: $note->supplier?->company_id ?: $note->warehouse?->company_id);
        if (! $organizationId || ! $note->supplier || ! $note->warehouse) {
            return;
        }

        // Only sync confirmed / invoiced / returned receipts — drafts stay DMS-only.
        if (in_array($note->status, [ReceiptNote::STATUS_DRAFT, ReceiptNote::STATUS_CANCELLED], true)) {
            return;
        }

        $company = BooksCompany::forOrganization($organizationId);
        $date = $note->receipt_date?->toDateString() ?: now()->toDateString();
        $year = $company ? BooksCompany::yearFor($company, $date) : null;
        $branchId = $company
            ? BooksCompany::branchId($organizationId, $note->branch_id ? (int) $note->branch_id : ($note->warehouse->branch_id ? (int) $note->warehouse->branch_id : null))
            : null;
        if (! $company || ! $year || ! $branchId) {
            throw new \RuntimeException('Receipt note '.$note->receipt_note_no.' has no books company/branch/year.');
        }

        $books = GoodsReceipt::query()->find(SyncLinks::acctId(self::KEY, $note));
        if ($books && $books->status === 'posted') {
            SyncLinks::store(self::KEY, $note, $books, 'conflict', 'Books GRN already posted');

            return;
        }

        $this->customers->push($note->supplier);
        $this->warehouses->push($note->warehouse);
        $party = Party::query()->find(SyncLinks::acctId(CustomerSync::KEY, $note->supplier));
        $godownId = SyncLinks::acctId(WarehouseSync::KEY, $note->warehouse);
        if (! $party || ! $godownId) {
            throw new \RuntimeException('Supplier/godown missing for receipt note '.$note->receipt_note_no);
        }

        $poId = null;
        if ($note->purchaseOrder) {
            $this->purchaseOrders->push($note->purchaseOrder);
            $poId = SyncLinks::acctId(PurchaseOrderSync::KEY, $note->purchaseOrder);
        }

        $attributes = [
            'company_id' => $company->id,
            'branch_id' => $branchId,
            'financial_year_id' => $year->id,
            'number' => mb_substr((string) $note->receipt_note_no, 0, 30),
            'receipt_date' => $date,
            'purchase_order_id' => $poId,
            'supplier_ledger_id' => $party->ledger_id,
            'godown_id' => $godownId,
            'receipt_kind' => 'goods',
            'status' => 'draft',
            'narration' => $note->remarks,
            'created_by' => $note->created_by ?: User::query()->value('id'),
        ];

        try {
            DB::transaction(function () use (&$books, $attributes, $note) {
                $books ? $books->update($attributes) : $books = GoodsReceipt::query()->create($attributes);
                $books->lines()->delete();
                $n = 1;
                foreach ($note->items as $item) {
                    $productId = null;
                    if ($item->product) {
                        $this->products->push($item->product);
                        $productId = SyncLinks::acctId(ProductSync::KEY, $item->product);
                    }
                    $received = (float) ($item->received_qty ?? 0);
                    $rate = (float) ($item->unit_cost ?? 0);
                    GoodsReceiptLine::query()->create([
                        'goods_receipt_id' => $books->id,
                        'line_number' => $n++,
                        'product_id' => $productId,
                        'item_name' => $item->product?->name ?? 'Item',
                        'ordered_qty' => $item->ordered_qty ?? $received,
                        'received_qty' => $received,
                        'rate' => $rate,
                        'line_total' => round($received * $rate, 2),
                    ]);
                }
                SyncLinks::store(self::KEY, $note, $books);
            });

            $user = User::query()->find($note->confirmed_by ?: $note->created_by) ?: User::query()->orderBy('id')->first();
            if ($user && $books && $books->status !== 'posted') {
                $this->goodsReceipts->post($books->fresh(['lines']), $user);
            }
        } catch (\Throwable $e) {
            SyncFailureLogger::write(self::KEY, 'dms_to_books', $note, $e);
            throw $e;
        }
    }

    public function pushInward(PurchaseInward $inward): void
    {
        $inward->loadMissing(['supplier', 'warehouse', 'items.product', 'purchaseOrder']);
        $organizationId = (int) ($inward->supplier?->company_id ?: $inward->warehouse?->company_id);
        if (! $organizationId || ! $inward->supplier || ! $inward->warehouse) {
            return;
        }

        $company = BooksCompany::forOrganization($organizationId);
        $date = $inward->inward_date?->toDateString() ?: now()->toDateString();
        $year = $company ? BooksCompany::yearFor($company, $date) : null;
        $branchId = $company ? BooksCompany::branchId($organizationId, $inward->warehouse->branch_id ? (int) $inward->warehouse->branch_id : null) : null;
        if (! $company || ! $year || ! $branchId) {
            throw new \RuntimeException('Inward '.$inward->inward_no.' has no books company/branch/year.');
        }

        $books = GoodsReceipt::query()->find(SyncLinks::acctId(self::KEY, $inward));
        if ($books && $books->status === 'posted') {
            SyncLinks::store(self::KEY, $inward, $books, 'conflict', 'Books GRN already posted');

            return;
        }

        $this->customers->push($inward->supplier);
        $this->warehouses->push($inward->warehouse);
        $party = Party::query()->find(SyncLinks::acctId(CustomerSync::KEY, $inward->supplier));
        $godownId = SyncLinks::acctId(WarehouseSync::KEY, $inward->warehouse);
        if (! $party || ! $godownId) {
            throw new \RuntimeException('Supplier/godown missing for inward '.$inward->inward_no);
        }

        $poId = null;
        if ($inward->purchaseOrder) {
            $this->purchaseOrders->push($inward->purchaseOrder);
            $poId = SyncLinks::acctId(PurchaseOrderSync::KEY, $inward->purchaseOrder);
        }

        $shouldPost = in_array($inward->status, ['posted', 'completed', 'received'], true);

        $attributes = [
            'company_id' => $company->id,
            'branch_id' => $branchId,
            'financial_year_id' => $year->id,
            'number' => mb_substr((string) $inward->inward_no, 0, 30),
            'receipt_date' => $date,
            'purchase_order_id' => $poId,
            'supplier_ledger_id' => $party->ledger_id,
            'godown_id' => $godownId,
            'receipt_kind' => 'goods',
            'status' => 'draft',
            'narration' => $inward->notes,
            'created_by' => $inward->created_by ?: User::query()->value('id'),
        ];

        try {
            DB::transaction(function () use (&$books, $attributes, $inward) {
                $books ? $books->update($attributes) : $books = GoodsReceipt::query()->create($attributes);
                $books->lines()->delete();
                $n = 1;
                foreach ($inward->items as $item) {
                    $productId = null;
                    if ($item->product) {
                        $this->products->push($item->product);
                        $productId = SyncLinks::acctId(ProductSync::KEY, $item->product);
                    }
                    $received = (float) ($item->received_qty ?? $item->accepted_qty ?? 0);
                    $rate = (float) ($item->unit_cost ?? 0);
                    GoodsReceiptLine::query()->create([
                        'goods_receipt_id' => $books->id,
                        'line_number' => $n++,
                        'product_id' => $productId,
                        'item_name' => $item->product?->name ?? 'Item',
                        'ordered_qty' => $item->ordered_qty ?? $received,
                        'received_qty' => $received,
                        'rate' => $rate,
                        'line_total' => round($received * $rate, 2),
                    ]);
                }
                SyncLinks::store(self::KEY, $inward, $books);
            });

            if ($shouldPost && $books && $books->status !== 'posted') {
                $user = User::query()->find($inward->created_by) ?: User::query()->orderBy('id')->first();
                if ($user) {
                    $this->goodsReceipts->post($books->fresh(['lines']), $user);
                }
            }
        } catch (\Throwable $e) {
            SyncFailureLogger::write(self::KEY, 'dms_to_books', $inward, $e);
            throw $e;
        }
    }

    /**
     * Cancel / void the Books GRN twin when a DMS Receipt Note is cancelled.
     */
    public function cancelBooksTwin(ReceiptNote|PurchaseInward $source): void
    {
        $books = GoodsReceipt::query()->find(SyncLinks::acctId(self::KEY, $source));
        if (! $books) {
            return;
        }

        try {
            if (in_array($books->status, ['draft', 'received'], true)) {
                $books->lines()->delete();
                $books->delete();
                SyncLinks::forget(self::KEY, $source);

                return;
            }

            if ($books->status === 'cancelled') {
                return;
            }

            if ($books->stock_transaction_id) {
                $stock = \Tally\Models\StockTransaction::query()->find($books->stock_transaction_id);
                if ($stock && ! $stock->isCancelled()) {
                    app(\Tally\Inventory\StockTransactionService::class)->cancel($stock);
                }
            }

            $books->update(['status' => 'cancelled']);
        } catch (\Throwable $e) {
            SyncFailureLogger::write(self::KEY, 'dms_to_books', $source, $e);
        }
    }
}
