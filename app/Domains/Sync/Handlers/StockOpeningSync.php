<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Inventory\Models\StockLevel;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Sync\Support\BooksCompany;
use App\Domains\Sync\Support\SyncFailureLogger;
use App\Domains\Sync\Support\SyncLinks;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Tally\Inventory\StockTransactionService;
use Tally\Inventory\StockTransactionType;
use Tally\Models\StockTransaction;

/**
 * Pushes current DMS stock levels into Books as a posted Stock Receipt (In)
 * per warehouse. Idempotent via sync_entity_links on the warehouse.
 */
class StockOpeningSync
{
    public const KEY = 'stock_opening';

    public function __construct(
        private readonly ProductSync $products,
        private readonly WarehouseSync $warehouses,
        private readonly StockTransactionService $stock,
    ) {}

    public function sync(Model $model): void
    {
        if ($model instanceof Warehouse) {
            $this->pushWarehouse($model);
        }
    }

    public function pushWarehouse(Warehouse $warehouse): void
    {
        if (! $warehouse->company_id) {
            return;
        }

        $existingId = SyncLinks::acctId(self::KEY, $warehouse);
        if ($existingId && StockTransaction::query()->whereKey($existingId)->exists()) {
            return;
        }

        $levels = StockLevel::query()
            ->with('product')
            ->where('warehouse_id', $warehouse->id)
            ->where('quantity', '>', 0)
            ->get();

        if ($levels->isEmpty()) {
            return;
        }

        $company = BooksCompany::forOrganization((int) $warehouse->company_id);
        $date = now()->toDateString();
        $year = $company ? BooksCompany::yearFor($company, $date) : null;
        $branchId = $company ? BooksCompany::branchId((int) $warehouse->company_id, $warehouse->branch_id ? (int) $warehouse->branch_id : null) : null;
        if (! $company || ! $year || ! $branchId) {
            throw new \RuntimeException('Warehouse '.$warehouse->code.' has no books company/branch/year.');
        }

        $this->warehouses->push($warehouse);
        $godownId = SyncLinks::acctId(WarehouseSync::KEY, $warehouse) ?: BooksCompany::defaultGodown($company, (int) $warehouse->company_id);

        $lines = [];
        foreach ($levels as $level) {
            if (! $level->product) {
                continue;
            }
            $this->products->push($level->product);
            $booksProductId = SyncLinks::acctId(ProductSync::KEY, $level->product);
            if (! $booksProductId) {
                continue;
            }
            $rate = number_format((float) ($level->product->purchase_price ?? 0), 2, '.', '');
            $lines[] = [
                'product_id' => $booksProductId,
                'godown_id' => $godownId,
                'quantity' => number_format((float) $level->quantity, 4, '.', ''),
                'rate' => $rate,
            ];
        }

        if ($lines === []) {
            return;
        }

        $branch = $company->branches()->whereKey($branchId)->first() ?: $company->branches()->first();
        $user = User::query()->orderBy('id')->first();
        if (! $branch || ! $user) {
            throw new \RuntimeException('Cannot post stock opening without branch/user.');
        }

        try {
            $txn = $this->stock->save(
                $company,
                $branch,
                $year,
                $user,
                StockTransactionType::In,
                [
                    'transaction_date' => $date,
                    'narration' => 'DMS stock opening — '.$warehouse->name.' ('.$warehouse->code.')',
                    'lines' => $lines,
                ],
            );
            SyncLinks::store(self::KEY, $warehouse, $txn);
        } catch (\Throwable $e) {
            SyncFailureLogger::write(self::KEY, 'dms_to_books', $warehouse, $e);
            throw $e;
        }
    }
}
