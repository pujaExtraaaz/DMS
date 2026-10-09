<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Master\Models\Product;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\PurchaseInvoiceItem;
use App\Domains\Sync\Support\BooksCompany;
use App\Domains\Sync\Support\BooksPoster;
use App\Domains\Sync\Support\SyncFailureLogger;
use App\Domains\Sync\Support\SyncLinks;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Tally\Accounting\VoucherStatus;
use Tally\Invoicing\InvoiceKind;
use Tally\Models\Invoice as BooksInvoice;
use Tally\Models\InvoiceLine;
use Tally\Models\Party;
use Tally\Models\Product as BooksProduct;

class PurchaseInvoiceSync
{
    public const KEY = 'purchase_invoice';

    public function __construct(
        private readonly CustomerSync $customers,
        private readonly ProductSync $products,
        private readonly WarehouseSync $warehouses,
    ) {}

    public function sync(Model $model): void
    {
        if ($model instanceof PurchaseInvoice) {
            $this->push($model);

            return;
        }

        if ($model instanceof BooksInvoice && $model->kind === InvoiceKind::Purchase) {
            $this->pull($model);
        }
    }

    public function push(PurchaseInvoice $invoice): void
    {
        $invoice->loadMissing(['supplier', 'items.product', 'warehouse']);
        $organizationId = (int) ($invoice->supplier?->company_id ?: $invoice->warehouse?->company_id);
        if (! $organizationId || ! $invoice->supplier) {
            return;
        }

        $company = BooksCompany::forOrganization($organizationId);
        $date = $invoice->invoice_date?->toDateString() ?: now()->toDateString();
        $year = $company ? BooksCompany::yearFor($company, $date) : null;
        $branchId = $company ? BooksCompany::branchId($organizationId, $invoice->supplier->branch_id ? (int) $invoice->supplier->branch_id : null) : null;
        if (! $company || ! $year || ! $branchId) {
            throw new \RuntimeException('Purchase invoice '.$invoice->invoice_no.' has no books company, branch, or financial year.');
        }

        $books = BooksInvoice::query()->find(SyncLinks::acctId(self::KEY, $invoice));
        if ($books && $books->status !== VoucherStatus::Draft) {
            $this->conflict($invoice, $books, 'dms_to_books', 'Books purchase invoice is '.$books->status->value.' and was left unchanged.');

            return;
        }

        $this->customers->push($invoice->supplier);
        $party = Party::query()->find(SyncLinks::acctId(CustomerSync::KEY, $invoice->supplier));
        if (! $party) {
            throw new \RuntimeException('Supplier '.$invoice->supplier_id.' did not sync to a books party.');
        }

        $attributes = [
            'company_id' => $company->id,
            'branch_id' => $branchId,
            'financial_year_id' => $year->id,
            'kind' => InvoiceKind::Purchase,
            'invoice_number' => $this->booksNumber((string) $invoice->invoice_no, $company->id, $books?->id),
            'invoice_date' => $date,
            'party_ledger_id' => $party->ledger_id,
            'account_ledger_id' => BooksCompany::purchaseLedger($company)->id,
            'reference_number' => $invoice->supplier_invoice_no,
            'narration' => $invoice->notes,
            'status' => VoucherStatus::Draft,
            'subtotal' => $invoice->subtotal ?? 0,
            'discount_total' => 0,
            'tax_total' => $invoice->tax_amount ?? 0,
            'grand_total' => $invoice->grand_total ?? 0,
        ];

        DB::transaction(function () use (&$books, $attributes, $invoice, $company, $organizationId) {
            $books ? $books->update($attributes) : $books = BooksInvoice::query()->create($attributes);
            $books->lines()->delete();
            $number = 1;
            $godownId = $this->godownId($invoice->warehouse, $company, $organizationId);
            foreach ($invoice->items as $item) {
                $productId = null;
                if ($item->product) {
                    $this->products->push($item->product);
                    $productId = SyncLinks::acctId(ProductSync::KEY, $item->product);
                }
                InvoiceLine::query()->create([
                    'invoice_id' => $books->id,
                    'line_number' => $number++,
                    'item_name' => $item->product?->name ?: 'Item',
                    'product_id' => $productId,
                    'godown_id' => $godownId,
                    'quantity' => $item->quantity,
                    'rate' => $item->unit_cost,
                    'discount' => 0,
                    'tax_amount' => (float) $item->cgst_amount + (float) $item->sgst_amount,
                    'line_total' => $item->line_total ?? 0,
                ]);
            }
            SyncLinks::store(self::KEY, $invoice, $books);
            if ($invoice->status === 'posted') {
                app(BooksPoster::class)->invoice($books, self::KEY);
            }
        });
    }

    public function pull(BooksInvoice $books): void
    {
        if ($books->kind !== InvoiceKind::Purchase) {
            return;
        }

        $organizationId = BooksCompany::organizationId($books->company_id);
        if (! $organizationId) {
            return;
        }

        $invoice = PurchaseInvoice::query()->find(SyncLinks::dmsId(self::KEY, $books));
        if ($invoice && $invoice->status === 'posted') {
            $this->conflict($invoice, $books, 'books_to_dms', 'DMS purchase invoice '.$invoice->invoice_no.' is posted and was left unchanged.');

            return;
        }

        $party = Party::query()->where('ledger_id', $books->party_ledger_id)->first();
        if (! $party) {
            throw new \RuntimeException('Books purchase invoice '.$books->invoice_number.' has no party.');
        }
        $this->customers->pull($party);
        $supplierId = SyncLinks::dmsId(CustomerSync::KEY, $party);
        if (! $supplierId) {
            throw new \RuntimeException('Party '.$party->id.' did not sync to a DMS supplier.');
        }

        $books->loadMissing('lines');
        $attributes = [
            'supplier_id' => $supplierId,
            'invoice_no' => $this->dmsNumber($books, $invoice),
            'supplier_invoice_no' => $books->reference_number,
            'invoice_date' => $books->invoice_date->toDateString(),
            'status' => $books->status === VoucherStatus::Cancelled ? 'cancelled' : 'draft',
            'subtotal' => $books->subtotal,
            'tax_amount' => $books->tax_total,
            'grand_total' => $books->grand_total,
            'notes' => $books->narration,
        ];

        DB::transaction(function () use (&$invoice, $attributes, $books) {
            if ($invoice) {
                $invoice->forceFill($attributes)->save();
            } else {
                $invoice = new PurchaseInvoice;
                $invoice->forceFill($attributes)->save();
            }
            $invoice->items()->delete();
            foreach ($books->lines as $line) {
                if (! $line->product_id) {
                    throw new \RuntimeException('Books purchase invoice '.$books->invoice_number.' has a line without a product.');
                }
                $booksProduct = BooksProduct::query()->find($line->product_id);
                $this->products->pull($booksProduct);
                $productId = SyncLinks::dmsId(ProductSync::KEY, $booksProduct);
                $product = Product::query()->find($productId);
                if (! $product?->base_uom_id) {
                    throw new \RuntimeException('Product '.$productId.' has no unit, so the purchase invoice was not copied.');
                }
                PurchaseInvoiceItem::query()->create([
                    'purchase_invoice_id' => $invoice->id,
                    'product_id' => $productId,
                    'uom_id' => $product->base_uom_id,
                    'quantity' => $line->quantity,
                    'unit_cost' => $line->rate,
                    'line_total' => $line->line_total,
                    'cgst_amount' => $line->tax_amount,
                    'sgst_amount' => 0,
                ]);
            }
            SyncLinks::store(self::KEY, $invoice, $books);
        });
    }

    public function remove(Model $source): void
    {
        if ($source instanceof PurchaseInvoice) {
            $books = BooksInvoice::query()->find(SyncLinks::acctId(self::KEY, $source));
            if (! $books) {
                SyncLinks::forget(self::KEY, $source);

                return;
            }
            if ($books->status !== VoucherStatus::Draft) {
                $this->conflict($source, $books, 'dms_to_books', 'Books purchase invoice is posted, so the delete was not copied.');

                return;
            }
            $books->lines()->delete();
            $books->delete();
            SyncLinks::forget(self::KEY, $source);

            return;
        }

        if (! $source instanceof BooksInvoice || $source->kind !== InvoiceKind::Purchase) {
            return;
        }

        $invoice = PurchaseInvoice::query()->find(SyncLinks::dmsId(self::KEY, $source));
        if (! $invoice) {
            SyncLinks::forget(self::KEY, $source);

            return;
        }
        if ($invoice->status === 'posted') {
            $this->conflict($invoice, $source, 'books_to_dms', 'DMS purchase invoice is posted, so the delete was not copied.');

            return;
        }
        $invoice->items()->delete();
        $invoice->delete();
        SyncLinks::forget(self::KEY, $source);
    }

    private function godownId(?Warehouse $warehouse, \Tally\Models\Company $company, int $organizationId): int
    {
        if ($warehouse) {
            $this->warehouses->push($warehouse);
            $linked = SyncLinks::acctId(WarehouseSync::KEY, $warehouse);
            if ($linked) {
                return $linked;
            }
        }

        return BooksCompany::defaultGodown($company, $organizationId);
    }

    private function booksNumber(string $preferred, int $companyId, ?int $ignoreId): string
    {
        $preferred = mb_substr($preferred, 0, 30);
        $taken = BooksInvoice::query()
            ->where('company_id', $companyId)
            ->where('invoice_number', $preferred)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();

        return $taken ? mb_substr($preferred, 0, 24).'-P'.($ignoreId ?: 'N') : $preferred;
    }

    private function dmsNumber(BooksInvoice $books, ?PurchaseInvoice $invoice): string
    {
        $preferred = mb_substr($books->invoice_number, 0, 40);
        $taken = PurchaseInvoice::query()
            ->where('invoice_no', $preferred)
            ->when($invoice, fn ($query) => $query->whereKeyNot($invoice->id))
            ->exists();

        return $taken ? mb_substr($preferred, 0, 32).'-B'.$books->id : $preferred;
    }

    private function conflict(PurchaseInvoice $invoice, BooksInvoice $books, string $direction, string $message): void
    {
        SyncLinks::store(self::KEY, $invoice, $books, 'conflict', $message);
        SyncFailureLogger::write(self::KEY, $direction, $direction === 'dms_to_books' ? $invoice : $books, $message);
    }
}
