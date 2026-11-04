<?php

namespace App\Domains\Sales\Services;

use App\Domains\Organization\Models\Company;
use App\Domains\Sales\Models\Invoice;
use App\Support\AuditLogService;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Enterprise client for Masters India GST / IRP / E-Way Bill APIs:
 *   1. generateIrn()      — Request IRN + signed QR for a B2B tax invoice (IRP Schema 1.1)
 *   2. cancelIrn()        — Cancel an existing IRN with valid statutory reason & remark
 *   3. generateEWayBill() — Generate an E-way Bill (against IRN or direct consignment)
 *   4. cancelEWayBill()   — Cancel an existing E-Way Bill with valid statutory reason
 *
 * When credentials are not configured or fallback_to_stub is enabled,
 * returns deterministic dummy payloads so development and testing proceed smoothly.
 */
class MastersIndiaGspService
{
    protected string $baseUrl;
    protected GstComplianceService $complianceService;
    protected AuditLogService $auditLogService;

    public function __construct(
        ?GstComplianceService $complianceService = null,
        ?AuditLogService $auditLogService = null
    ) {
        $this->baseUrl = rtrim((string) config('services.mastersindia.base_url', 'https://sandb-api.mastersindia.co'), '/');
        $this->complianceService = $complianceService ?? app(GstComplianceService::class);
        $this->auditLogService = $auditLogService ?? app(AuditLogService::class);
    }

    public function isConfigured(): bool
    {
        return filled(config('services.mastersindia.client_id'))
            && filled(config('services.mastersindia.client_secret'))
            && filled(config('services.mastersindia.username'))
            && filled(config('services.mastersindia.password'))
            && filled(config('services.mastersindia.gstin'));
    }

    /**
     * Generate E-Invoice IRN & signed QR code.
     *
     * @return array{
     *   irn: string,
     *   ack_no: ?string,
     *   ack_date: ?string,
     *   signed_invoice: ?string,
     *   signed_qr_base64: ?string,
     *   status: string,
     *   raw: array
     * }
     */
    public function generateIrn(Invoice $invoice): array
    {
        // Eligibility pre-check
        $eligibility = $this->complianceService->evaluateEInvoiceEligibility($invoice);
        if (! $eligibility['applicable'] && $eligibility['status'] === 'not_applicable') {
            throw new RuntimeException($eligibility['reason']);
        }

        if (! $this->isConfigured()) {
            $stub = $this->stubIrn($invoice, 'Masters India credentials not configured; using deterministic stub.');
            $this->auditLogService->record($invoice, 'einvoice_generated_stub', null, [
                'irn' => $stub['irn'],
                'ack_no' => $stub['ack_no'],
                'status' => 'stub',
            ]);
            return $stub;
        }

        try {
            $payload = $this->buildIrnPayload($invoice);
            $response = $this->client()->post('/ei/api/invoice', $payload);

            if (! $response->ok()) {
                $errBody = $response->json();
                $errMsg = $errBody['message'] ?? $errBody['Message'] ?? $errBody['error'] ?? $response->body();
                Log::warning('MastersIndia IRN call failed', ['status' => $response->status(), 'body' => $errMsg]);

                if ((bool) config('services.mastersindia.fallback_to_stub')) {
                    $stub = $this->stubIrn($invoice, 'Upstream response ' . $response->status() . ': ' . $errMsg);
                    $this->auditLogService->record($invoice, 'einvoice_fallback_stub', null, ['reason' => $errMsg]);
                    return $stub;
                }

                $this->auditLogService->record($invoice, 'einvoice_failed', null, ['error' => $errMsg]);
                throw new RuntimeException('Masters India IRN generation failed: ' . $errMsg);
            }

            $body = $response->json();
            $data = $body['Data'] ?? $body['data'] ?? $body;

            $result = [
                'irn' => (string) ($data['Irn'] ?? $data['irn'] ?? ''),
                'ack_no' => (string) ($data['AckNo'] ?? $data['ack_no'] ?? ''),
                'ack_date' => $data['AckDt'] ?? $data['ack_date'] ?? null,
                'signed_invoice' => $data['SignedInvoice'] ?? $data['signed_invoice'] ?? null,
                'signed_qr_base64' => $data['SignedQRCode'] ?? $data['signed_qr_base64'] ?? null,
                'status' => 'generated',
                'raw' => $body,
            ];

            $this->auditLogService->record($invoice, 'einvoice_generated', null, [
                'irn' => $result['irn'],
                'ack_no' => $result['ack_no'],
            ]);

            return $result;
        } catch (\Throwable $e) {
            Log::error('MastersIndia IRN exception', ['error' => $e->getMessage()]);
            if ((bool) config('services.mastersindia.fallback_to_stub')) {
                return $this->stubIrn($invoice, 'Exception: ' . $e->getMessage());
            }
            throw $e;
        }
    }

    /**
     * Cancel an active E-Invoice IRN.
     *
     * @param  string  $cancelReason  1=Duplicate, 2=Data entry mistake, 3=Order cancelled, 4=Others
     * @return array{irn: string, cancel_date: ?string, status: string, raw: array}
     */
    public function cancelIrn(Invoice $invoice, string $cancelReason, ?string $cancelRemarks = null): array
    {
        $eInvoice = $invoice->eInvoice;
        if (! $eInvoice || blank($eInvoice->irn)) {
            throw new RuntimeException('Cannot cancel E-Invoice: No active IRN found for this invoice.');
        }

        if ($eInvoice->status === 'cancelled') {
            throw new RuntimeException('This E-Invoice has already been cancelled.');
        }

        $remarks = trim((string) ($cancelRemarks ?: 'Cancelled by user'));
        if (strlen($remarks) < 3) {
            $remarks = 'Invoice cancelled by user';
        }

        if (! $this->isConfigured()) {
            $stub = [
                'irn' => $eInvoice->irn,
                'cancel_date' => Carbon::now()->toIso8601String(),
                'status' => 'cancelled',
                'raw' => ['note' => 'Stub cancellation (no live credentials)'],
            ];
            $this->auditLogService->record($invoice, 'einvoice_cancelled_stub', null, [
                'irn' => $eInvoice->irn,
                'reason' => $cancelReason,
                'remarks' => $remarks,
            ]);
            return $stub;
        }

        try {
            $payload = [
                'Irn' => $eInvoice->irn,
                'CnlRsn' => $cancelReason,
                'CnlRem' => substr($remarks, 0, 100),
            ];

            $response = $this->client()->post('/ei/api/invoice/cancel', $payload);

            if (! $response->ok()) {
                $err = $response->json('message') ?? $response->body();
                Log::warning('MastersIndia IRN cancellation failed', ['status' => $response->status(), 'body' => $err]);

                if ((bool) config('services.mastersindia.fallback_to_stub')) {
                    return [
                        'irn' => $eInvoice->irn,
                        'cancel_date' => Carbon::now()->toIso8601String(),
                        'status' => 'cancelled',
                        'raw' => ['note' => 'Fallback stub cancellation: ' . $err],
                    ];
                }

                throw new RuntimeException('E-Invoice cancellation failed: ' . $err);
            }

            $body = $response->json();
            $data = $body['Data'] ?? $body['data'] ?? $body;

            $this->auditLogService->record($invoice, 'einvoice_cancelled', null, [
                'irn' => $eInvoice->irn,
                'reason' => $cancelReason,
                'remarks' => $remarks,
            ]);

            return [
                'irn' => (string) ($data['Irn'] ?? $eInvoice->irn),
                'cancel_date' => $data['CancelDate'] ?? Carbon::now()->toIso8601String(),
                'status' => 'cancelled',
                'raw' => $body,
            ];
        } catch (\Throwable $e) {
            Log::error('MastersIndia IRN cancel exception', ['error' => $e->getMessage()]);
            if ((bool) config('services.mastersindia.fallback_to_stub')) {
                return [
                    'irn' => $eInvoice->irn,
                    'cancel_date' => Carbon::now()->toIso8601String(),
                    'status' => 'cancelled',
                    'raw' => ['note' => 'Exception fallback: ' . $e->getMessage()],
                ];
            }
            throw $e;
        }
    }

    /**
     * Generate an E-Way Bill (using IRN if present, or direct EWB API if not).
     *
     * @param  array<string, mixed>  $extra
     * @return array{
     *   eway_bill_no: string,
     *   ewb_date: ?string,
     *   valid_upto: ?string,
     *   status: string,
     *   raw: array
     * }
     */
    public function generateEWayBill(Invoice $invoice, array $extra = []): array
    {
        $eInvoice = $invoice->eInvoice;
        $hasActiveIrn = $eInvoice && filled($eInvoice->irn) && $eInvoice->status !== 'cancelled';

        if (! $this->isConfigured()) {
            $stub = $this->stubEwayBill($invoice, 'Masters India credentials not configured; using deterministic stub.');
            $this->auditLogService->record($invoice, 'eway_bill_generated_stub', null, [
                'eway_bill_no' => $stub['eway_bill_no'],
                'status' => 'stub',
            ]);
            return $stub;
        }

        try {
            if ($hasActiveIrn) {
                // Generate E-Way Bill by IRN
                $payload = array_merge($this->buildEwbByIrnPayload($invoice), $extra);
                $endpoint = '/ei/api/ewayapi';
            } else {
                // Direct E-Way Bill generation (for B2C, unregistered, or independent invoices)
                $payload = array_merge($this->buildDirectEwbPayload($invoice), $extra);
                $endpoint = '/ewb/api/ewaybill';
            }

            $response = $this->client()->post($endpoint, $payload);

            if (! $response->ok()) {
                $err = $response->json('message') ?? $response->body();
                Log::warning('MastersIndia EWB call failed', ['endpoint' => $endpoint, 'status' => $response->status(), 'body' => $err]);

                if ((bool) config('services.mastersindia.fallback_to_stub')) {
                    $stub = $this->stubEwayBill($invoice, 'Upstream response ' . $response->status() . ': ' . $err);
                    $this->auditLogService->record($invoice, 'eway_bill_fallback_stub', null, ['reason' => $err]);
                    return $stub;
                }

                $this->auditLogService->record($invoice, 'eway_bill_failed', null, ['error' => $err]);
                throw new RuntimeException('Masters India E-Way Bill generation failed: ' . $err);
            }

            $body = $response->json();
            $data = $body['Data'] ?? $body['data'] ?? $body;

            $result = [
                'eway_bill_no' => (string) ($data['EwbNo'] ?? $data['ewbNo'] ?? $data['eway_bill_no'] ?? ''),
                'ewb_date' => $data['EwbDt'] ?? $data['ewb_date'] ?? Carbon::now()->toIso8601String(),
                'valid_upto' => $data['EwbValidTill'] ?? $data['valid_upto'] ?? null,
                'status' => 'generated',
                'raw' => $body,
            ];

            $this->auditLogService->record($invoice, 'eway_bill_generated', null, [
                'eway_bill_no' => $result['eway_bill_no'],
                'valid_upto' => $result['valid_upto'],
            ]);

            return $result;
        } catch (\Throwable $e) {
            Log::error('MastersIndia EWB exception', ['error' => $e->getMessage()]);
            if ((bool) config('services.mastersindia.fallback_to_stub')) {
                return $this->stubEwayBill($invoice, 'Exception: ' . $e->getMessage());
            }
            throw $e;
        }
    }

    /**
     * Cancel an active E-Way Bill.
     *
     * @param  string  $cancelReason  1=Duplicate, 2=Order Cancelled, 3=Data Entry Mistake, 4=Others
     */
    public function cancelEWayBill(Invoice $invoice, string $cancelReason, ?string $cancelRemarks = null): array
    {
        $eWayBill = $invoice->eWayBill;
        if (! $eWayBill || blank($eWayBill->eway_bill_no)) {
            throw new RuntimeException('Cannot cancel E-Way Bill: No active E-Way Bill found for this invoice.');
        }

        if ($eWayBill->status === 'cancelled') {
            throw new RuntimeException('This E-Way Bill has already been cancelled.');
        }

        $remarks = trim((string) ($cancelRemarks ?: 'Cancelled by user'));
        if (strlen($remarks) < 3) {
            $remarks = 'Order movement cancelled';
        }

        if (! $this->isConfigured()) {
            $stub = [
                'eway_bill_no' => $eWayBill->eway_bill_no,
                'cancel_date' => Carbon::now()->toIso8601String(),
                'status' => 'cancelled',
                'raw' => ['note' => 'Stub cancellation (no live credentials)'],
            ];
            $this->auditLogService->record($invoice, 'eway_bill_cancelled_stub', null, [
                'eway_bill_no' => $eWayBill->eway_bill_no,
                'reason' => $cancelReason,
                'remarks' => $remarks,
            ]);
            return $stub;
        }

        try {
            $payload = [
                'ewbNo' => $eWayBill->eway_bill_no,
                'cancelRsnCode' => (int) $cancelReason,
                'cancelRmrk' => substr($remarks, 0, 100),
            ];

            $response = $this->client()->post('/ewb/api/ewaybill/cancel', $payload);

            if (! $response->ok()) {
                $err = $response->json('message') ?? $response->body();
                Log::warning('MastersIndia EWB cancellation failed', ['status' => $response->status(), 'body' => $err]);

                if ((bool) config('services.mastersindia.fallback_to_stub')) {
                    return [
                        'eway_bill_no' => $eWayBill->eway_bill_no,
                        'cancel_date' => Carbon::now()->toIso8601String(),
                        'status' => 'cancelled',
                        'raw' => ['note' => 'Fallback stub cancellation: ' . $err],
                    ];
                }

                throw new RuntimeException('E-Way Bill cancellation failed: ' . $err);
            }

            $body = $response->json();
            $data = $body['Data'] ?? $body['data'] ?? $body;

            $this->auditLogService->record($invoice, 'eway_bill_cancelled', null, [
                'eway_bill_no' => $eWayBill->eway_bill_no,
                'reason' => $cancelReason,
                'remarks' => $remarks,
            ]);

            return [
                'eway_bill_no' => (string) ($data['ewbNo'] ?? $eWayBill->eway_bill_no),
                'cancel_date' => $data['cancelDate'] ?? Carbon::now()->toIso8601String(),
                'status' => 'cancelled',
                'raw' => $body,
            ];
        } catch (\Throwable $e) {
            Log::error('MastersIndia EWB cancel exception', ['error' => $e->getMessage()]);
            if ((bool) config('services.mastersindia.fallback_to_stub')) {
                return [
                    'eway_bill_no' => $eWayBill->eway_bill_no,
                    'cancel_date' => Carbon::now()->toIso8601String(),
                    'status' => 'cancelled',
                    'raw' => ['note' => 'Exception fallback: ' . $e->getMessage()],
                ];
            }
            throw $e;
        }
    }

    protected function client(): PendingRequest
    {
        $token = $this->accessToken();

        return Http::baseUrl($this->baseUrl)
            ->timeout((int) config('services.mastersindia.timeout', 30))
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
                'client_id' => (string) config('services.mastersindia.client_id'),
                'client_secret' => (string) config('services.mastersindia.client_secret'),
                'gstin' => (string) config('services.mastersindia.gstin'),
            ]);
    }

    protected function accessToken(): string
    {
        $clientId = (string) config('services.mastersindia.client_id');
        $key = 'mastersindia:token:' . md5($clientId);

        return Cache::remember($key, now()->addMinutes(50), function () {
            $resp = Http::baseUrl($this->baseUrl)
                ->timeout((int) config('services.mastersindia.timeout', 30))
                ->asJson()
                ->post('/oauth/token', [
                    'username' => config('services.mastersindia.username'),
                    'password' => config('services.mastersindia.password'),
                    'client_id' => config('services.mastersindia.client_id'),
                    'client_secret' => config('services.mastersindia.client_secret'),
                    'grant_type' => 'password',
                ]);

            if (! $resp->ok()) {
                throw new RuntimeException('Masters India authentication failed: ' . $resp->body());
            }

            return (string) ($resp->json('access_token') ?? $resp->json('data.access_token') ?? '');
        });
    }

    /**
     * Build standard IRP Schema 1.1 JSON payload.
     */
    protected function buildIrnPayload(Invoice $invoice): array
    {
        $company = $invoice->selling_company
            ?? Company::query()->find($invoice->company_id)
            ?? Company::resolveCurrent()
            ?? Company::first();

        $customer = $invoice->customer;
        $sellerGstin = (string) ($company?->gstin ?? config('services.mastersindia.gstin'));
        $buyerGstin = (string) ($invoice->billingAddress?->gstin ?: ($customer?->gstin ?: 'URP'));

        $sellerStateCode = $this->complianceService->resolveStateCode($company?->state, $sellerGstin);
        $buyerStateCode = $this->complianceService->resolveStateCode(
            $invoice->place_of_supply_state ?: ($invoice->delivery_state ?: ($customer?->state ?: '')),
            $buyerGstin
        );

        $isIntra = $invoice->tax_type === 'intra' || ($invoice->tax_type === null && ($invoice->cgst_amount > 0 || $invoice->sgst_amount > 0));

        $items = $invoice->items->map(function ($item, $i) use ($isIntra) {
            $taxable = (float) ($item->taxable_amount ?? round((float) $item->line_total - (float) $item->tax_amount, 2));
            $taxRate = (float) ($item->tax_rate ?? ($item->product?->tax_rate ?? 0));
            $cgst = $isIntra ? (float) ($item->cgst_amount ?? round($item->tax_amount / 2, 2)) : 0.0;
            $sgst = $isIntra ? (float) ($item->sgst_amount ?? round($item->tax_amount / 2, 2)) : 0.0;
            $igst = ! $isIntra ? (float) ($item->igst_amount ?? (float) $item->tax_amount) : 0.0;

            $uomCode = strtoupper((string) ($item->uom?->code ?? 'NOS'));
            if ($uomCode === 'PIECES' || $uomCode === 'PIECE') $uomCode = 'PCS';

            return [
                'SlNo' => (string) ($i + 1),
                'PrdDesc' => substr((string) ($item->product?->name ?? $item->item_name ?? 'Item'), 0, 100),
                'IsServc' => 'N',
                'HsnCd' => (string) ($item->hsn_code ?? $item->product?->hsn_code ?? '85176200'),
                'Qty' => (float) $item->quantity,
                'Unit' => $uomCode,
                'UnitPrice' => (float) ($item->entered_unit_price ?? $item->unit_price),
                'TotAmt' => round((float) $item->quantity * (float) ($item->entered_unit_price ?? $item->unit_price), 2),
                'Discount' => (float) $item->discount_amount,
                'AssAmt' => $taxable,
                'GstRt' => $taxRate,
                'CgstAmt' => $cgst,
                'SgstAmt' => $sgst,
                'IgstAmt' => $igst,
                'TotItemVal' => (float) $item->line_total,
            ];
        })->values()->all();

        return [
            'Version' => '1.1',
            'TranDtls' => [
                'TaxSch' => 'GST',
                'SupTyp' => 'B2B',
                'RegRev' => 'N',
            ],
            'DocDtls' => [
                'Typ' => 'INV',
                'No' => $invoice->invoice_no,
                'Dt' => $invoice->invoice_date->format('d/m/Y'),
            ],
            'SellerDtls' => [
                'Gstin' => $sellerGstin,
                'LglNm' => substr((string) ($company?->legal_name ?: ($company?->name ?: 'Seller')), 0, 100),
                'Addr1' => substr((string) ($company?->address ?: 'Head Office Address'), 0, 100),
                'Loc' => substr((string) ($company?->city ?: ($company?->state ?: 'City')), 0, 50),
                'Pin' => (int) ($company?->pincode ?: 400001),
                'Stcd' => $sellerStateCode,
            ],
            'BuyerDtls' => [
                'Gstin' => $buyerGstin,
                'LglNm' => substr((string) ($customer?->name ?: 'Customer'), 0, 100),
                'Pos' => $buyerStateCode,
                'Addr1' => substr((string) ($invoice->billing_address ?: ($customer?->address ?: 'Customer Address')), 0, 100),
                'Loc' => substr((string) ($invoice->destination ?: ($customer?->city ?: ($customer?->state ?: 'City'))), 0, 50),
                'Pin' => (int) ($invoice->shippingAddress?->pincode ?: ($customer?->pincode ?: 400001)),
                'Stcd' => $buyerStateCode,
            ],
            'ItemList' => $items,
            'ValDtls' => [
                'AssVal' => (float) ($invoice->taxable_amount ?? $invoice->subtotal),
                'CgstVal' => $isIntra ? (float) $invoice->cgst_amount : 0.0,
                'SgstVal' => $isIntra ? (float) $invoice->sgst_amount : 0.0,
                'IgstVal' => ! $isIntra ? (float) ($invoice->igst_amount ?: $invoice->tax_amount) : 0.0,
                'Discount' => (float) $invoice->discount_amount,
                'RndOffAmt' => (float) ($invoice->round_off ?? 0),
                'TotInvVal' => (float) $invoice->grand_total,
            ],
        ];
    }

    /**
     * Build E-Way Bill payload generated against an IRN.
     */
    protected function buildEwbByIrnPayload(Invoice $invoice): array
    {
        $eInv = $invoice->eInvoice;

        $docNo = $invoice->lr_rr_no ?: ($invoice->dispatch_doc_no ?: ($invoice->reference_no ?: $invoice->invoice_no));
        $docDate = $invoice->lr_rr_date ? $invoice->lr_rr_date->format('d/m/Y') : $invoice->invoice_date->format('d/m/Y');

        return [
            'Irn' => $eInv?->irn,
            'Distance' => (int) ($invoice->distance_km ?: 50),
            'TransMode' => (string) ($invoice->transport_mode ?: '1'),
            'TransId' => $invoice->transporter_id ?: null,
            'TransName' => $invoice->transporter_name ?: null,
            'TransDocNo' => $docNo,
            'TransDocDt' => $docDate,
            'VehNo' => $invoice->vehicle_no ?: null,
            'VehType' => $invoice->vehicle_type ?: 'R',
        ];
    }

    /**
     * Build direct E-Way Bill payload (for B2C / non-IRN invoices).
     */
    protected function buildDirectEwbPayload(Invoice $invoice): array
    {
        $company = $invoice->selling_company
            ?? Company::query()->find($invoice->company_id)
            ?? Company::resolveCurrent()
            ?? Company::first();

        $customer = $invoice->customer;
        $sellerGstin = (string) ($company?->gstin ?? config('services.mastersindia.gstin'));
        $buyerGstin = (string) ($invoice->billingAddress?->gstin ?: ($customer?->gstin ?: 'URP'));

        $fromStateCode = $this->complianceService->resolveStateCode($company?->state, $sellerGstin);
        $toStateCode = $this->complianceService->resolveStateCode(
            $invoice->place_of_supply_state ?: ($invoice->delivery_state ?: ($customer?->state ?: '')),
            $buyerGstin
        );

        $docNo = $invoice->lr_rr_no ?: ($invoice->dispatch_doc_no ?: $invoice->invoice_no);
        $docDate = $invoice->lr_rr_date ? $invoice->lr_rr_date->format('d/m/Y') : $invoice->invoice_date->format('d/m/Y');

        return [
            'supplyType' => 'O',
            'subSupplyType' => '1',
            'docType' => 'INV',
            'docNo' => $invoice->invoice_no,
            'docDate' => $invoice->invoice_date->format('d/m/Y'),
            'fromGstin' => $sellerGstin,
            'fromTrdName' => $company?->name,
            'fromAddr1' => substr((string) ($company?->address ?: 'Dispatch Office'), 0, 100),
            'fromPlace' => $company?->city ?: ($company?->state ?: 'City'),
            'fromPincode' => (int) ($company?->pincode ?: 400001),
            'fromStateCode' => (int) $fromStateCode,
            'actualFromStateCode' => (int) $fromStateCode,
            'toGstin' => $buyerGstin,
            'toTrdName' => $customer?->name,
            'toAddr1' => substr((string) ($invoice->shipping_address ?: ($customer?->address ?: 'Delivery Address')), 0, 100),
            'toPlace' => $invoice->destination ?: ($customer?->city ?: ($customer?->state ?: 'City')),
            'toPincode' => (int) ($invoice->shippingAddress?->pincode ?: ($customer?->pincode ?: 400001)),
            'toStateCode' => (int) $toStateCode,
            'actualToStateCode' => (int) $toStateCode,
            'totalValue' => (float) ($invoice->taxable_amount ?? $invoice->subtotal),
            'cgstValue' => (float) $invoice->cgst_amount,
            'sgstValue' => (float) $invoice->sgst_amount,
            'igstValue' => (float) $invoice->igst_amount,
            'totInvValue' => (float) $invoice->grand_total,
            'transMode' => (string) ($invoice->transport_mode ?: '1'),
            'transDistance' => (int) ($invoice->distance_km ?: 50),
            'transporterId' => $invoice->transporter_id ?: null,
            'transporterName' => $invoice->transporter_name ?: null,
            'transDocNo' => $docNo,
            'transDocDate' => $docDate,
            'vehicleNo' => $invoice->vehicle_no ?: null,
            'vehicleType' => $invoice->vehicle_type ?: 'R',
        ];
    }

    protected function stubIrn(Invoice $invoice, ?string $reason = null): array
    {
        $seed = hash('sha256', $invoice->invoice_no . $invoice->invoice_date->toDateString());
        $irn = strtoupper(substr($seed, 0, 64));
        $ackNo = substr(preg_replace('/\D/', '7', $seed), 0, 15);

        return [
            'irn' => $irn,
            'ack_no' => $ackNo,
            'ack_date' => Carbon::now()->toIso8601String(),
            'signed_invoice' => null,
            'signed_qr_base64' => base64_encode('IRN:' . $irn . '|GSTIN:' . ($invoice->customer?->gstin ?? 'URP') . '|INV:' . $invoice->invoice_no . '|TOT:' . $invoice->grand_total),
            'status' => 'generated',
            'raw' => [
                'note' => 'Deterministic stub because ' . ($reason ?? 'no credentials'),
                'Irn' => $irn,
                'AckNo' => $ackNo,
                'AckDt' => Carbon::now()->toIso8601String(),
            ],
        ];
    }

    protected function stubEwayBill(Invoice $invoice, ?string $reason = null): array
    {
        $seed = hash('sha256', $invoice->invoice_no . 'eway' . Carbon::now()->toDateString());
        $ewbNo = substr(preg_replace('/\D/', '3', $seed), 0, 12);

        return [
            'eway_bill_no' => $ewbNo,
            'ewb_date' => Carbon::now()->toIso8601String(),
            'valid_upto' => Carbon::now()->addDays(max(1, (int) ceil(($invoice->distance_km ?: 100) / 200)))->toIso8601String(),
            'status' => 'generated',
            'raw' => [
                'note' => 'Deterministic stub because ' . ($reason ?? 'no credentials'),
                'ewbNo' => $ewbNo,
                'ewbDate' => Carbon::now()->toIso8601String(),
            ],
        ];
    }
}
