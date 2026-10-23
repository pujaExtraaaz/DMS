<?php

namespace App\Domains\Tally\Observers;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Payment\Models\CreditNote;
use App\Domains\Payment\Models\Payment;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Tally\Services\TallyExportService;
use App\Domains\TallySync\Support\SyncGate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Queues DMS rows after the Books copy has been written, so a posted sale
 * is sent once as the Books voucher.
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
        $isMaster = $model instanceof Product || $model instanceof Customer || $model instanceof Uom || $model instanceof Warehouse;

        DB::afterCommit(function () use ($class, $id, $created, $statusChanged, $isMaster) {
            if (static::$muted || ! SyncGate::enabled()) {
                return;
            }

            $fresh = $class::query()->find($id);
            if (! $fresh) {
                return;
            }

            if ($isMaster) {
                $this->maybeEnqueueMaster($fresh);

                return;
            }

            if ($fresh instanceof PurchaseOrder && $fresh->status !== 'approved') {
                return;
            }

            if (! $created && ! $statusChanged) {
                return;
            }

            $this->maybeEnqueue($fresh);
        });
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
            $model instanceof PurchaseOrder
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