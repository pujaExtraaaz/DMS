<?php

namespace App\Domains\Purchasing\Services;

use App\Domains\Organization\Services\FinancialYearService;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\SupplierPayable;
use App\Domains\Purchasing\Models\SupplierPayment;
use App\Models\User;
use App\Support\AuditLogService;
use App\Support\DocumentNumberService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SupplierPaymentService
{
    public function __construct(
        protected DocumentNumberService $documentNumberService,
        protected AuditLogService $auditLogService,
        protected FinancialYearService $financialYearService,
    ) {}

    public function create(array $data, User $actor): SupplierPayment
    {
        return DB::transaction(function () use ($data, $actor) {
            $this->financialYearService->assertOpen($data['payment_date']);

            $amount = round((float) $data['amount'], 2);
            if ($amount <= 0) {
                throw new InvalidArgumentException('Payment amount must be greater than zero.');
            }

            if (! empty($data['purchase_invoice_id'])) {
                $invoice = PurchaseInvoice::query()->findOrFail($data['purchase_invoice_id']);
                if ((int) $invoice->supplier_id !== (int) $data['supplier_id']) {
                    throw new InvalidArgumentException('Purchase invoice does not belong to the selected supplier.');
                }
            }

            $payment = SupplierPayment::create([
                'payment_no' => $this->documentNumberService->nextDocument('SPAY', now(), 4),
                'company_id' => $data['company_id'] ?? null,
                'supplier_id' => $data['supplier_id'],
                'purchase_invoice_id' => $data['purchase_invoice_id'] ?? null,
                'payment_date' => $data['payment_date'],
                'amount' => $amount,
                'method' => $data['method'] ?? 'bank',
                'reference_no' => $data['reference_no'] ?? null,
                'status' => 'completed',
                'notes' => $data['notes'] ?? null,
                'recorded_by' => $actor->id,
            ]);

            $this->recordPayableCredit($payment);

            $this->auditLogService->record($payment, 'created', $actor->name);

            return $payment->load(['supplier', 'purchaseInvoice']);
        });
    }

    public function cancel(SupplierPayment $payment, User $actor): SupplierPayment
    {
        return DB::transaction(function () use ($payment, $actor) {
            $locked = SupplierPayment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === 'cancelled') {
                return $locked;
            }

            if ($locked->status !== 'completed') {
                throw new InvalidArgumentException('Only completed payments can be cancelled.');
            }

            $previous = (float) (SupplierPayable::query()
                ->where('supplier_id', $locked->supplier_id)
                ->orderByDesc('id')
                ->value('balance') ?? 0);

            SupplierPayable::create([
                'supplier_id' => $locked->supplier_id,
                'type' => 'payment_reversal',
                'reference_type' => $locked->getMorphClass(),
                'reference_id' => $locked->id,
                'debit' => $locked->amount,
                'credit' => 0,
                'balance' => $previous + (float) $locked->amount,
                'notes' => "Cancel supplier payment {$locked->payment_no}",
            ]);

            $locked->update(['status' => 'cancelled']);
            $this->auditLogService->record($locked, 'cancelled', $actor->name);

            return $locked->fresh(['supplier', 'purchaseInvoice']);
        });
    }

    protected function recordPayableCredit(SupplierPayment $payment): void
    {
        $previous = (float) (SupplierPayable::query()
            ->where('supplier_id', $payment->supplier_id)
            ->orderByDesc('id')
            ->value('balance') ?? 0);

        $ref = $payment->purchaseInvoice?->invoice_no;

        SupplierPayable::create([
            'supplier_id' => $payment->supplier_id,
            'type' => 'payment',
            'reference_type' => $payment->getMorphClass(),
            'reference_id' => $payment->id,
            'debit' => 0,
            'credit' => $payment->amount,
            'balance' => $previous - (float) $payment->amount,
            'notes' => 'Supplier payment '.$payment->payment_no.($ref ? " for {$ref}" : ''),
        ]);
    }
}
