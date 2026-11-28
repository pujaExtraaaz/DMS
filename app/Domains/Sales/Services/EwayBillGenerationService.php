<?php

namespace App\Domains\Sales\Services;

use App\Domains\Organization\Models\Company;
use App\Domains\Sales\Models\EWayBill;
use App\Domains\Sales\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Orchestrates E-Way Bill generate / cancel / vehicle update for tax invoices.
 */
class EwayBillGenerationService
{
    public function __construct(
        protected MastersIndiaGspService $mastersIndia,
        protected GstComplianceService $compliance,
    ) {}

    /**
     * @param  array<string, mixed>  $transportOverrides
     */
    public function generate(Invoice $invoice, array $transportOverrides = []): array
    {
        if (! config('ewaybill.enabled', true)) {
            throw new RuntimeException('E-Way Bill generation is disabled.');
        }

        // selling_company is an accessor (not a relation) — eager-load company instead
        $invoice->loadMissing(['customer', 'items.product', 'items.uom', 'eInvoice', 'eWayBill', 'company', 'shippingAddress']);

        return DB::transaction(function () use ($invoice, $transportOverrides) {
            /** @var Invoice $locked */
            $locked = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $locked->loadMissing(['customer', 'items.product', 'items.uom', 'eInvoice', 'eWayBill', 'company', 'shippingAddress']);

            if ($locked->isEWayBillGenerated() || ($locked->eWayBill && in_array($locked->eWayBill->status, ['generated', 'active'], true) && filled($locked->eWayBill->eway_bill_no))) {
                $existingNo = $locked->eWayBill?->eway_bill_no ?: $locked->eway_bill_no;
                throw new RuntimeException("E-Way Bill has already been generated with number: {$existingNo}. Duplicate generation is not allowed.");
            }

            if ($this->isProcessingLocked($locked->eway_bill_status, $locked->eWayBill?->status, $locked->eWayBill?->updated_at, (int) config('ewaybill.processing_stale_minutes', 15))) {
                throw new RuntimeException('E-Way Bill generation is already in progress. Please wait and retry.');
            }

            if (! empty($transportOverrides)) {
                $locked->update(array_filter($transportOverrides, fn ($v) => $v !== null && $v !== ''));
                $locked->refresh();
            }

            // Auto distance: send 0 so portal computes pin-pair km
            if (config('ewaybill.auto_distance', true) && (int) ($locked->distance_km ?? 0) <= 0) {
                $locked->update(['distance_km' => 0]);
            }

            $validation = $this->compliance->validateForEWayBillGeneration($locked);
            if (! $validation['valid']) {
                throw new RuntimeException('Cannot generate E-Way Bill: '.implode(' ', $validation['errors']));
            }

            $locked->update(['eway_bill_status' => 'processing']);
            EWayBill::updateOrCreate(
                ['invoice_id' => $locked->id],
                [
                    'status' => 'processing',
                    'provider' => (string) config('ewaybill.provider', 'masters_india'),
                    'last_error' => null,
                ]
            );

            try {
                $result = $this->mastersIndia->generateEWayBill($locked->fresh([
                    'customer', 'items.product', 'items.uom', 'eInvoice', 'eWayBill', 'company', 'shippingAddress',
                ]), [
                    'VehNo' => $locked->vehicle_no,
                    'VehType' => $locked->vehicle_type ?? 'R',
                    'TransMode' => $locked->transport_mode ?? '1',
                    'TransId' => $locked->transporter_id,
                    'TransName' => $locked->transporter_name,
                    'Distance' => (int) ($locked->distance_km ?? 0),
                ]);
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
    public function applySuccessToSale(Invoice $invoice, array $data): EWayBill
    {
        $company = Company::resolveCurrent() ?? Company::query()->first();
        $parse = fn (?string $dateStr): ?Carbon => $this->parsePortalDate($dateStr);

        $record = EWayBill::updateOrCreate(
            ['invoice_id' => $invoice->id],
            [
                'status' => $data['status'] ?? 'generated',
                'provider' => (string) ($data['provider'] ?? config('ewaybill.provider', 'masters_india')),
                'eway_bill_no' => $data['eway_bill_no'],
                'valid_upto' => $parse($data['valid_upto'] ?? null),
                'ewb_date' => $parse($data['ewb_date'] ?? null) ?? now(),
                'distance_km' => (int) ($invoice->distance_km ?? 0),
                'transporter_id' => $invoice->transporter_id,
                'transporter_name' => $invoice->transporter_name,
                'vehicle_no' => $invoice->vehicle_no,
                'vehicle_type' => $invoice->vehicle_type ?? 'R',
                'transport_mode' => $invoice->transport_mode ?? '1',
                'doc_no' => $invoice->lr_rr_no,
                'doc_date' => $invoice->lr_rr_date,
                'from_pincode' => $invoice->consignor_pincode ?: ($company?->pincode ?: null),
                'to_pincode' => $invoice->shippingAddress?->pincode ?: ($invoice->consignee_pincode ?: ($invoice->customer?->pincode ?: null)),
                'from_state' => $invoice->consignor_state ?: ($company?->state ?: null),
                'to_state' => $invoice->delivery_state ?: ($invoice->customer?->state ?: null),
                'last_error' => null,
                'payload' => $data['raw'] ?? null,
            ]
        );

        $invoice->update([
            'eway_bill_no' => $data['eway_bill_no'],
            'eway_bill_date' => $parse($data['ewb_date'] ?? null) ?? now(),
            'eway_bill_status' => 'generated',
        ]);

        return $record;
    }

    public function applyFailureToSale(Invoice $invoice, string $error): void
    {
        EWayBill::updateOrCreate(
            ['invoice_id' => $invoice->id],
            [
                'status' => 'failed',
                'last_error' => substr($error, 0, 2000),
            ]
        );
        $invoice->update(['eway_bill_status' => 'failed']);
    }

    public function cancel(Invoice $invoice, string $cancelReason, ?string $cancelRemarks = null): array
    {
        $invoice->loadMissing(['eWayBill']);
        $result = $this->mastersIndia->cancelEWayBill($invoice, $cancelReason, $cancelRemarks);

        $reasonText = match ((string) $cancelReason) {
            '1' => 'Duplicate',
            '2' => 'Order Cancelled',
            '3' => 'Data Entry Mistake',
            default => 'Others',
        };

        EWayBill::updateOrCreate(
            ['invoice_id' => $invoice->id],
            [
                'status' => 'cancelled',
                'cancel_reason' => $reasonText,
                'cancel_remarks' => $cancelRemarks,
                'cancelled_at' => now(),
                'cancelled_by' => auth()->id(),
            ]
        );
        $invoice->update(['eway_bill_status' => 'cancelled']);

        return $result;
    }

    /**
     * @param  array<string, mixed>  $vehicleData
     */
    public function updateVehicle(Invoice $invoice, array $vehicleData): array
    {
        $invoice->loadMissing(['eWayBill', 'company']);

        $invoice->update([
            'vehicle_no' => $vehicleData['vehicle_no'],
            'vehicle_type' => $vehicleData['vehicle_type'] ?? ($invoice->vehicle_type ?? 'R'),
        ]);

        if ($invoice->eWayBill) {
            $invoice->eWayBill->update([
                'vehicle_no' => $vehicleData['vehicle_no'],
                'vehicle_type' => $vehicleData['vehicle_type'] ?? ($invoice->vehicle_type ?? 'R'),
            ]);
        }

        return $this->mastersIndia->updateEWayVehicle($invoice->fresh(['eWayBill', 'company']), $vehicleData);
    }

    protected function parsePortalDate(?string $dateStr): ?Carbon
    {
        if (empty($dateStr)) {
            return null;
        }

        try {
            if (preg_match('#^\d{2}/\d{2}/\d{4}#', $dateStr)) {
                return Carbon::createFromFormat('d/m/Y H:i:s', strlen($dateStr) === 10 ? $dateStr.' 00:00:00' : $dateStr);
            }

            return Carbon::parse($dateStr);
        } catch (\Throwable) {
            try {
                return Carbon::parse(str_replace('/', '-', $dateStr));
            } catch (\Throwable) {
                return now();
            }
        }
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
