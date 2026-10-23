<?php

namespace App\Domains\Target\Observers;

use App\Domains\Payment\Models\CreditNote;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Target\Services\SalesTargetCalculationService;

class SalesInvoiceTargetObserver
{
    public function __construct(
        protected SalesTargetCalculationService $calculationService
    ) {}

    public function saved($model): void
    {
        $this->handleRecalculation($model);
    }

    public function deleted($model): void
    {
        $this->handleRecalculation($model);
    }

    public function restored($model): void
    {
        $this->handleRecalculation($model);
    }

    protected function handleRecalculation($model): void
    {
        try {
            if ($model instanceof Invoice) {
                $this->calculationService->recalculateForInvoice($model);
            } elseif ($model instanceof CreditNote && $model->invoice) {
                $this->calculationService->recalculateForInvoice($model->invoice);
            } else {
                $this->calculationService->recalculateAll(true);
            }
        } catch (\Throwable $e) {
            // Silently catch observer failures so primary invoice transactions never abort
            report($e);
        }
    }
}

