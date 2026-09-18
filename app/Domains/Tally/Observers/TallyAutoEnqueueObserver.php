<?php

namespace App\Domains\Tally\Observers;

use App\Domains\Payment\Models\CreditNote;
use App\Domains\Payment\Models\Payment;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Tally\Services\TallyExportService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Auto-enqueue supported documents to the Tally queue on create AND on transition
 * to a posted status. Silent on failure — Tally sync must NEVER break the main flow.
 */
class TallyAutoEnqueueObserver
{
    public function __construct(protected TallyExportService $tallyService) {}

    public function created(Model $model): void
    {
        $this->maybeEnqueue($model);
    }

    public function updated(Model $model): void
    {
        // Only re-enqueue if `status` transitioned to a posted-like state.
        if (! $model->wasChanged('status')) {
            return;
        }

        $this->maybeEnqueue($model);
    }

    protected function maybeEnqueue(Model $model): void
    {
        if (! ($model instanceof Invoice || $model instanceof Payment || $model instanceof CreditNote || $model instanceof PurchaseInvoice)) {
            return;
        }

        try {
            $this->tallyService->enqueuePostedDocument($model);
        } catch (\Throwable $e) {
            // Never bubble Tally issues into the primary transaction.
            Log::warning('Tally auto-enqueue failed', [
                'model' => $model::class,
                'id' => $model->getKey(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
