<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Inventory\Models\StockMovement;
use App\Domains\Payment\Models\CreditNote;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sync\Support\BooksCompany;
use App\Domains\Sync\Support\SyncFailureLogger;
use App\Domains\Sync\Support\SyncLinks;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Tally\Inventory\StockTransactionService;
use Tally\Inventory\StockTransactionType;
use Tally\Models\Branch;
use Tally\Models\StockTransaction;

/**
 * Auto-pushes DMS stock movements into Books as posted In/Out (skips invoice/CN
 * movements — those are handled when Books invoices post).
 */
class StockMovementSync
{
    public const KEY = 'stock_movement';

    public function __construct(
        private readonly ProductSync $products,
        private readonly WarehouseSync $warehouses,
        private readonly StockTransactionService $stock,
    ) {}

    public function sync(Model $model): void
    {
        if ($model instanceof StockMovement) {
            $this->push($model);
        }
    }

    public function push(StockMovement $movement): void
    {
        if (SyncLinks::acctId(self::KEY, $movement)) {
            return;
        }

        $refType = (string) $movement->reference_type;
        if (in_array($refType, [Invoice::class, CreditNote::class], true)) {
            return;
        }

        $movement->loadMissing(['warehouse', 'product']);
        $warehouse = $movement->warehouse;
        $product = $movement->product;
        if (! $warehouse?->company_id || ! $product) {
            return;
        }

        $qty = (float) $movement->quantity;
        if ($qty == 0.0) {
            return;
        }

        $company = BooksCompany::forOrganization((int) $warehouse->company_id);
        $date = ($movement->created_at ?? now())->toDateString();
        $year = $company ? BooksCompany::yearFor($company, $date) : null;
        $branchId = $company ? BooksCompany::branchId((int) $warehouse->company_id, $warehouse->branch_id ? (int) $warehouse->branch_id : null) : null;
        if (! $company || ! $year || ! $branchId) {
            return;
        }

        $this->warehouses->push($warehouse);
        $this->products->push($product);
        $godownId = SyncLinks::acctId(WarehouseSync::KEY, $warehouse) ?: BooksCompany::defaultGodown($company, (int) $warehouse->company_id);
        $booksProductId = SyncLinks::acctId(ProductSync::KEY, $product);
        if (! $booksProductId) {
            return;
        }

        $type = $qty > 0 ? StockTransactionType::In : StockTransactionType::Out;
        $branch = Branch::query()->find($branchId) ?: $company->branches()->first();
        $user = User::query()->find($movement->created_by) ?: User::query()->orderBy('id')->first();
        if (! $branch || ! $user) {
            return;
        }

        try {
            $txn = $this->stock->save(
                $company,
                $branch,
                $year,
                $user,
                $type,
                [
                    'transaction_date' => $date,
                    'narration' => trim('DMS '.$movement->type.' #'.$movement->id.' '.($movement->notes ?? '')),
                    'lines' => [[
                        'product_id' => $booksProductId,
                        'godown_id' => $godownId,
                        'quantity' => number_format(abs($qty), 4, '.', ''),
                        'rate' => number_format((float) ($product->purchase_price ?? 0), 2, '.', ''),
                    ]],
                ],
            );
            SyncLinks::store(self::KEY, $movement, $txn);
        } catch (\Throwable $e) {
            SyncFailureLogger::write(self::KEY, 'dms_to_books', $movement, $e);
        }
    }
}
