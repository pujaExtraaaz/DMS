<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Master\Models\Product;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderItem;
use App\Domains\Sync\Support\BooksCompany;
use App\Domains\Sync\Support\SyncFailureLogger;
use App\Domains\Sync\Support\SyncLinks;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Tally\Models\Party;
use Tally\Models\Product as BooksProduct;
use Tally\Models\SalesOrder as BooksSalesOrder;
use Tally\Models\SalesOrderLine;

class SalesOrderSync
{
    public const KEY = 'sales_order';

    public function __construct(
        private readonly CustomerSync $customers,
        private readonly ProductSync $products,
    ) {}

    public function sync(Model $model): void
    {
        if ($model instanceof Order) {
            $this->push($model);

            return;
        }

        if ($model instanceof BooksSalesOrder) {
            $this->pull($model);
        }
    }

    public function push(Order $order): void
    {
        $order->loadMissing(['customer', 'items.product']);
        $organizationId = (int) $order->customer?->company_id;
        if (! $organizationId || ! $order->customer) {
            return;
        }

        $company = BooksCompany::forOrganization($organizationId);
        $date = $order->order_date?->toDateString() ?: now()->toDateString();
        $year = $company ? BooksCompany::yearFor($company, $date) : null;
        $branchId = $company ? BooksCompany::branchId($organizationId, $order->customer->branch_id ? (int) $order->customer->branch_id : null) : null;
        if (! $company || ! $year || ! $branchId) {
            throw new \RuntimeException('Sales order '.$order->order_no.' has no books company, branch, or financial year.');
        }

        $books = BooksSalesOrder::query()->find(SyncLinks::acctId(self::KEY, $order));
        if ($order->status === 'converted') {
            if (! $books) {
                $this->write($order, $company->id, $branchId, $year->id, $date, 'closed');
            }

            return;
        }
        if ($books && ! in_array($books->status, ['open', 'draft'], true)) {
            $this->conflict($order, $books, 'dms_to_books', 'Books sales order is '.$books->status.' and was left unchanged.');

            return;
        }

        $this->write($order, $company->id, $branchId, $year->id, $date, 'open');
    }

    public function pull(BooksSalesOrder $books): void
    {
        $organizationId = BooksCompany::organizationId($books->company_id);
        if (! $organizationId) {
            return;
        }

        $order = Order::query()->find(SyncLinks::dmsId(self::KEY, $books));
        if ($order && $order->status === 'converted') {
            $this->conflict($order, $books, 'books_to_dms', 'DMS sales order '.$order->order_no.' is converted and was left unchanged.');

            return;
        }
        if (! in_array($books->status, ['open', 'draft'], true)) {
            if ($order) {
                $this->conflict($order, $books, 'books_to_dms', 'Books sales order is '.$books->status.' and was left unchanged.');
            }

            return;
        }

        $party = Party::query()->where('ledger_id', $books->customer_ledger_id)->first();
        if (! $party) {
            throw new \RuntimeException('Books sales order '.$books->number.' has no customer.');
        }
        $this->customers->pull($party);
        $customerId = SyncLinks::dmsId(CustomerSync::KEY, $party);
        if (! $customerId) {
            throw new \RuntimeException('Party '.$party->id.' did not sync to a DMS customer.');
        }

        $books->loadMissing('lines');
        $attributes = [
            'customer_id' => $customerId,
            'order_no' => $this->dmsNumber($books, $order),
            'order_date' => $books->order_date->toDateString(),
            'status' => $order?->status ?? 'pending',
            'fulfilment_mode' => $order?->fulfilment_mode ?? 'warehouse',
            'subtotal' => $books->subtotal,
            'discount_amount' => $books->discount_total,
            'tax_amount' => $books->tax_total,
            'grand_total' => $books->grand_total,
            'notes' => $books->narration,
        ];
        if (! $order) {
            $attributes['salesperson_id'] = User::query()->value('id');
        }

        DB::transaction(function () use (&$order, $attributes, $books) {
            $order ? $order->update($attributes) : $order = Order::query()->create($attributes);
            $order->items()->delete();
            foreach ($books->lines as $line) {
                if (! $line->product_id) {
                    throw new \RuntimeException('Books sales order '.$books->number.' has a line without a product.');
                }
                $booksProduct = BooksProduct::query()->find($line->product_id);
                $this->products->pull($booksProduct);
                $productId = SyncLinks::dmsId(ProductSync::KEY, $booksProduct);
                $product = Product::query()->find($productId);
                if (! $product?->base_uom_id) {
                    throw new \RuntimeException('Product '.$productId.' has no unit, so the sales order was not copied.');
                }
                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $productId,
                    'uom_id' => $product->base_uom_id,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->rate,
                    'line_total' => $line->line_total,
                ]);
            }
            SyncLinks::store(self::KEY, $order, $books);
        });
    }

    public function remove(Model $source): void
    {
        if ($source instanceof Order) {
            $books = BooksSalesOrder::query()->find(SyncLinks::acctId(self::KEY, $source));
            if (! $books) {
                SyncLinks::forget(self::KEY, $source);

                return;
            }
            if ($source->status === 'converted' || ! in_array($books->status, ['open', 'draft'], true)) {
                $this->conflict($source, $books, 'dms_to_books', 'Sales order is '.$source->status.' in DMS and '.$books->status.' in Books, so the delete was not copied.');

                return;
            }
            $books->lines()->delete();
            $books->delete();
            SyncLinks::forget(self::KEY, $source);

            return;
        }

        if (! $source instanceof BooksSalesOrder) {
            return;
        }

        $order = Order::query()->find(SyncLinks::dmsId(self::KEY, $source));
        if (! $order) {
            SyncLinks::forget(self::KEY, $source);

            return;
        }
        if ($order->status === 'converted' || ! in_array($source->status, ['open', 'draft'], true)) {
            $this->conflict($order, $source, 'books_to_dms', 'Sales order is '.$order->status.' in DMS and '.$source->status.' in Books, so the delete was not copied.');

            return;
        }
        $order->items()->delete();
        $order->delete();
        SyncLinks::forget(self::KEY, $source);
    }

    private function write(Order $order, int $companyId, int $branchId, int $yearId, string $date, string $status): void
    {
        $this->customers->push($order->customer);
        $party = Party::query()->find(SyncLinks::acctId(CustomerSync::KEY, $order->customer));
        if (! $party) {
            throw new \RuntimeException('Customer '.$order->customer_id.' did not sync before the sales order.');
        }

        $books = BooksSalesOrder::query()->find(SyncLinks::acctId(self::KEY, $order));
        $attributes = [
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'financial_year_id' => $yearId,
            'number' => $this->booksNumber((string) $order->order_no, $companyId, $books?->id),
            'order_date' => $date,
            'customer_ledger_id' => $party->ledger_id,
            'narration' => $order->notes,
            'subtotal' => $order->subtotal ?? 0,
            'discount_total' => $order->discount_amount ?? 0,
            'tax_total' => $order->tax_amount ?? 0,
            'grand_total' => $order->grand_total ?? 0,
            'status' => $status,
        ];

        DB::transaction(function () use (&$books, $attributes, $order) {
            $books ? $books->update($attributes) : $books = BooksSalesOrder::query()->create($attributes);
            $books->lines()->delete();
            $number = 1;
            foreach ($order->items as $item) {
                if (! $item->product) {
                    continue;
                }
                $this->products->push($item->product);
                SalesOrderLine::query()->create([
                    'sales_order_id' => $books->id,
                    'line_number' => $number++,
                    'item_name' => $item->product->name ?: 'Item',
                    'product_id' => SyncLinks::acctId(ProductSync::KEY, $item->product),
                    'quantity' => $item->quantity,
                    'rate' => $item->unit_price,
                    'discount' => 0,
                    'tax_amount' => 0,
                    'line_total' => $item->line_total ?? 0,
                ]);
            }
            SyncLinks::store(self::KEY, $order, $books);
        });
    }

    private function booksNumber(string $preferred, int $companyId, ?int $ignoreId): string
    {
        $preferred = mb_substr($preferred, 0, 30);
        $taken = BooksSalesOrder::query()
            ->where('company_id', $companyId)
            ->where('number', $preferred)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();

        return $taken ? mb_substr($preferred, 0, 24).'-S'.($ignoreId ?: 'N') : $preferred;
    }

    private function dmsNumber(BooksSalesOrder $books, ?Order $order): string
    {
        $preferred = mb_substr((string) $books->number, 0, 40);
        $taken = Order::query()
            ->where('order_no', $preferred)
            ->when($order, fn ($query) => $query->whereKeyNot($order->id))
            ->exists();

        return $taken ? mb_substr($preferred, 0, 32).'-B'.$books->id : $preferred;
    }

    private function conflict(Order $order, BooksSalesOrder $books, string $direction, string $message): void
    {
        SyncLinks::store(self::KEY, $order, $books, 'conflict', $message);
        SyncFailureLogger::write(self::KEY, $direction, $direction === 'dms_to_books' ? $order : $books, $message);
    }
}
