<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Purchasing\Models\DebitNote;
use App\Domains\Purchasing\Models\DebitNoteItem;
use App\Domains\Purchasing\Models\PurchaseInvoice;
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

class DebitNoteSync
{
    public const KEY = 'debit_note';

    public function __construct(
        private readonly CustomerSync $customers,
        private readonly ProductSync $products,
    ) {}

    public function sync(Model $model): void
    {
        if ($model instanceof DebitNote) {
            $this->push($model);

            return;
        }

        if ($model instanceof BooksInvoice && $model->kind === InvoiceKind::DebitNote) {
            $this->pull($model);
        }
    }

    public function push(DebitNote $note): void
    {
        $note->loadMissing(['supplier', 'items.product', 'purchaseInvoice']);
        $supplier = $note->supplier;
        $orgCompanyId = (int) ($note->company_id ?: $supplier?->company_id);
        if (! $orgCompanyId || ! $supplier) {
            return;
        }

        $company = BooksCompany::forOrganization($orgCompanyId);
        $date = $note->debit_note_date?->toDateString() ?: now()->toDateString();
        $year = $company ? BooksCompany::yearFor($company, $date) : null;
        $branchId = $company ? BooksCompany::branchId($orgCompanyId, $supplier->branch_id ? (int) $supplier->branch_id : null) : null;
        if (! $company || ! $year || ! $branchId) {
            throw new \RuntimeException('Debit note '.$note->debit_note_no.' has no books company, branch, or financial year.');
        }

        $books = BooksInvoice::query()->find(SyncLinks::acctId(self::KEY, $note));
        if ($books && $books->status !== VoucherStatus::Draft) {
            $this->conflict($note, $books, 'dms_to_books', 'Books debit note is '.$books->status->value.' and was left unchanged.');

            return;
        }

        $this->customers->push($supplier);
        $party = Party::query()->find(SyncLinks::acctId(CustomerSync::KEY, $supplier));
        if (! $party) {
            throw new \RuntimeException('Supplier '.$supplier->id.' did not sync before the debit note.');
        }

        $attributes = [
            'company_id' => $company->id,
            'branch_id' => $branchId,
            'financial_year_id' => $year->id,
            'kind' => InvoiceKind::DebitNote,
            'invoice_number' => $this->booksNumber((string) $note->debit_note_no, $company->id, $books?->id),
            'invoice_date' => $date,
            'party_ledger_id' => $party->ledger_id,
            'account_ledger_id' => BooksCompany::purchaseLedger($company)->id,
            'reference_number' => $note->purchaseInvoice?->invoice_no,
            'narration' => $note->reason ?: $note->notes,
            'status' => VoucherStatus::Draft,
            'subtotal' => $note->subtotal ?? 0,
            'discount_total' => 0,
            'tax_total' => $note->tax_amount ?? 0,
            'grand_total' => $note->grand_total ?? 0,
        ];

        $godownId = BooksCompany::defaultGodown($company, $orgCompanyId);

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
                    'rate' => $item->unit_cost,
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
        if ($books->kind !== InvoiceKind::DebitNote) {
            return;
        }

        $organizationId = BooksCompany::organizationId($books->company_id);
        if (! $organizationId) {
            return;
        }

        $note = DebitNote::query()->find(SyncLinks::dmsId(self::KEY, $books));
        if ($note && in_array($note->status, ['approved', 'posted'], true)) {
            $this->conflict($note, $books, 'books_to_dms', 'DMS debit note '.$note->debit_note_no.' is '.$note->status.' and was left unchanged.');

            return;
        }

        $party = Party::query()->where('ledger_id', $books->party_ledger_id)->first();
        if (! $party) {
            throw new \RuntimeException('Books debit note '.$books->invoice_number.' has no party.');
        }
        $this->customers->pull($party);
        $supplierId = SyncLinks::dmsId(CustomerSync::KEY, $party);
        if (! $supplierId) {
            throw new \RuntimeException('Party '.$party->id.' did not sync to a DMS supplier.');
        }

        $purchaseInvoiceId = null;
        if ($books->reference_number) {
            $purchaseInvoiceId = PurchaseInvoice::query()->where('invoice_no', $books->reference_number)->value('id');
        }

        $books->loadMissing('lines');
        $attributes = [
            'debit_note_no' => $this->dmsNumber($books, $note),
            'company_id' => $organizationId,
            'supplier_id' => $supplierId,
            'purchase_invoice_id' => $purchaseInvoiceId,
            'debit_note_date' => $books->invoice_date->toDateString(),
            'reason' => 'other',
            'status' => $books->status === VoucherStatus::Cancelled ? 'cancelled' : 'draft',
            'subtotal' => $books->subtotal,
            'tax_amount' => $books->tax_total,
            'grand_total' => $books->grand_total,
            'notes' => $books->narration,
        ];

        DB::transaction(function () use (&$note, $attributes, $books) {
            $note ? $note->update($attributes) : $note = DebitNote::query()->create($attributes);
            $note->items()->delete();
            foreach ($books->lines as $line) {
                $productId = null;
                $uomId = null;
                if ($line->product_id) {
                    $booksProduct = BooksProduct::query()->find($line->product_id);
                    if ($booksProduct) {
                        $this->products->pull($booksProduct);
                        $productId = SyncLinks::dmsId(ProductSync::KEY, $booksProduct);
                        $uomId = \App\Domains\Master\Models\Product::query()->whereKey($productId)->value('base_uom_id');
                    }
                }
                DebitNoteItem::query()->create([
                    'debit_note_id' => $note->id,
                    'product_id' => $productId,
                    'uom_id' => $uomId,
                    'quantity' => $line->quantity,
                    'unit_cost' => $line->rate,
                    'tax_amount' => $line->tax_amount,
                    'line_total' => $line->line_total,
                    'description' => $line->item_name,
                ]);
            }
            SyncLinks::store(self::KEY, $note, $books);
        });
    }

    public function cancelBooksTwin(DebitNote $note): void
    {
        $books = BooksInvoice::query()->find(SyncLinks::acctId(self::KEY, $note));
        if (! $books) {
            return;
        }

        try {
            if ($books->status === VoucherStatus::Draft) {
                $books->lines()->delete();
                $books->delete();
                SyncLinks::forget(self::KEY, $note);

                return;
            }

            if ($books->isCancelled()) {
                return;
            }

            app(\Tally\Invoicing\InvoiceService::class)->cancel($books->fresh());
        } catch (\Throwable $e) {
            SyncFailureLogger::write(self::KEY, 'dms_to_books', $note, $e);
        }
    }

    public function remove(Model $source): void
    {
        if ($source instanceof DebitNote) {
            $books = BooksInvoice::query()->find(SyncLinks::acctId(self::KEY, $source));
            if (! $books) {
                SyncLinks::forget(self::KEY, $source);

                return;
            }
            if ($books->status !== VoucherStatus::Draft) {
                $this->conflict($source, $books, 'dms_to_books', 'Books debit note is posted, so the delete was not copied.');

                return;
            }
            $books->lines()->delete();
            $books->delete();
            SyncLinks::forget(self::KEY, $source);

            return;
        }

        if (! $source instanceof BooksInvoice || $source->kind !== InvoiceKind::DebitNote) {
            return;
        }

        $note = DebitNote::query()->find(SyncLinks::dmsId(self::KEY, $source));
        if (! $note) {
            SyncLinks::forget(self::KEY, $source);

            return;
        }
        if (in_array($note->status, ['approved', 'posted'], true)) {
            $this->conflict($note, $source, 'books_to_dms', 'DMS debit note is '.$note->status.', so the delete was not copied.');

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

        return $taken ? mb_substr($preferred, 0, 24).'-D'.($ignoreId ?: 'N') : $preferred;
    }

    private function dmsNumber(BooksInvoice $books, ?DebitNote $note): string
    {
        $preferred = mb_substr($books->invoice_number, 0, 30);
        $taken = DebitNote::query()
            ->where('debit_note_no', $preferred)
            ->when($note, fn ($query) => $query->whereKeyNot($note->id))
            ->exists();

        return $taken ? mb_substr($preferred, 0, 22).'-B'.$books->id : $preferred;
    }

    private function conflict(DebitNote $note, BooksInvoice $books, string $direction, string $message): void
    {
        SyncLinks::store(self::KEY, $note, $books, 'conflict', $message);
        SyncFailureLogger::write(self::KEY, $direction, $direction === 'dms_to_books' ? $note : $books, $message);
    }
}
