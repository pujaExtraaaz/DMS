<?php

namespace App\Domains\Tally\Observers;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\SubCategory;
use App\Domains\Inventory\Models\StockAdjustment;
use App\Domains\Inventory\Models\StockTransfer;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\PartyAddress;
use App\Domains\Master\Models\PartyBankAccount;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\ProductType;
use App\Domains\Master\Models\TaxRate;
use App\Domains\Master\Models\Uom;
use App\Domains\Order\Models\Order;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Payment\Models\CreditNote;
use App\Domains\Payment\Models\Payment;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\PurchaseInward;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\Quotation;
use App\Domains\Tally\Services\TallyExportService;
use App\Domains\TallySync\Support\SyncGate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Queues DMS masters and posted documents for TallyPrime while the connector is on.
 */
class TallyAutoEnqueueObserver
{
    protected static bool $muted = false;

    public function __construct(protected TallyExportService $tallyService) {}

    public static function muted(): bool
    {
        return static::$muted;
    }

    public static function withoutSync(callable $callback): mixed
    {
        $previous = static::$muted;
        static::$muted = true;

        try {
            return $callback();
        } finally {
            static::$muted = $previous;
        }
    }

    public function created(Model $model): void
    {
        $this->schedule($model, true);
    }

    public function updated(Model $model): void
    {
        $this->schedule($model, false);
    }

    protected function schedule(Model $model, bool $created): void
    {
        if (static::$muted || ! SyncGate::enabled()) {
            return;
        }

        $class = $model::class;
        $id = $model->getKey();
        $statusChanged = $model->wasChanged('status');
        $isMaster = $this->isMaster($model);
        $isPartySatellite = $model instanceof PartyAddress || $model instanceof PartyBankAccount;
        // Ignore timestamp-only touches so inbound/import saves do not re-queue forever.
        $masterDirty = $created || $model->wasChanged();

        DB::afterCommit(function () use ($class, $id, $created, $statusChanged, $isMaster, $isPartySatellite, $masterDirty) {
            if (static::$muted || ! SyncGate::enabled()) {
                return;
            }

            $fresh = $class::query()->find($id);
            if (! $fresh) {
                return;
            }

            if ($isPartySatellite) {
                if (! $masterDirty) {
                    return;
                }
                $customer = $fresh->customer;
                if ($customer) {
                    $this->maybeEnqueueMaster($customer);
                }

                return;
            }

            if ($isMaster) {
                if (! $masterDirty) {
                    return;
                }
                $this->maybeEnqueueMaster($fresh);

                return;
            }

            if ($fresh instanceof PurchaseOrder && $fresh->status !== 'approved') {
                return;
            }

            if ($fresh instanceof Order && $fresh->status !== 'approved') {
                return;
            }

            if ($fresh instanceof Quotation && ! in_array($fresh->status, ['sent', 'accepted'], true)) {
                return;
            }

            if ($fresh instanceof PurchaseInward && ! in_array($fresh->status, ['posted', 'completed', 'received'], true)) {
                return;
            }

            if ($fresh instanceof StockTransfer && ! in_array($fresh->status, ['completed', 'received', 'posted', 'approved'], true)) {
                return;
            }

            if ($fresh instanceof StockAdjustment && ! in_array($fresh->status, ['posted', 'approved', 'completed'], true)) {
                return;
            }

            if (! $created && ! $statusChanged) {
                return;
            }

            $this->maybeEnqueue($fresh);
        });
    }

    protected function isMaster(Model $model): bool
    {
        return $model instanceof Product
            || $model instanceof Customer
            || $model instanceof Uom
            || $model instanceof Warehouse
            || $model instanceof Brand
            || $model instanceof Category
            || $model instanceof SubCategory
            || $model instanceof ProductType
            || $model instanceof Branch
            || $model instanceof TaxRate;
    }

    protected function maybeEnqueueMaster(Model $model): void
    {
        try {
            $this->tallyService->enqueueMaster($model);
        } catch (\Throwable $exception) {
            Log::warning('Tally master enqueue failed', [
                'model' => $model::class,
                'id' => $model->getKey(),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    protected function maybeEnqueue(Model $model): void
    {
        if (! (
            $model instanceof Invoice ||
            $model instanceof Payment ||
            $model instanceof CreditNote ||
            $model instanceof PurchaseInvoice ||
            $model instanceof PurchaseOrder ||
            $model instanceof Order ||
            $model instanceof Quotation ||
            $model instanceof PurchaseInward ||
            $model instanceof StockTransfer ||
            $model instanceof StockAdjustment
        )) {
            return;
        }

        try {
            $this->tallyService->enqueuePostedDocument($model);
        } catch (\Throwable $exception) {
            Log::warning('Tally auto-enqueue failed', [
                'model' => $model::class,
                'id' => $model->getKey(),
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
