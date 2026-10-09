<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Master\Models\Product;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Purchasing\Models\PurchaseOrderItem;
use App\Domains\Sync\Support\BooksCompany;
use App\Domains\Sync\Support\SyncFailureLogger;
use App\Domains\Sync\Support\SyncLinks;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Tally\Models\Party;
use Tally\Models\Product as BooksProduct;
use Tally\Models\PurchaseOrder as BooksPurchaseOrder;
use Tally\Models\PurchaseOrderLine;

class PurchaseOrderSync
{
    public const KEY = 'purchase_order';

    public function __construct(
        private readonly CustomerSync $customers,
        private readonly ProductSync $products,
    ) {}

    public function sync(Model $model): void
    {
        if ($model instanceof PurchaseOrder) {
            $this->push($model);

            return;
        }

        if ($model instanceof BooksPurchaseOrder) {
            $this->pull($model);
        }
    }

    public function push(PurchaseOrder $order): void
    {
        $order->loadMissing(['supplier', 'items.product']);
        $organizationId = (int) ($order->company_id ?: $order->supplier?->company_id);
        if (! $organizationId || ! $order->supplier) {
            return;
        }

        $company = BooksCompany::forOrganization($organizationId);
        $date = $order->po_date?->toDateString() ?: now()->toDateString();
        $year = $company ? BooksCompany::yearFor($company, $date) : null;
        $branchId = $company ? BooksCompany::branchId($organizationId, $order->branch_id ? (int) $order->branch_id : ($order->supplier->branch_id ? (int) $order->supplier->branch_id : null)) : null;
        if (! $company || ! $year || ! $branchId) {
            throw new \RuntimeException('Purchase order '.$order->po_no.' has no books company, branch, or financial year.');
        }

        $books = BooksPurchaseOrder::query()->find(SyncLinks::acctId(self::KEY, $order));
        if ($books && ! in_array($books->status, ['open', 'draft'], true)) {
            $this->conflict($order, $books, 'dms_to_books', 'Books purchase order is '.$books->status.' and was left unchanged.');

            return;
        }

        $this->customers->push($order->supplier);
        $party = Party::query()->find(SyncLinks::acctId(CustomerSync::KEY, $order->supplier));
        if (! $party) {
            throw new \RuntimeException('Supplier '.$order->supplier_id.' did not sync before the purchase order.');
        }

        $attributes = [
            'company_id' => $company->id,
            'branch_id' => $branchId,
            'financial_year_id' => $year->id,
            'number' => $this->booksNumber((string) $order->po_no, $company->id, $books?->id),
            'order_date' => $date,
            'supplier_ledger_id' => $party->ledger_id,
            'purchase_ledger_id' => BooksCompany::purchaseLedger($company)->id,
            'narration' => $order->notes,
            'subtotal' => $order->subtotal ?? 0,
            'discount_total' => 0,
            'tax_total' => $order->tax_amount ?? 0,
            'grand_total' => $order->grand_total ?? 0,
            'status' => $order->status === 'draft' ? 'open' : (in_array($order->status, ['closed', 'cancelled'], true) ? $order->status : 'open'),
        ];

        DB::transaction(function () use (&$books, $attributes, $order) {
            $books ? $books->update($attributes) : $books = BooksPurchaseOrder::query()->create($attributes);
            $books->lines()->delete();
            $number = 1;
            foreach ($order->items as $item) {
                $productId = null;
                if ($item->product) {
                    $this->products->push($item->product);
                    $productId = SyncLinks::acctId(ProductSync::KEY, $item->product);
                }
                PurchaseOrderLine::query()->create([
                    'purchase_order_id' => $books->id,
                    'line_number' => $number++,
                    'item_name' => $item->product?->name ?: 'Item',
                    'product_id' => $productId,
                    'quantity' => $item->quantity,
                    'rate' => $item->unit_cost,
                    'discount' => 0,
                    'tax_amount' => (float) $item->cgst_amount + (float) $item->sgst_amount,
                    'line_total' => $item->line_total ?? 0,
                ]);
            }
            SyncLinks::store(self::KEY, $order, $books);
        });
    }

    public function pull(BooksPurchaseOrder $books): void
    {
        $organizationId = BooksCompany::organizationId($books->company_id);
        if (! $organizationId) {
            return;
        }

        $order = PurchaseOrder::query()->find(SyncLinks::dmsId(self::KEY, $books));
        if ($order && ! in_array($order->status, ['draft', 'pending_approval'], true)) {
            $this->conflict($order, $books, 'books_to_dms', 'DMS purchase order '.$order->po_no.' is '.$order->status.' and was left unchanged.');

            return;
        }

        $party = Party::query()->where('ledger_id', $books->supplier_ledger_id)->first();
        if (! $party) {
            throw new \RuntimeException('Books purchase order '.$books->number.' has no supplier.');
        }
        $this->customers->pull($party);
        $supplierId = SyncLinks::dmsId(CustomerSync::KEY, $party);
        if (! $supplierId) {
            throw new \RuntimeException('Party '.$party->id.' did not sync to a DMS supplier.');
        }

        $books->loadMissing('lines');
        $attributes = [
            'company_id' => $organizationId,
            'supplier_id' => $supplierId,
            'po_no' => $this->dmsNumber($books, $order),
            'po_date' => $books->order_date->toDateString(),
            'status' => $books->status === 'closed' ? 'closed' : ($books->status === 'cancelled' ? 'cancelled' : 'draft'),
            'subtotal' => $books->subtotal,
            'tax_amount' => $books->tax_total,
            'grand_total' => $books->grand_total,
            'notes' => $books->narration,
        ];

        DB::transaction(function () use (&$order, $attributes, $books) {
            $order ? $order->update($attributes) : $order = PurchaseOrder::query()->create($attributes);
            $order->items()->delete();
            foreach ($books->lines as $line) {
                if (! $line->product_id) {
                    throw new \RuntimeException('Books purchase order '.$books->number.' has a line without a product.');
                }
                $booksProduct = BooksProduct::query()->find($line->product_id);
                $this->products->pull($booksProduct);
                $productId = SyncLinks::dmsId(ProductSync::KEY, $booksProduct);
                $product = Product::query()->find($productId);
                if (! $product?->base_uom_id) {
                    throw new \RuntimeException('Product '.$productId.' has no unit, so the purchase order was not copied.');
                }
                PurchaseOrderItem::query()->create([
                    'purchase_order_id' => $order->id,
                    'product_id' => $productId,
                    'uom_id' => $product->base_uom_id,
                    'quantity' => $line->quantity,
                    'unit_cost' => $line->rate,
                    'line_total' => $line->line_total,
                    'cgst_amount' => $line->tax_amount,
                    'sgst_amount' => 0,
                ]);
            }
            SyncLinks::store(self::KEY, $order, $books);
        });
    }

    public function remove(Model $source): void
    {
        if ($source instanceof PurchaseOrder) {
            $books = BooksPurchaseOrder::query()->find(SyncLinks::acctId(self::KEY, $source));
            if (! $books) {
                SyncLinks::forget(self::KEY, $source);

                return;
            }
            if (! in_array($books->status, ['open', 'draft'], true)) {
                $this->conflict($source, $books, 'dms_to_books', 'Books purchase order is '.$books->status.', so the delete was not copied.');

                return;
            }
            $books->lines()->delete();
            $books->delete();
            SyncLinks::forget(self::KEY, $source);

            return;
        }

        if (! $source instanceof BooksPurchaseOrder) {
            return;
        }

        $order = PurchaseOrder::query()->find(SyncLinks::dmsId(self::KEY, $source));
        if (! $order) {
            SyncLinks::forget(self::KEY, $source);

            return;
        }
        if (! in_array($order->status, ['draft', 'pending_approval'], true)) {
            $this->conflict($order, $source, 'books_to_dms', 'DMS purchase order is '.$order->status.', so the delete was not copied.');

            return;
        }
        $order->items()->delete();
        $order->delete();
        SyncLinks::forget(self::KEY, $source);
    }

    private function booksNumber(string $preferred, int $companyId, ?int $ignoreId): string
    {
        $preferred = mb_substr($preferred, 0, 30);
        $taken = BooksPurchaseOrder::query()
            ->where('company_id', $companyId)
            ->where('number', $preferred)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();

        return $taken ? mb_substr($preferred, 0, 24).'-P'.($ignoreId ?: 'N') : $preferred;
    }

    private function dmsNumber(BooksPurchaseOrder $books, ?PurchaseOrder $order): string
    {
        $preferred = mb_substr((string) $books->number, 0, 40);
        $taken = PurchaseOrder::query()
            ->where('po_no', $preferred)
            ->when($order, fn ($query) => $query->whereKeyNot($order->id))
            ->exists();

        return $taken ? mb_substr($preferred, 0, 32).'-B'.$books->id : $preferred;
    }

    private function conflict(PurchaseOrder $order, BooksPurchaseOrder $books, string $direction, string $message): void
    {
        SyncLinks::store(self::KEY, $order, $books, 'conflict', $message);
        SyncFailureLogger::write(self::KEY, $direction, $direction === 'dms_to_books' ? $order : $books, $message);
    }
}
