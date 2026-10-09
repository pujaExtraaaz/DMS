<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Master\Models\Product;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\InvoiceItem;
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
use Tally\Models\Product as BooksProduct;

class InvoiceSync
{
    public const KEY = 'sales_invoice';

    public function __construct(
        private readonly CustomerSync $customers,
        private readonly ProductSync $products,
    ) {}

    public function sync(Model $model): void
    {
        if ($model instanceof Invoice) {
            $this->push($model);

            return;
        }

        if ($model instanceof BooksInvoice && $model->kind === InvoiceKind::Sales) {
            $this->pull($model);
        }
    }

    public function push(Invoice $invoice): void
    {
        $invoice->loadMissing(['customer', 'items.product']);
        $customer = $invoice->customer;
        if (! $customer?->company_id) {
            return;
        }

        $company = BooksCompany::forOrganization((int) $customer->company_id);
        $year = $company ? BooksCompany::yearFor($company, $invoice->invoice_date->toDateString()) : null;
        $branchId = $company ? BooksCompany::branchId((int) $customer->company_id, $customer->branch_id ? (int) $customer->branch_id : null) : null;
        if (! $company || ! $year || ! $branchId) {
            throw new \RuntimeException('Sales invoice '.$invoice->invoice_no.' has no books company, branch, or financial year.');
        }

        $books = BooksInvoice::query()->find(SyncLinks::acctId(self::KEY, $invoice));
        if ($books && $books->status !== VoucherStatus::Draft) {
            SyncLinks::store(self::KEY, $invoice, $books, 'conflict', 'Books invoice is '.$books->status->value.' and was left unchanged.');
            SyncFailureLogger::write(self::KEY, 'dms_to_books', $invoice, 'Books invoice is '.$books->status->value.' and was left unchanged.');

            return;
        }

        $this->customers->push($customer);
        $party = \Tally\Models\Party::query()->find(SyncLinks::acctId(CustomerSync::KEY, $customer));
        if (! $party) {
            throw new \RuntimeException('Customer '.$customer->id.' did not sync to a books party.');
        }

        $attributes = [
            'company_id' => $company->id,
            'branch_id' => $branchId,
            'financial_year_id' => $year->id,
            'kind' => InvoiceKind::Sales,
            'invoice_number' => mb_substr($invoice->invoice_no, 0, 30),
            'invoice_date' => $invoice->invoice_date->toDateString(),
            'party_ledger_id' => $party->ledger_id,
            'account_ledger_id' => BooksCompany::salesLedger($company)->id,
            'reference_number' => $invoice->reference_no,
            'narration' => $invoice->notes,
            'status' => VoucherStatus::Draft,
            'subtotal' => $invoice->subtotal,
            'discount_total' => $invoice->discount_amount,
            'tax_total' => $invoice->tax_amount,
            'grand_total' => $invoice->grand_total,
        ];

        $godownId = BooksCompany::defaultGodown($company, (int) $customer->company_id);

        DB::transaction(function () use (&$books, $attributes, $invoice, $godownId) {
            $books ? $books->update($attributes) : $books = BooksInvoice::query()->create($attributes);
            $books->lines()->delete();
            $number = 1;
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
                    'godown_id' => $productId ? $godownId : null,
                    'quantity' => $item->quantity,
                    'rate' => $item->unit_price,
                    'discount' => $item->discount_amount,
                    'tax_amount' => $item->tax_amount,
                    'line_total' => $item->line_total,
                ]);
            }
            SyncLinks::store(self::KEY, $invoice, $books);
            if (in_array($invoice->status, ['issued', 'paid', 'partial'], true)) {
                app(BooksPoster::class)->invoice($books, self::KEY);
            }
        });
    }

    public function remove(Model $source): void
    {
        if ($source instanceof Invoice) {
            $books = BooksInvoice::query()->find(SyncLinks::acctId(self::KEY, $source));
            if (! $books) {
                SyncLinks::forget(self::KEY, $source);

                return;
            }
            if ($books->status !== VoucherStatus::Draft) {
                SyncLinks::store(self::KEY, $source, $books, 'conflict', 'Books invoice is posted, so the delete was not copied.');
                SyncFailureLogger::write(self::KEY, 'dms_to_books', $source, 'Books invoice is posted, so the delete was not copied.');

                return;
            }
            $books->lines()->delete();
            $books->delete();
            SyncLinks::forget(self::KEY, $source);

            return;
        }

        if (! $source instanceof BooksInvoice || $source->kind !== InvoiceKind::Sales) {
            return;
        }

        $invoice = Invoice::query()->find(SyncLinks::dmsId(self::KEY, $source));
        if (! $invoice) {
            SyncLinks::forget(self::KEY, $source);

            return;
        }
        if ($invoice->paid_amount > 0 || in_array($invoice->status, ['paid', 'partial', 'cancelled'], true)) {
            SyncLinks::store(self::KEY, $invoice, $source, 'conflict', 'DMS invoice has payments or is closed, so the delete was not copied.');
            SyncFailureLogger::write(self::KEY, 'books_to_dms', $source, 'DMS invoice has payments or is closed, so the delete was not copied.');

            return;
        }
        $invoice->items()->delete();
        $invoice->delete();
        SyncLinks::forget(self::KEY, $source);
    }

    public function pull(BooksInvoice $books): void
    {
        if ($books->kind !== InvoiceKind::Sales) {
            return;
        }

        $organizationId = BooksCompany::organizationId($books->company_id);
        if (! $organizationId) {
            return;
        }

        $invoice = Invoice::query()->find(SyncLinks::dmsId(self::KEY, $books));
        if ($invoice && ($invoice->paid_amount > 0 || in_array($invoice->status, ['paid', 'partial', 'cancelled'], true))) {
            SyncLinks::store(self::KEY, $invoice, $books, 'conflict', 'DMS invoice '.$invoice->invoice_no.' already has payments or is closed.');
            SyncFailureLogger::write(self::KEY, 'books_to_dms', $books, 'DMS invoice '.$invoice->invoice_no.' already has payments or is closed.');

            return;
        }

        $party = \Tally\Models\Party::query()->where('ledger_id', $books->party_ledger_id)->first();
        if (! $party) {
            throw new \RuntimeException('Books invoice '.$books->invoice_number.' has no party.');
        }
        $this->customers->pull($party);
        $customerId = SyncLinks::dmsId(CustomerSync::KEY, $party);
        if (! $customerId) {
            throw new \RuntimeException('Party '.$party->id.' did not sync to a DMS customer.');
        }

        $books->loadMissing('lines');
        foreach ($books->lines as $line) {
            if (! $line->product_id) {
                throw new \RuntimeException('Books invoice '.$books->invoice_number.' has a line without a product, so it was not copied into DMS.');
            }
        }

        $attributes = [
            'invoice_no' => $this->invoiceNumber($books, $invoice),
            'customer_id' => $customerId,
            'invoice_date' => $books->invoice_date->toDateString(),
            'status' => $books->status === VoucherStatus::Cancelled ? 'cancelled' : ($invoice?->status ?: 'issued'),
            'subtotal' => $books->subtotal,
            'discount_amount' => $books->discount_total,
            'tax_amount' => $books->tax_total,
            'grand_total' => $books->grand_total,
            'notes' => $books->narration,
            'reference_no' => $books->reference_number,
        ];

        DB::transaction(function () use (&$invoice, $attributes, $books) {
            $invoice ? $invoice->update($attributes) : $invoice = Invoice::query()->create($attributes + ['paid_amount' => 0]);
            $invoice->items()->delete();
            foreach ($books->lines as $line) {
                $booksProduct = BooksProduct::query()->find($line->product_id);
                $this->products->pull($booksProduct);
                $productId = SyncLinks::dmsId(ProductSync::KEY, $booksProduct);
                $product = Product::query()->find($productId);
                InvoiceItem::query()->create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $productId,
                    'uom_id' => $product?->base_uom_id,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->rate,
                    'discount_amount' => $line->discount,
                    'tax_amount' => $line->tax_amount,
                    'line_total' => $line->line_total,
                ]);
            }
            SyncLinks::store(self::KEY, $invoice, $books);
        });
    }

    private function invoiceNumber(BooksInvoice $books, ?Invoice $invoice): string
    {
        $preferred = mb_substr($books->invoice_number, 0, 30);
        $taken = Invoice::query()
            ->where('invoice_no', $preferred)
            ->when($invoice, fn ($query) => $query->where('id', '!=', $invoice->id))
            ->exists();

        if (! $taken) {
            return $preferred;
        }

        return mb_substr($preferred, 0, 22).'-B'.$books->id;
    }
}
