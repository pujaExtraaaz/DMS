<?php

namespace App\Console\Commands;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Payment\Models\CreditNote;
use App\Domains\Payment\Models\Payment;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Banking\Models\BankAccountTransaction;
use App\Domains\Banking\Models\OdAccount;
use App\Domains\Inventory\Models\StockAdjustment;
use App\Domains\Order\Models\Order;
use App\Domains\Sync\Support\SyncGuard;
use App\Domains\Sync\Support\SyncRegistry;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Throwable;
use Tally\Inventory\StockTransactionType;
use Tally\Models\BankAccount;
use Tally\Models\SalesOrder as BooksSalesOrder;
use Tally\Models\StockTransaction;
use Tally\Models\Voucher;

class BackfillAccounting extends Command
{
    protected $signature = 'accounting:backfill';

    protected $description = 'Copy existing DMS masters and documents into Books once, without pulling Books rows back';

    public function handle(): int
    {
        $steps = [
            Uom::class,
            Warehouse::class,
            Customer::class,
            Product::class,
            Invoice::class,
            Payment::class,
            PurchaseOrder::class,
            PurchaseInvoice::class,
            CreditNote::class,
            Order::class,
            StockAdjustment::class,
            OdAccount::class,
            BankAccountTransaction::class,
            BooksSalesOrder::class,
            BankAccount::class,
        ];

        $copied = 0;
        $failed = 0;

        SyncGuard::run(function () use ($steps, &$copied, &$failed) {
            foreach ($steps as $class) {
                $class::query()->orderBy('id')->each(function (Model $row) use (&$copied, &$failed) {
                    try {
                        SyncRegistry::handler($row)?->sync($row);
                        $copied++;
                    } catch (Throwable $exception) {
                        $failed++;
                        $this->error($row::class.' #'.$row->getKey().': '.$exception->getMessage());
                    }
                });
            }

            StockTransaction::query()
                ->where('type', StockTransactionType::Adjustment)
                ->orderBy('id')
                ->each(function (StockTransaction $row) use (&$copied, &$failed) {
                    try {
                        SyncRegistry::handler($row)?->sync($row);
                        $copied++;
                    } catch (Throwable $exception) {
                        $failed++;
                        $this->error($row::class.' #'.$row->getKey().': '.$exception->getMessage());
                    }
                });

            Voucher::query()->orderBy('id')->each(function (Voucher $row) use (&$copied, &$failed) {
                try {
                    app(\App\Domains\Sync\Handlers\BankTransactionSync::class)->sync($row);
                    $copied++;
                } catch (Throwable $exception) {
                    $failed++;
                    $this->error($row::class.' #'.$row->getKey().': '.$exception->getMessage());
                }
            });
        });

        $this->info("Backfill finished. {$copied} records copied, {$failed} failed.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
