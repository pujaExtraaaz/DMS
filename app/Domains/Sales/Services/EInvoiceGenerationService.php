<?php

namespace App\Domains\Sales\Services;

use App\Domains\Sales\Models\EInvoice;
use App\Domains\Sales\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Orchestrates IRN generation/cancel for tax invoices.
 * Keeps provider switching + persistence out of the HTTP controller.
 */
class EInvoiceGenerationService
{
    public function __construct(
        protected MastersIndiaGspService $mastersIndia,
        protected GstComplianceService $compliance,
    ) {}

    public function generateIrn(Invoice $invoice): array
    {
        if (! config('einvoice.enabled', true)) {
            throw new RuntimeException('E-Invoice generation is disabled.');
        }

        // selling_company is an accessor (not a relation) — eager-load company instead
        $invoice->loadMissing(['customer', 'items.product', 'items.uom', 'eInvoice', 'company']);

        return DB::transaction(function () use ($invoice) {
            /** @var Invoice $locked */
            $locked = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $locked->loadMissing(['customer', 'items.product', 'items.uom', 'eInvoice', 'company']);

            $existing = $locked->eInvoice;
            if ($existing && in_array($existing->status, ['generated', 'active'], true) && filled($existing->irn)) {
                throw new RuntimeException('E-Invoice has already been generated with IRN: '.$existing->irn);
            }

            if ($this->isProcessingLocked($locked->einvoice_status, $existing?->status, $existing?->requested_at, (int) config('einvoice.processing_stale_minutes', 15))) {
                throw new RuntimeException('E-Invoice generation is already in progress. Please wait and retry.');
            }

            $eligibility = $this->compliance->evaluateEInvoiceEligibility($locked);
            if (! ($eligibility['eligible'] ?? false)) {
                throw new RuntimeException($eligibility['reason'] ?? 'Invoice is not eligible for E-Invoicing.');
            }

            $locked->update(['einvoice_status' => 'processing']);
            EInvoice::updateOrCreate(
                ['invoice_id' => $locked->id],
                [
                    'status' => 'processing',
                    'provider' => (string) config('einvoice.provider', 'masters_india'),
                    'requested_at' => now(),
                    'last_error' => null,
                ]
            );

            try {
                $result = $this->mastersIndia->generateIrn($locked->fresh(['customer', 'items.product', 'items.uom', 'eInvoice', 'company']));
                $this->applySuccessToSale($locked, $result);

                return $result;
            } catch (\Throwable $e) {
                $this->applyFailureToSale($locked, $e->getMessage());
                throw $e;
            }
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function applySuccessToSale(Invoice $invoice, array $data): EInvoice
    {
        $record = EInvoice::updateOrCreate(
            ['invoice_id' => $invoice->id],
            [
                'status' => $data['status'] ?? 'generated',
                'provider' => (string) ($data['provider'] ?? config('einvoice.provider', 'masters_india')),
                'irn' => $data['irn'] ?? null,
                'ack_no' => $data['ack_no'] ?? null,
                'ack_date' => isset($data['ack_date']) ? Carbon::parse($data['ack_date']) : null,
                'signed_invoice' => $data['signed_invoice'] ?? null,
                'signed_qr_base64' => $data['signed_qr_base64'] ?? null,
                'requested_at' => now(),
                'last_error' => null,
                'payload' => $data['raw'] ?? null,
            ]
        );

        $invoice->update(['einvoice_status' => 'generated']);

        // Portal may return EwbNo with IRN — copy if invoice has no EWB yet
        $ewbNo = $data['eway_bill_no']
            ?? data_get($data, 'raw.EwbNo')
            ?? data_get($data, 'raw.ewbNo')
            ?? data_get($data, 'raw.message.EwbNo');

        if (filled($ewbNo) && blank($invoice->eway_bill_no)) {
            $invoice->update([
                'eway_bill_no' => (string) $ewbNo,
                'eway_bill_date' => now(),
                'eway_bill_status' => 'generated',
            ]);
        }

        return $record;
    }

    public function applyFailureToSale(Invoice $invoice, string $error): void
    {
        EInvoice::updateOrCreate(
            ['invoice_id' => $invoice->id],
            [
                'status' => 'failed',
                'last_error' => substr($error, 0, 2000),
                'requested_at' => now(),
            ]
        );
        $invoice->update(['einvoice_status' => 'failed']);
    }

    public function cancelIrn(Invoice $invoice, string $cancelReason, ?string $cancelRemarks = null): array
    {
        $invoice->loadMissing(['eInvoice']);
        $result = $this->mastersIndia->cancelIrn($invoice, $cancelReason, $cancelRemarks);

        $reasonText = match ((string) $cancelReason) {
            '1' => 'Duplicate',
            '2' => 'Data entry mistake',
            '3' => 'Order cancelled',
            default => 'Others',
        };

        EInvoice::updateOrCreate(
            ['invoice_id' => $invoice->id],
            [
                'status' => 'cancelled',
                'cancel_reason' => $reasonText,
                'cancel_remarks' => $cancelRemarks,
                'cancelled_at' => now(),
                'cancelled_by' => auth()->id(),
            ]
        );
        $invoice->update(['einvoice_status' => 'cancelled']);

        return $result;
    }

    protected function isProcessingLocked(?string $invoiceStatus, ?string $recordStatus, mixed $requestedAt, int $staleMinutes): bool
    {
        $isProcessing = $invoiceStatus === 'processing' || $recordStatus === 'processing';
        if (! $isProcessing) {
            return false;
        }

        if (! $requestedAt) {
            return true;
        }

        $at = $requestedAt instanceof Carbon ? $requestedAt : Carbon::parse($requestedAt);

        return $at->gt(now()->subMinutes(max(1, $staleMinutes)));
    }
}
