<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Inventory\Models\StockAdjustment;
use App\Domains\Inventory\Models\StockAdjustmentItem;
use App\Domains\Master\Models\Product;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Sync\Support\BooksCompany;
use App\Domains\Sync\Support\SyncFailureLogger;
use App\Domains\Sync\Support\SyncLinks;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Tally\Accounting\VoucherStatus;
use Tally\Inventory\StockTransactionType;
use Tally\Models\Godown;
use Tally\Models\Product as BooksProduct;
use Tally\Models\StockTransaction;
use Tally\Models\StockTransactionLine;

class StockJournalSync
{
    public const KEY = 'stock_journal';

    public function __construct(
        private readonly ProductSync $products,
        private readonly WarehouseSync $warehouses,
    ) {}

    public function sync(Model $model): void
    {
        if ($model instanceof StockAdjustment) {
            $this->push($model);

            return;
        }

        if ($model instanceof StockTransaction && $model->type === StockTransactionType::Adjustment) {
            $this->pull($model);
        }
    }

    public function push(StockAdjustment $adjustment): void
    {
        $adjustment->loadMissing(['warehouse', 'items.product']);
        $organizationId = (int) $adjustment->warehouse?->company_id;
        if (! $organizationId) {
            throw new \RuntimeException('Stock journal '.$adjustment->adjustment_no.' has no warehouse.');
        }

        $company = BooksCompany::forOrganization($organizationId);
        $date = $adjustment->adjustment_date?->toDateString() ?: now()->toDateString();
        $year = $company ? BooksCompany::yearFor($company, $date) : null;
        $branchId = $company ? BooksCompany::branchId($organizationId, $adjustment->warehouse->branch_id ? (int) $adjustment->warehouse->branch_id : null) : null;
        if (! $company || ! $year || ! $branchId) {
            throw new \RuntimeException('Stock journal '.$adjustment->adjustment_no.' has no books company, branch, or financial year.');
        }

        $books = StockTransaction::query()->find(SyncLinks::acctId(self::KEY, $adjustment));
        if ($books && $books->status !== VoucherStatus::Draft) {
            $this->conflict($adjustment, $books, 'dms_to_books', 'Books stock journal is '.$books->status->value.' and was left unchanged.');

            return;
        }

        $this->warehouses->push($adjustment->warehouse);
        $godownId = SyncLinks::acctId(WarehouseSync::KEY, $adjustment->warehouse) ?: BooksCompany::defaultGodown($company, $organizationId);

        $attributes = [
            'company_id' => $company->id,
            'branch_id' => $branchId,
            'financial_year_id' => $year->id,
            'type' => StockTransactionType::Adjustment,
            'number' => $this->booksNumber((string) $adjustment->adjustment_no, $company->id, $books?->id),
            'transaction_date' => $date,
            'source_godown_id' => $godownId,
            'narration' => trim(($adjustment->reason ? $adjustment->reason.'. ' : '').(string) $adjustment->notes),
            'status' => VoucherStatus::Draft,
            'created_by' => $adjustment->created_by ?: User::query()->value('id'),
        ];

        DB::transaction(function () use (&$books, $attributes, $adjustment, $godownId) {
            $books ? $books->update($attributes) : $books = StockTransaction::query()->create($attributes);
            $books->lines()->delete();
            $number = 1;
            foreach ($adjustment->items as $item) {
                if (! $item->product) {
                    continue;
                }
                $this->products->push($item->product);
                $quantity = (float) $item->quantity;
                StockTransactionLine::query()->create([
                    'stock_transaction_id' => $books->id,
                    'line_number' => $number++,
                    'product_id' => SyncLinks::acctId(ProductSync::KEY, $item->product),
                    'godown_id' => $godownId,
                    'quantity' => $quantity,
                    'rate' => 0,
                    'value' => 0,
                ]);
            }
            SyncLinks::store(self::KEY, $adjustment, $books);
        });
    }

    public function pull(StockTransaction $books): void
    {
        if ($books->type !== StockTransactionType::Adjustment) {
            return;
        }

        $organizationId = BooksCompany::organizationId($books->company_id);
        if (! $organizationId) {
            return;
        }

        $adjustment = StockAdjustment::query()->find(SyncLinks::dmsId(self::KEY, $books));
        if ($adjustment && ($books->status !== VoucherStatus::Draft || $adjustment->status !== 'draft')) {
            $this->conflict($adjustment, $books, 'books_to_dms', 'Stock journal is '.$adjustment->status.' in DMS and '.$books->status->value.' in Books, so it was left unchanged.');

            return;
        }

        $dmsStatus = $books->status === VoucherStatus::Draft ? 'draft' : 'posted';

        $books->loadMissing('lines');
        $godown = Godown::query()->find($books->source_godown_id ?: $books->lines->first()?->godown_id);
        $warehouseId = null;
        if ($godown) {
            $this->warehouses->pull($godown);
            $warehouseId = SyncLinks::dmsId(WarehouseSync::KEY, $godown);
        }
        if (! $warehouseId) {
            $warehouseId = Warehouse::query()->where('company_id', $organizationId)->orderBy('id')->value('id');
        }
        if (! $warehouseId) {
            throw new \RuntimeException('Books stock journal '.$books->number.' has no warehouse.');
        }

        $attributes = [
            'adjustment_no' => $this->dmsNumber($books, $adjustment),
            'adjustment_date' => $books->transaction_date->toDateString(),
            'warehouse_id' => $warehouseId,
            'reason' => 'other',
            'status' => $dmsStatus,
            'notes' => $books->narration,
            'created_by' => $adjustment?->created_by ?: User::query()->value('id'),
        ];

        DB::transaction(function () use (&$adjustment, $attributes, $books) {
            $adjustment ? $adjustment->update($attributes) : $adjustment = StockAdjustment::query()->create($attributes);
            $adjustment->items()->delete();
            foreach ($books->lines as $line) {
                if (! $line->product_id) {
                    throw new \RuntimeException('Books stock journal '.$books->number.' has a line without a product.');
                }
                $booksProduct = BooksProduct::query()->find($line->product_id);
                $this->products->pull($booksProduct);
                $productId = SyncLinks::dmsId(ProductSync::KEY, $booksProduct);
                $product = Product::query()->find($productId);
                if (! $product?->base_uom_id) {
                    throw new \RuntimeException('Product '.$productId.' has no unit, so the stock journal was not copied.');
                }
                StockAdjustmentItem::query()->create([
                    'stock_adjustment_id' => $adjustment->id,
                    'product_id' => $productId,
                    'uom_id' => $product->base_uom_id,
                    'quantity' => $line->quantity,
                ]);
            }
            SyncLinks::store(self::KEY, $adjustment, $books);
        });
    }

    public function remove(Model $source): void
    {
        if ($source instanceof StockAdjustment) {
            $books = StockTransaction::query()->find(SyncLinks::acctId(self::KEY, $source));
            if (! $books) {
                SyncLinks::forget(self::KEY, $source);

                return;
            }
            if ($books->status !== VoucherStatus::Draft) {
                $this->conflict($source, $books, 'dms_to_books', 'Books stock journal is '.$books->status->value.', so the delete was not copied.');

                return;
            }
            $books->lines()->delete();
            $books->delete();
            SyncLinks::forget(self::KEY, $source);

            return;
        }

        if (! $source instanceof StockTransaction || $source->type !== StockTransactionType::Adjustment) {
            return;
        }

        $adjustment = StockAdjustment::query()->find(SyncLinks::dmsId(self::KEY, $source));
        if (! $adjustment) {
            SyncLinks::forget(self::KEY, $source);

            return;
        }
        if ($source->status !== VoucherStatus::Draft || $adjustment->status !== 'draft') {
            $this->conflict($adjustment, $source, 'books_to_dms', 'Stock journal is '.$adjustment->status.' in DMS and '.$source->status->value.' in Books, so the delete was not copied.');

            return;
        }
        $adjustment->items()->delete();
        $adjustment->delete();
        SyncLinks::forget(self::KEY, $source);
    }

    private function booksNumber(string $preferred, int $companyId, ?int $ignoreId): string
    {
        $preferred = mb_substr($preferred, 0, 30);
        $taken = StockTransaction::query()
            ->where('company_id', $companyId)
            ->where('number', $preferred)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();

        return $taken ? mb_substr($preferred, 0, 24).'-J'.($ignoreId ?: 'N') : $preferred;
    }

    private function dmsNumber(StockTransaction $books, ?StockAdjustment $adjustment): string
    {
        $preferred = mb_substr((string) $books->number, 0, 40);
        $taken = StockAdjustment::query()
            ->where('adjustment_no', $preferred)
            ->when($adjustment, fn ($query) => $query->whereKeyNot($adjustment->id))
            ->exists();

        return $taken ? mb_substr($preferred, 0, 32).'-B'.$books->id : $preferred;
    }

    private function conflict(StockAdjustment $adjustment, StockTransaction $books, string $direction, string $message): void
    {
        SyncLinks::store(self::KEY, $adjustment, $books, 'conflict', $message);
        SyncFailureLogger::write(self::KEY, $direction, $direction === 'dms_to_books' ? $adjustment : $books, $message);
    }
}
