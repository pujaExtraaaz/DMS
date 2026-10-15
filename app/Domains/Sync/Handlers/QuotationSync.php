<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Sales\Models\Quotation;
use App\Domains\Sync\Support\BooksCompany;
use App\Domains\Sync\Support\SyncFailureLogger;
use App\Domains\Sync\Support\SyncLinks;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Tally\Models\Party;
use Tally\Models\Quotation as BooksQuotation;
use Tally\Models\QuotationLine;

class QuotationSync
{
    public const KEY = 'quotation';

    public function __construct(
        private readonly CustomerSync $customers,
        private readonly ProductSync $products,
    ) {}

    public function sync(Model $model): void
    {
        if ($model instanceof Quotation) {
            $this->push($model);
        }
    }

    public function push(Quotation $quote): void
    {
        $quote->loadMissing(['customer', 'items.product']);
        $organizationId = (int) $quote->customer?->company_id;
        if (! $organizationId || ! $quote->customer) {
            return;
        }

        $company = BooksCompany::forOrganization($organizationId);
        $date = $quote->quotation_date?->toDateString() ?: now()->toDateString();
        $year = $company ? BooksCompany::yearFor($company, $date) : null;
        $branchId = $company ? BooksCompany::branchId($organizationId, $quote->customer->branch_id ? (int) $quote->customer->branch_id : null) : null;
        if (! $company || ! $year || ! $branchId) {
            throw new \RuntimeException('Quotation '.$quote->quotation_no.' has no books company/branch/year.');
        }

        $books = BooksQuotation::query()->find(SyncLinks::acctId(self::KEY, $quote));
        if ($books && ! in_array($books->status, ['draft', 'open', 'sent'], true)) {
            SyncLinks::store(self::KEY, $quote, $books, 'conflict', 'Books quotation is '.$books->status);
            SyncFailureLogger::write(self::KEY, 'dms_to_books', $quote, 'Books quotation is '.$books->status);

            return;
        }

        $this->customers->push($quote->customer);
        $party = Party::query()->find(SyncLinks::acctId(CustomerSync::KEY, $quote->customer));
        if (! $party) {
            throw new \RuntimeException('Customer did not sync before quotation '.$quote->quotation_no);
        }

        $status = match ($quote->status) {
            'converted' => 'converted',
            'accepted', 'approved' => 'accepted',
            'rejected', 'cancelled' => 'cancelled',
            default => 'open',
        };

        $attributes = [
            'company_id' => $company->id,
            'branch_id' => $branchId,
            'financial_year_id' => $year->id,
            'number' => mb_substr((string) $quote->quotation_no, 0, 30),
            'quote_date' => $date,
            'valid_until' => $quote->valid_until?->toDateString(),
            'customer_ledger_id' => $party->ledger_id,
            'reference_number' => $quote->quotation_no,
            'narration' => $quote->notes,
            'status' => $status,
            'subtotal' => $quote->subtotal,
            'discount_total' => $quote->discount_amount,
            'tax_total' => $quote->tax_amount,
            'grand_total' => $quote->grand_total,
            'created_by' => User::query()->value('id'),
        ];

        DB::transaction(function () use (&$books, $attributes, $quote) {
            $books ? $books->update($attributes) : $books = BooksQuotation::query()->create($attributes);
            $books->lines()->delete();
            $n = 1;
            foreach ($quote->items as $item) {
                $productId = null;
                if ($item->product) {
                    $this->products->push($item->product);
                    $productId = SyncLinks::acctId(ProductSync::KEY, $item->product);
                }
                QuotationLine::query()->create([
                    'quotation_id' => $books->id,
                    'line_number' => $n++,
                    'product_id' => $productId,
                    'item_name' => $item->product?->name ?? 'Item',
                    'quantity' => $item->quantity,
                    'rate' => $item->unit_price ?? $item->rate ?? 0,
                    'discount' => $item->discount_amount ?? 0,
                    'tax_amount' => $item->tax_amount ?? 0,
                    'line_total' => $item->line_total ?? 0,
                ]);
            }
            SyncLinks::store(self::KEY, $quote, $books);
        });
    }

    public function remove(Model $source): void
    {
        if (! $source instanceof Quotation) {
            return;
        }
        $books = BooksQuotation::query()->find(SyncLinks::acctId(self::KEY, $source));
        if ($books && in_array($books->status, ['draft', 'open', 'sent'], true)) {
            $books->lines()->delete();
            $books->delete();
        }
        SyncLinks::forget(self::KEY, $source);
    }
}
