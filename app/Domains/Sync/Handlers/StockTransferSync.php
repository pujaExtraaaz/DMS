<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Inventory\Models\StockTransfer;
use App\Domains\Sync\Support\BooksCompany;
use App\Domains\Sync\Support\SyncFailureLogger;
use App\Domains\Sync\Support\SyncLinks;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Tally\Inventory\StockTransactionService;
use Tally\Inventory\StockTransactionType;
use Tally\Models\Branch;

class StockTransferSync
{
    public const KEY = 'stock_transfer';

    public function __construct(
        private readonly ProductSync $products,
        private readonly WarehouseSync $warehouses,
        private readonly StockTransactionService $stock,
    ) {}

    public function sync(Model $model): void
    {
        if ($model instanceof StockTransfer) {
            $this->push($model);
        }
    }

    public function push(StockTransfer $transfer): void
    {
        if (! in_array($transfer->status, ['posted', 'received', 'completed', 'approved'], true)) {
            return;
        }
        if (SyncLinks::acctId(self::KEY, $transfer)) {
            return;
        }

        $transfer->loadMissing(['fromWarehouse', 'toWarehouse', 'items.product']);
        $from = $transfer->fromWarehouse;
        $to = $transfer->toWarehouse;
        if (! $from?->company_id || ! $to) {
            return;
        }

        $company = BooksCompany::forOrganization((int) $from->company_id);
        $date = $transfer->transfer_date?->toDateString() ?: now()->toDateString();
        $year = $company ? BooksCompany::yearFor($company, $date) : null;
        $branchId = $company ? BooksCompany::branchId((int) $from->company_id, $from->branch_id ? (int) $from->branch_id : null) : null;
        if (! $company || ! $year || ! $branchId) {
            return;
        }

        $this->warehouses->push($from);
        $this->warehouses->push($to);
        $sourceId = SyncLinks::acctId(WarehouseSync::KEY, $from);
        $destId = SyncLinks::acctId(WarehouseSync::KEY, $to);
        if (! $sourceId || ! $destId) {
            return;
        }

        $lines = [];
        foreach ($transfer->items as $item) {
            if (! $item->product) {
                continue;
            }
            $this->products->push($item->product);
            $pid = SyncLinks::acctId(ProductSync::KEY, $item->product);
            if (! $pid) {
                continue;
            }
            $lines[] = [
                'product_id' => $pid,
                'quantity' => number_format((float) $item->quantity, 4, '.', ''),
                'rate' => number_format((float) ($item->product->purchase_price ?? 0), 2, '.', ''),
            ];
        }
        if ($lines === []) {
            return;
        }

        $branch = Branch::query()->find($branchId) ?: $company->branches()->first();
        $user = User::query()->find($transfer->created_by) ?: User::query()->orderBy('id')->first();
        if (! $branch || ! $user) {
            return;
        }

        try {
            $txn = $this->stock->save(
                $company,
                $branch,
                $year,
                $user,
                StockTransactionType::Transfer,
                [
                    'transaction_date' => $date,
                    'source_godown_id' => $sourceId,
                    'destination_godown_id' => $destId,
                    'narration' => 'DMS transfer '.$transfer->transfer_no,
                    'lines' => $lines,
                ],
            );
            SyncLinks::store(self::KEY, $transfer, $txn);
        } catch (\Throwable $e) {
            SyncFailureLogger::write(self::KEY, 'dms_to_books', $transfer, $e);
        }
    }
}
