<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Purchasing\Models\PurchaseInward;
use App\Domains\Sync\Support\BooksCompany;
use App\Domains\Sync\Support\SyncFailureLogger;
use App\Domains\Sync\Support\SyncLinks;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Tally\Models\GoodsReceipt;
use Tally\Models\GoodsReceiptLine;
use Tally\Models\Party;

class GoodsReceiptSync
{
    public const KEY = 'goods_receipt';

    public function __construct(
        private readonly CustomerSync $customers,
        private readonly ProductSync $products,
        private readonly WarehouseSync $warehouses,
        private readonly PurchaseOrderSync $purchaseOrders,
    ) {}

    public function sync(Model $model): void
    {
        if ($model instanceof PurchaseInward) {
            $this->push($model);
        }
    }

    public function push(PurchaseInward $inward): void
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

        $status = in_array($inward->status, ['posted', 'completed', 'received'], true) ? 'posted' : 'draft';

        $attributes = [
            'company_id' => $company->id,
            'branch_id' => $branchId,
            'financial_year_id' => $year->id,
            'number' => mb_substr((string) $inward->inward_no, 0, 30),
            'receipt_date' => $date,
            'purchase_order_id' => $poId,
            'supplier_ledger_id' => $party->ledger_id,
            'godown_id' => $godownId,
            'receipt_kind' => 'purchase',
            'status' => $status,
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
        } catch (\Throwable $e) {
            SyncFailureLogger::write(self::KEY, 'dms_to_books', $inward, $e);
            throw $e;
        }
    }
}
