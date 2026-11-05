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

                // If customer or date changed, also recalculate the previous customer/period targets
                if ($model->wasChanged('customer_id') && $model->getOriginal('customer_id')) {
                    $originalCustomerId = (int) $model->getOriginal('customer_id');
                    $date = $model->getOriginal('invoice_date') ?? $model->invoice_date;
                    $this->calculationService->recalculateForCustomerIdAndDate($originalCustomerId, $date);
                }
                if ($model->wasChanged('invoice_date') && $model->getOriginal('invoice_date')) {
                    $originalDate = $model->getOriginal('invoice_date');
                    $customerId = (int) ($model->getOriginal('customer_id') ?? $model->customer_id);
                    $this->calculationService->recalculateForCustomerIdAndDate($customerId, $originalDate);
                }
            } elseif ($model instanceof CreditNote) {
                $this->calculationService->recalculateForCreditNote($model);

                if ($model->wasChanged('customer_id') && $model->getOriginal('customer_id')) {
                    $originalCustomerId = (int) $model->getOriginal('customer_id');
                    $date = $model->getOriginal('credit_note_date') ?? $model->credit_note_date;
                    $this->calculationService->recalculateForCustomerIdAndDate($originalCustomerId, $date);
                }
                if ($model->wasChanged('credit_note_date') && $model->getOriginal('credit_note_date')) {
                    $originalDate = $model->getOriginal('credit_note_date');
                    $customerId = (int) ($model->getOriginal('customer_id') ?? $model->customer_id);
                    $this->calculationService->recalculateForCustomerIdAndDate($customerId, $originalDate);
                }
            } else {
                $this->calculationService->recalculateAll(true);
            }
        } catch (\Throwable $e) {
            // Silently catch observer failures so primary invoice transactions never abort
            report($e);
        }
    }
}

