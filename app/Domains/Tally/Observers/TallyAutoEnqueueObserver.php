<?php

namespace App\Domains\Tally\Observers;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Payment\Models\CreditNote;
use App\Domains\Payment\Models\Payment;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Tally\Services\TallyExportService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Auto-enqueue supported documents to the Tally queue on create AND on transition
 * to a posted status. Silent on failure — Tally sync must NEVER break the main flow.
 *
 * Masters (Product, Customer, Uom) sync immediately on save/update.
 * Vouchers (Invoice, Payment, etc.) sync only when they reach a posted status.
 */
class TallyAutoEnqueueObserver
{
    protected static bool $muted = false;

    public function __construct(
        protected TallyExportService $tallyService
    ) {
    }

    /**
     * Run a callback without queueing anything back to Tally
     * (used when the data being saved came from Tally in the first place).
     */
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
        if (static::$muted) {
            return;
        }

        // Master records sync immediately on creation.
        if ($model instanceof Product || $model instanceof Customer || $model instanceof Uom) {
            $this->maybeEnqueueMaster($model);
            return;
        }

        // Purchase Orders should sync to Tally only after approval.
        if ($model instanceof PurchaseOrder) {
            return;
        }

        $this->maybeEnqueue($model);
    }

    public function updated(Model $model): void
    {
        if (static::$muted) {
            return;
        }

        // Masters sync on any field change (name, HSN, GST rate, address etc.)
        if ($model instanceof Product || $model instanceof Customer || $model instanceof Uom) {
            $this->maybeEnqueueMaster($model);
            return;
        }

        if (! $model->wasChanged('status')) {
            return;
        }

        // Purchase Orders sync only when they become approved.
        if ($model instanceof PurchaseOrder && $model->status !== 'approved') {
            return;
        }

        $this->maybeEnqueue($model);
    }

    protected function maybeEnqueueMaster(Model $model): void
    {
        try {
            $this->tallyService->enqueueMaster($model);
        } catch (\Throwable $e) {
            Log::warning('Tally master enqueue failed', [
                'model' => $model::class,
                'id'    => $model->getKey(),
                'error' => $e->getMessage(),
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
        } catch (\Throwable $e) {
            // Never bubble Tally issues into the primary transaction.
            Log::warning('Tally auto-enqueue failed', [
                'model' => $model::class,
                'id'    => $model->getKey(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
