<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Master\Models\Product;
use App\Domains\Payment\Models\CreditNote;
use App\Domains\Payment\Models\CreditNoteItem;
use App\Domains\Sales\Models\Invoice;
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

class CreditNoteSync
{
    public const KEY = 'credit_note';

    public function __construct(
        private readonly CustomerSync $customers,
        private readonly ProductSync $products,
    ) {}

    public function sync(Model $model): void
    {
        if ($model instanceof CreditNote) {
            $this->push($model);

            return;
        }

        if ($model instanceof BooksInvoice && $model->kind === InvoiceKind::CreditNote) {
            $this->pull($model);
        }
    }

    public function push(CreditNote $note): void
    {
        $note->loadMissing(['customer', 'items.product', 'invoice']);
        $customer = $note->customer;
        if (! $customer?->company_id) {
            return;
        }

        $company = BooksCompany::forOrganization((int) $customer->company_id);
        $date = $note->credit_note_date?->toDateString() ?: now()->toDateString();
        $year = $company ? BooksCompany::yearFor($company, $date) : null;
        $branchId = $company ? BooksCompany::branchId((int) $customer->company_id, $customer->branch_id ? (int) $customer->branch_id : null) : null;
        if (! $company || ! $year || ! $branchId) {
            throw new \RuntimeException('Credit note '.$note->credit_note_no.' has no books company, branch, or financial year.');
        }

        $books = BooksInvoice::query()->find(SyncLinks::acctId(self::KEY, $note));
        if ($books && $books->status !== VoucherStatus::Draft) {
            $this->conflict($note, $books, 'dms_to_books', 'Books credit note is '.$books->status->value.' and was left unchanged.');

            return;
        }

        $this->customers->push($customer);
        $party = Party::query()->find(SyncLinks::acctId(CustomerSync::KEY, $customer));
        if (! $party) {
            throw new \RuntimeException('Customer '.$customer->id.' did not sync before the credit note.');
        }

        $attributes = [
            'company_id' => $company->id,
            'branch_id' => $branchId,
            'financial_year_id' => $year->id,
            'kind' => InvoiceKind::CreditNote,
            'invoice_number' => $this->booksNumber((string) $note->credit_note_no, $company->id, $books?->id),
            'invoice_date' => $date,
            'party_ledger_id' => $party->ledger_id,
            'account_ledger_id' => BooksCompany::salesLedger($company)->id,
            'reference_number' => $note->invoice?->invoice_no,
            'narration' => $note->reason ?: $note->notes,
            'status' => VoucherStatus::Draft,
            'subtotal' => $note->subtotal ?? 0,
            'discount_total' => 0,
            'tax_total' => $note->tax_amount ?? 0,
            'grand_total' => $note->grand_total ?? 0,
        ];

        $godownId = BooksCompany::defaultGodown($company, (int) $customer->company_id);

        DB::transaction(function () use (&$books, $attributes, $note, $godownId) {
            $books ? $books->update($attributes) : $books = BooksInvoice::query()->create($attributes);
            $books->lines()->delete();
            $number = 1;
            foreach ($note->items as $item) {
                $productId = null;
                if ($item->product) {
                    $this->products->push($item->product);
                    $productId = SyncLinks::acctId(ProductSync::KEY, $item->product);
                }
                InvoiceLine::query()->create([
                    'invoice_id' => $books->id,
                    'line_number' => $number++,
                    'item_name' => $item->description ?: ($item->product?->name ?: 'Item'),
                    'product_id' => $productId,
                    'godown_id' => $productId ? $godownId : null,
                    'quantity' => $item->quantity,
                    'rate' => $item->unit_price,
                    'discount' => 0,
                    'tax_amount' => $item->tax_amount ?? 0,
                    'line_total' => $item->line_total ?? 0,
                ]);
            }
            SyncLinks::store(self::KEY, $note, $books);
            if (in_array($note->status, ['approved', 'posted'], true)) {
                app(BooksPoster::class)->invoice($books, self::KEY);
            }
        });
    }

    public function pull(BooksInvoice $books): void
    {
        if ($books->kind !== InvoiceKind::CreditNote) {
            return;
        }

        $organizationId = BooksCompany::organizationId($books->company_id);
        if (! $organizationId) {
            return;
        }

        $note = CreditNote::query()->find(SyncLinks::dmsId(self::KEY, $books));
        if ($note && in_array($note->status, ['approved', 'posted'], true)) {
            $this->conflict($note, $books, 'books_to_dms', 'DMS credit note '.$note->credit_note_no.' is '.$note->status.' and was left unchanged.');

            return;
        }

        $party = Party::query()->where('ledger_id', $books->party_ledger_id)->first();
        if (! $party) {
            throw new \RuntimeException('Books credit note '.$books->invoice_number.' has no party.');
        }
        $this->customers->pull($party);
        $customerId = SyncLinks::dmsId(CustomerSync::KEY, $party);
        if (! $customerId) {
            throw new \RuntimeException('Party '.$party->id.' did not sync to a DMS customer.');
        }

        $invoiceId = null;
        if ($books->reference_number) {
            $invoiceId = Invoice::query()->where('invoice_no', $books->reference_number)->value('id');
        }

        $books->loadMissing('lines');
        $attributes = [
            'credit_note_no' => $this->dmsNumber($books, $note),
            'customer_id' => $customerId,
            'invoice_id' => $invoiceId,
            'credit_note_date' => $books->invoice_date->toDateString(),
            'reason' => $books->narration,
            'status' => $books->status === VoucherStatus::Cancelled ? 'cancelled' : 'draft',
            'subtotal' => $books->subtotal,
            'tax_amount' => $books->tax_total,
            'grand_total' => $books->grand_total,
            'notes' => $books->narration,
        ];

        DB::transaction(function () use (&$note, $attributes, $books) {
            $note ? $note->update($attributes) : $note = CreditNote::query()->create($attributes);
            $note->items()->delete();
            foreach ($books->lines as $line) {
                $productId = null;
                $uomId = null;
                if ($line->product_id) {
                    $booksProduct = BooksProduct::query()->find($line->product_id);
                    if ($booksProduct) {
                        $this->products->pull($booksProduct);
                        $productId = SyncLinks::dmsId(ProductSync::KEY, $booksProduct);
                        $uomId = Product::query()->whereKey($productId)->value('base_uom_id');
                    }
                }
                CreditNoteItem::query()->create([
                    'credit_note_id' => $note->id,
                    'product_id' => $productId,
                    'uom_id' => $uomId,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->rate,
                    'tax_amount' => $line->tax_amount,
                    'line_total' => $line->line_total,
                    'description' => $line->item_name,
                ]);
            }
            SyncLinks::store(self::KEY, $note, $books);
        });
    }

    public function remove(Model $source): void
    {
        if ($source instanceof CreditNote) {
            $books = BooksInvoice::query()->find(SyncLinks::acctId(self::KEY, $source));
            if (! $books) {
                SyncLinks::forget(self::KEY, $source);

                return;
            }
            if ($books->status !== VoucherStatus::Draft) {
                $this->conflict($source, $books, 'dms_to_books', 'Books credit note is posted, so the delete was not copied.');

                return;
            }
            $books->lines()->delete();
            $books->delete();
            SyncLinks::forget(self::KEY, $source);

            return;
        }

        if (! $source instanceof BooksInvoice || $source->kind !== InvoiceKind::CreditNote) {
            return;
        }

        $note = CreditNote::query()->find(SyncLinks::dmsId(self::KEY, $source));
        if (! $note) {
            SyncLinks::forget(self::KEY, $source);

            return;
        }
        if (in_array($note->status, ['approved', 'posted'], true)) {
            $this->conflict($note, $source, 'books_to_dms', 'DMS credit note is '.$note->status.', so the delete was not copied.');

            return;
        }
        $note->items()->delete();
        $note->delete();
        SyncLinks::forget(self::KEY, $source);
    }

    private function booksNumber(string $preferred, int $companyId, ?int $ignoreId): string
    {
        $preferred = mb_substr($preferred, 0, 30);
        $taken = BooksInvoice::query()
            ->where('company_id', $companyId)
            ->where('invoice_number', $preferred)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();

        return $taken ? mb_substr($preferred, 0, 24).'-C'.($ignoreId ?: 'N') : $preferred;
    }

    private function dmsNumber(BooksInvoice $books, ?CreditNote $note): string
    {
        $preferred = mb_substr($books->invoice_number, 0, 30);
        $taken = CreditNote::query()
            ->where('credit_note_no', $preferred)
            ->when($note, fn ($query) => $query->whereKeyNot($note->id))
            ->exists();

        return $taken ? mb_substr($preferred, 0, 22).'-B'.$books->id : $preferred;
    }

    private function conflict(CreditNote $note, BooksInvoice $books, string $direction, string $message): void
    {
        SyncLinks::store(self::KEY, $note, $books, 'conflict', $message);
        SyncFailureLogger::write(self::KEY, $direction, $direction === 'dms_to_books' ? $note : $books, $message);
    }
}
