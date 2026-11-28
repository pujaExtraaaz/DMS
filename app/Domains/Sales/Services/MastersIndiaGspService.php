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
        $this->complianceService = $complianceService ?? app(GstComplianceService::class);
        $this->auditLogService = $auditLogService ?? app(AuditLogService::class);
        $this->baseUrl = $this->resolvedBaseUrl();
    }

    public function isConfigured(): bool
    {
        return match ($this->resolveAuthMode()) {
            'api_key' => filled(config('services.mastersindia.api_key')),
            'token_auth' => filled(config('services.mastersindia.token_auth_username'))
                && filled(config('services.mastersindia.token_auth_password')),
            'oauth' => filled(config('services.mastersindia.client_id'))
                && filled(config('services.mastersindia.client_secret'))
                && filled(config('services.mastersindia.username'))
                && filled(config('services.mastersindia.password'))
                && filled(config('services.mastersindia.gstin')),
            default => false,
        };
    }

    /**
     * auto → api_key → token_auth → oauth
     */
    public function resolveAuthMode(): string
    {
        $mode = strtolower((string) config('services.mastersindia.auth_mode', 'auto'));

        if ($mode !== 'auto') {
            return $mode;
        }

        if (filled(config('services.mastersindia.api_key'))) {
            return 'api_key';
        }

        if (filled(config('services.mastersindia.token_auth_username'))
            && filled(config('services.mastersindia.token_auth_password'))) {
            return 'token_auth';
        }

        if (filled(config('services.mastersindia.client_id'))
            && filled(config('services.mastersindia.client_secret'))
            && filled(config('services.mastersindia.username'))
            && filled(config('services.mastersindia.password'))) {
            return 'oauth';
        }

        return 'none';
    }

    /**
     * auto → v1 when api_key/token_auth; else legacy /ei + /ewb OAuth API.
     */
    public function usesApiV1(): bool
    {
        $style = strtolower((string) config('services.mastersindia.api_style', 'auto'));

        if ($style === 'v1') {
            return true;
        }

        if ($style === 'legacy') {
            return false;
        }

        return in_array($this->resolveAuthMode(), ['api_key', 'token_auth'], true);
    }

    public function resolvedBaseUrl(): string
    {
        if ((bool) config('services.mastersindia.sandbox_mode', true)) {
            return rtrim((string) (
                config('services.mastersindia.base_url')
                ?: config('services.mastersindia.sandbox_url')
                ?: 'https://sandb-api.mastersindia.co'
            ), '/');
        }

        return rtrim((string) (
            config('services.mastersindia.prod_url')
            ?: config('services.mastersindia.base_url')
            ?: 'https://prod-api.mastersindia.co'
        ), '/');
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
        if (! $eligibility['eligible']) {
            throw new RuntimeException($eligibility['reason'] ?? 'Invoice is not eligible for E-Invoicing.');
        }

        if ($invoice->eInvoice && $invoice->eInvoice->status === 'generated' && filled($invoice->eInvoice->irn)) {
            throw new RuntimeException('E-Invoice has already been generated with IRN: ' . $invoice->eInvoice->irn);
        }

        if (! $this->isConfigured()) {
            if ((bool) config('services.mastersindia.fallback_to_stub')) {
                $stub = $this->stubIrn($invoice, 'Masters India credentials not configured; using deterministic stub.');
                $this->auditLogService->record($invoice, 'einvoice_generated_stub', null, [
                    'irn' => $stub['irn'],
                    'ack_no' => $stub['ack_no'],
                    'status' => 'stub',
                ]);
                return $stub;
            }

            throw new RuntimeException('Government Portal / Masters India GSP credentials are not configured. Please configure MASTERSINDIA_CLIENT_ID, MASTERSINDIA_CLIENT_SECRET, MASTERSINDIA_USERNAME, MASTERSINDIA_PASSWORD, and MASTERSINDIA_GSTIN in your environment.');
        }

        try {
            $payload = $this->usesApiV1() ? $this->buildIrnPayloadV1($invoice) : $this->buildIrnPayload($invoice);
            $endpoint = $this->usesApiV1() ? '/api/v1/einvoice/' : '/ei/api/invoice';
            $response = $this->client()->post($endpoint, $payload);

            if (! $response->ok()) {
                $errBody = $response->json();
                $errMsg = is_array($errBody)
                    ? $this->extractMastersIndiaMessage($errBody, $errBody['results'] ?? $errBody['Results'] ?? null, 'IRN')
                    : (string) $response->body();
                Log::warning('MastersIndia IRN call failed', ['status' => $response->status(), 'body' => $errMsg, 'endpoint' => $endpoint]);

                if ((bool) config('services.mastersindia.fallback_to_stub')) {
                    $stub = $this->stubIrn($invoice, 'Upstream response ' . $response->status() . ': ' . $errMsg);
                    $this->auditLogService->record($invoice, 'einvoice_fallback_stub', null, ['reason' => $errMsg]);
                    return $stub;
                }

                $this->auditLogService->record($invoice, 'einvoice_failed', null, ['error' => $errMsg]);
                throw new RuntimeException($errMsg);
            }

            $body = $response->json();
            $this->assertMastersIndiaSuccess($body, 'IRN');

            $results = is_array($body['results'] ?? null) ? $body['results'] : (is_array($body['Results'] ?? null) ? $body['Results'] : null);
            $data = $body['Data'] ?? $body['data'] ?? $results ?? $body['message'] ?? $body;
            if (is_string($data)) {
                $data = ['Irn' => $data];
            }

            // Masters India v1 nests IRN under results.message (array).
            $message = null;
            foreach ([
                $body['message'] ?? null,
                $body['Message'] ?? null,
                is_array($results) ? ($results['message'] ?? $results['Message'] ?? null) : null,
                is_array($data) ? ($data['message'] ?? $data['Message'] ?? null) : null,
            ] as $candidate) {
                if (is_array($candidate) && (isset($candidate['Irn']) || isset($candidate['irn']))) {
                    $message = $candidate;
                    break;
                }
            }
            if (! is_array($message)) {
                $message = is_array($data) ? $data : [];
            }

            $result = [
                'irn' => (string) ($message['Irn'] ?? $message['irn'] ?? $data['Irn'] ?? $data['irn'] ?? ''),
                'ack_no' => (string) ($message['AckNo'] ?? $message['ack_no'] ?? $data['AckNo'] ?? $data['ack_no'] ?? ''),
                'ack_date' => $message['AckDt'] ?? $message['ack_date'] ?? $data['AckDt'] ?? $data['ack_date'] ?? null,
                'signed_invoice' => $message['SignedInvoice'] ?? $message['signed_invoice'] ?? $data['SignedInvoice'] ?? $data['signed_invoice'] ?? null,
                'signed_qr_base64' => $message['SignedQRCode'] ?? $message['signed_qr_base64'] ?? $data['SignedQRCode'] ?? $data['signed_qr_base64'] ?? null,
                'eway_bill_no' => $message['EwbNo'] ?? $message['ewbNo'] ?? $data['EwbNo'] ?? $data['ewbNo'] ?? null,
                'status' => 'generated',
                'provider' => 'masters_india',
                'raw' => $body,
            ];

            if ($result['irn'] === '') {
                throw new RuntimeException('Portal accepted the request but did not return an IRN.');
            }

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
            if ((bool) config('services.mastersindia.fallback_to_stub')) {
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

            throw new RuntimeException('Government Portal / Masters India GSP credentials are not configured. Please configure MASTERSINDIA_CLIENT_ID, MASTERSINDIA_CLIENT_SECRET, MASTERSINDIA_USERNAME, MASTERSINDIA_PASSWORD, and MASTERSINDIA_GSTIN in your environment.');
        }

        try {
            if ($this->usesApiV1()) {
                $payload = [
                    'user_gstin' => (string) config('services.mastersindia.gstin'),
                    'irn' => $eInvoice->irn,
                    'cancel_reason' => (string) $cancelReason,
                    'cancel_remarks' => substr($remarks, 0, 100),
                    'ewaybill_cancel' => '',
                ];
                $endpoint = '/api/v1/cancel-einvoice/';
            } else {
                $payload = [
                    'Irn' => $eInvoice->irn,
                    'CnlRsn' => $cancelReason,
                    'CnlRem' => substr($remarks, 0, 100),
                ];
                $endpoint = '/ei/api/invoice/cancel';
            }

            $response = $this->client()->post($endpoint, $payload);

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
        // 1. Prevent duplicate generation if already generated
        if ($invoice->isEWayBillGenerated() || ($invoice->eWayBill && in_array($invoice->eWayBill->status, ['generated', 'active'], true) && filled($invoice->eWayBill->eway_bill_no))) {
            $existingNo = $invoice->eWayBill?->eway_bill_no ?: $invoice->eway_bill_no;
            throw new RuntimeException("E-Way Bill has already been generated with number: {$existingNo}. Duplicate generation is not allowed.");
        }

        // 2. Validate all required invoice and transport information
        $validation = $this->complianceService->validateForEWayBillGeneration($invoice, $extra);
        if (! $validation['valid']) {
            $msg = implode(' ', $validation['errors']);
            throw new RuntimeException("Validation Error: {$msg}");
        }

        // 3. Verify API configuration / credentials
        if (! $this->isConfigured()) {
            if ((bool) config('services.mastersindia.fallback_to_stub')) {
                $stub = $this->stubEwayBill($invoice, 'Masters India credentials not configured; using deterministic stub.');
                $this->auditLogService->record($invoice, 'eway_bill_generated_stub', null, [
                    'eway_bill_no' => $stub['eway_bill_no'],
                    'status' => 'stub',
                ]);
                return $stub;
            }

            throw new RuntimeException('Configuration Error: Government Portal / Masters India GSP credentials are not configured in your environment. Please configure MASTERSINDIA_CLIENT_ID, MASTERSINDIA_CLIENT_SECRET, MASTERSINDIA_USERNAME, MASTERSINDIA_PASSWORD, and MASTERSINDIA_GSTIN in your environment.');
        }

        $eInvoice = $invoice->eInvoice;
        $hasActiveIrn = $eInvoice && filled($eInvoice->irn) && $eInvoice->status !== 'cancelled';

        try {
            if ($this->usesApiV1()) {
                $payload = array_merge($this->buildEwbPayloadV1($invoice), $this->mapExtraToV1($extra));
                $endpoint = '/api/v1/ewayBillsGenerate/';
            } elseif ($hasActiveIrn) {
                $payload = array_merge($this->buildEwbByIrnPayload($invoice), $extra);
                $endpoint = '/ei/api/ewayapi';
            } else {
                $payload = array_merge($this->buildDirectEwbPayload($invoice), $extra);
                $endpoint = '/ewb/api/ewaybill';
            }

            try {
                $response = $this->client()->post($endpoint, $payload);
            } catch (\Illuminate\Http\Client\ConnectionException $connEx) {
                $errMsg = 'API Connectivity Error: Unable to connect to Government Portal / Masters India API (' . $connEx->getMessage() . ').';
                Log::error('MastersIndia EWB connection failed', ['endpoint' => $endpoint, 'error' => $connEx->getMessage()]);
                $this->auditLogService->record($invoice, 'eway_bill_failed', null, ['error' => $errMsg]);
                throw new RuntimeException($errMsg);
            } catch (\Throwable $reqEx) {
                if (str_starts_with($reqEx->getMessage(), 'Authentication Error:') || str_starts_with($reqEx->getMessage(), 'API Connectivity Error:')) {
                    $this->auditLogService->record($invoice, 'eway_bill_failed', null, ['error' => $reqEx->getMessage()]);
                    throw $reqEx;
                }
                $errMsg = 'API Connectivity Error: ' . $reqEx->getMessage();
                $this->auditLogService->record($invoice, 'eway_bill_failed', null, ['error' => $errMsg]);
                throw new RuntimeException($errMsg);
            }

            if (! $response->ok()) {
                $errMsg = $this->extractErrorMessage($response, 'Government Portal Rejection');
                if ($response->status() === 401 || $response->status() === 403) {
                    $errMsg = 'Authentication Error: Invalid or expired API credentials / access token.';
                }
                Log::warning('MastersIndia EWB call failed', ['endpoint' => $endpoint, 'status' => $response->status(), 'body' => $errMsg]);
                $this->auditLogService->record($invoice, 'eway_bill_failed', null, ['error' => $errMsg]);
                throw new RuntimeException($errMsg);
            }

            $body = $response->json();
            $this->assertMastersIndiaSuccess($body, 'E-Way Bill');

            $results = is_array($body['results'] ?? null) ? $body['results'] : (is_array($body['Results'] ?? null) ? $body['Results'] : null);
            $message = null;
            foreach ([
                $body['message'] ?? null,
                $body['Message'] ?? null,
                is_array($results) ? ($results['message'] ?? $results['Message'] ?? null) : null,
            ] as $candidate) {
                if (is_array($candidate) && (
                    isset($candidate['ewayBillNo']) || isset($candidate['EwbNo']) || isset($candidate['ewbNo']) || isset($candidate['eway_bill_no'])
                )) {
                    $message = $candidate;
                    break;
                }
            }

            $data = $message
                ?? $body['Data']
                ?? $body['data']
                ?? (is_array($results) ? $results : null)
                ?? (is_array($body) ? $body : []);

            $ewbNo = (string) ($data['ewayBillNo'] ?? $data['EwbNo'] ?? $data['ewbNo'] ?? $data['eway_bill_no'] ?? $data['EwayBillNo'] ?? $body['EwbNo'] ?? $body['eway_bill_no'] ?? '');

            if ($ewbNo === '') {
                $errMsg = $this->extractMastersIndiaMessage(
                    is_array($body) ? $body : [],
                    is_array($results) ? $results : null,
                    'E-Way Bill'
                );
                $this->auditLogService->record($invoice, 'eway_bill_failed', null, ['error' => $errMsg]);
                throw new RuntimeException($errMsg !== '' ? $errMsg : 'Portal did not return an E-Way Bill number.');
            }

            $ewbDate = $data['ewayBillDate'] ?? $data['EwbDt'] ?? $data['ewbDt'] ?? $data['ewb_date'] ?? $data['eway_bill_date'] ?? Carbon::now()->toIso8601String();
            $validUpto = $data['validUpto'] ?? $data['EwbValidTill'] ?? $data['ewbValidTill'] ?? $data['valid_upto'] ?? $data['valid_until'] ?? null;

            $result = [
                'eway_bill_no' => $ewbNo,
                'ewb_date' => $ewbDate,
                'valid_upto' => $validUpto,
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
            if ((bool) config('services.mastersindia.fallback_to_stub')) {
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

            throw new RuntimeException('Government Portal / Masters India GSP credentials are not configured. Please configure MASTERSINDIA_CLIENT_ID, MASTERSINDIA_CLIENT_SECRET, MASTERSINDIA_USERNAME, MASTERSINDIA_PASSWORD, and MASTERSINDIA_GSTIN in your environment.');
        }

        try {
            if ($this->usesApiV1()) {
                $payload = [
                    'userGstin' => (string) config('services.mastersindia.gstin'),
                    'eway_bill_number' => $eWayBill->eway_bill_no,
                    'cancel_reason' => (string) $cancelReason,
                    'cancel_remarks' => substr($remarks, 0, 100),
                    'data_source' => 'erp',
                ];
                $endpoint = '/api/v1/ewayBillCancel/';
            } else {
                $payload = [
                    'ewbNo' => $eWayBill->eway_bill_no,
                    'cancelRsnCode' => (int) $cancelReason,
                    'cancelRmrk' => substr($remarks, 0, 100),
                ];
                $endpoint = '/ewb/api/ewaybill/cancel';
            }

            $response = $this->client()->post($endpoint, $payload);

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
        $this->baseUrl = $this->resolvedBaseUrl();
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        $mode = $this->resolveAuthMode();

        if ($mode === 'api_key') {
            $headers['api_key'] = (string) config('services.mastersindia.api_key');
        } elseif ($mode === 'token_auth') {
            $headers['Authorization'] = 'JWT '.$this->jwtAccessToken();
        } else {
            $token = $this->oauthAccessToken();
            $headers['Authorization'] = 'Bearer '.$token;
            $headers['client_id'] = (string) config('services.mastersindia.client_id');
            $headers['client_secret'] = (string) config('services.mastersindia.client_secret');
            $headers['gstin'] = (string) config('services.mastersindia.gstin');
        }

        if (filled(config('services.mastersindia.gstin'))) {
            $headers['gstin'] = $headers['gstin'] ?? (string) config('services.mastersindia.gstin');
        }

        return Http::baseUrl($this->baseUrl)
            ->timeout((int) config('services.mastersindia.timeout', 45))
            ->withHeaders($headers);
    }

    protected function jwtAccessToken(): string
    {
        $user = (string) config('services.mastersindia.token_auth_username');
        $key = 'mastersindia:jwt:'.md5($user);

        return Cache::remember($key, now()->addMinutes(50), function () {
            try {
                $resp = Http::baseUrl($this->resolvedBaseUrl())
                    ->timeout((int) config('services.mastersindia.timeout', 45))
                    ->asJson()
                    ->post('/api/v1/token-auth/', [
                        'username' => config('services.mastersindia.token_auth_username'),
                        'password' => config('services.mastersindia.token_auth_password'),
                    ]);
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                throw new RuntimeException('API Connectivity Error: Unable to connect to Masters India token-auth: '.$e->getMessage());
            }

            if (! $resp->ok()) {
                $body = $resp->json();
                $err = is_array($body)
                    ? ($body['error_description'] ?? $body['message'] ?? $body['error'] ?? $resp->body())
                    : $resp->body();
                throw new RuntimeException('Authentication Error: '.$err);
            }

            $token = (string) ($resp->json('token') ?? $resp->json('access_token') ?? $resp->json('data.token') ?? $resp->json('data.access_token') ?? '');
            if ($token === '') {
                throw new RuntimeException('Authentication Error: JWT token was not returned in response.');
            }

            return $token;
        });
    }

    protected function oauthAccessToken(): string
    {
        $clientId = (string) config('services.mastersindia.client_id');
        $key = 'mastersindia:token:'.md5($clientId);

        return Cache::remember($key, now()->addMinutes(50), function () {
            try {
                $resp = Http::baseUrl($this->resolvedBaseUrl())
                    ->timeout((int) config('services.mastersindia.timeout', 45))
                    ->asJson()
                    ->post('/oauth/token', [
                        'username' => config('services.mastersindia.username'),
                        'password' => config('services.mastersindia.password'),
                        'client_id' => config('services.mastersindia.client_id'),
                        'client_secret' => config('services.mastersindia.client_secret'),
                        'grant_type' => 'password',
                    ]);
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                throw new RuntimeException('API Connectivity Error: Unable to connect to Masters India authentication service: '.$e->getMessage());
            }

            if (! $resp->ok()) {
                $body = $resp->json();
                $err = is_array($body)
                    ? ($body['error_description'] ?? $body['message'] ?? $body['error'] ?? $resp->body())
                    : $resp->body();
                throw new RuntimeException('Authentication Error: '.$err);
            }

            $token = (string) ($resp->json('access_token') ?? $resp->json('data.access_token') ?? '');
            if ($token === '') {
                throw new RuntimeException('Authentication Error: Access token was not returned in response.');
            }

            return $token;
        });
    }

    /**
     * @param  array<string, mixed>|null  $body
     */
    protected function assertMastersIndiaSuccess(?array $body, string $context): void
    {
        if (! is_array($body)) {
            return;
        }

        // Masters India returns Results/Message (PascalCase) or results/message (snake)
        $results = $body['results'] ?? $body['Results'] ?? null;
        if (is_array($results)) {
            $status = strtolower((string) ($results['status'] ?? $results['Status'] ?? ''));
            $code = (int) ($results['code'] ?? $results['Code'] ?? 0);
            $ok = $status === 'success' || $code === 200;

            if (! $ok && ($status !== '' || $code > 0)) {
                $msg = $this->extractMastersIndiaMessage($body, $results, $context);
                throw new RuntimeException($msg);
            }
        }

        if (isset($body['status_cd']) && (string) $body['status_cd'] === '0') {
            $msg = $body['status_desc'] ?? $body['StatusDesc'] ?? $this->extractMastersIndiaMessage($body, null, $context);
            throw new RuntimeException($this->humanizePortalError((string) $msg, $context));
        }
    }

    /**
     * @param  array<string, mixed>  $body
     * @param  array<string, mixed>|null  $results
     */
    protected function extractMastersIndiaMessage(array $body, ?array $results, string $context): string
    {
        $candidates = [
            $results['Errormessage'] ?? null,
            $results['errorMessage'] ?? null,
            $results['error_message'] ?? null,
            $results['Message'] ?? null,
            $results['message'] ?? null,
            is_string($body['Errormessage'] ?? null) ? $body['Errormessage'] : null,
            is_string($body['message'] ?? null) ? $body['message'] : null,
            is_string($body['Message'] ?? null) ? $body['Message'] : null,
            is_array($body['message'] ?? null) ? ($body['message']['Errormessage'] ?? $body['message']['errorMessage'] ?? json_encode($body['message'])) : null,
            is_array($body['Message'] ?? null) ? ($body['Message']['Errormessage'] ?? $body['Message']['errorMessage'] ?? json_encode($body['Message'])) : null,
            $body['error'] ?? null,
            $body['Error'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_array($candidate)) {
                $candidate = $candidate['Errormessage']
                    ?? $candidate['errorMessage']
                    ?? $candidate['message']
                    ?? $candidate['Message']
                    ?? json_encode($candidate);
            }
            $text = trim((string) $candidate);
            if ($text !== '' && strtolower($text) !== 'null' && strtolower($text) !== 'success') {
                return $this->humanizePortalError($text, $context);
            }
        }

        if (is_array($results) && $results !== []) {
            return $this->humanizePortalError(json_encode($results) ?: '', $context);
        }

        return 'Portal rejected '.$context.'.';
    }

    /**
     * Extract human-readable error message from an HTTP error response.
     */
    protected function extractErrorMessage($response, string $defaultPrefix = 'Government Portal Rejection'): string
    {
        $body = $response->json();
        if (is_array($body)) {
            $results = $body['results'] ?? $body['Results'] ?? null;
            if (is_array($results)) {
                return $this->extractMastersIndiaMessage($body, $results, 'request');
            }
            if (! empty($body['errors']) && is_array($body['errors'])) {
                $flattened = collect($body['errors'])->flatten()->join(' ');
                return $this->humanizePortalError($flattened, 'request');
            }
            if (! empty($body['error'])) {
                $err = is_array($body['error']) ? ($body['error']['message'] ?? json_encode($body['error'])) : (string) $body['error'];
                return $this->humanizePortalError((string) $err, 'request');
            }
            if (! empty($body['message'])) {
                $msg = is_array($body['message'])
                    ? ($body['message']['Errormessage'] ?? $body['message']['errorMessage'] ?? json_encode($body['message']))
                    : (string) $body['message'];
                return $this->humanizePortalError((string) $msg, 'request');
            }
            if (! empty($body['status_desc'])) {
                return $this->humanizePortalError((string) $body['status_desc'], 'request');
            }
        }

        $raw = trim($response->body());
        return $this->humanizePortalError($raw !== '' ? $raw : ('HTTP '.$response->status()), 'request');
    }

    /**
     * Turn NIC / Masters India rejection text into short plain-English bullets.
     */
    protected function humanizePortalError(string $raw, string $context = 'request'): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return 'Portal rejected '.$context.'.';
        }

        // If whole payload was JSON-encoded, unwrap Errormessage / message.
        if (str_starts_with($raw, '{') || str_starts_with($raw, '[')) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $nested = $decoded['Errormessage']
                    ?? $decoded['errorMessage']
                    ?? $decoded['error_message']
                    ?? $decoded['Message']
                    ?? $decoded['message']
                    ?? null;
                if (is_string($nested) && trim($nested) !== '') {
                    $raw = $nested;
                } elseif (is_array($nested)) {
                    $raw = (string) ($nested['Errormessage'] ?? $nested['errorMessage'] ?? json_encode($nested));
                }
            }
        }

        $parts = preg_split('/\s*\|\|\s*/', $raw) ?: [$raw];
        $bullets = [];

        foreach ($parts as $part) {
            $part = trim((string) $part);
            if ($part === '') {
                continue;
            }

            // Strip leading NIC code like "3028: "
            $text = preg_replace('/^\d{3,5}\s*:\s*/', '', $part) ?? $part;
            $text = trim($text);
            $lower = strtolower($text);

            if (str_contains($lower, 'gstin') && str_contains($lower, 'invalid')) {
                if (preg_match('/GSTIN\s*-?\s*([0-9A-Z]{15})/i', $text, $m)) {
                    $bullets[] = 'GSTIN '.$m[1].' is invalid for sandbox.';
                } else {
                    $bullets[] = 'One of the GSTINs is invalid for sandbox.';
                }
                continue;
            }

            if (str_contains($lower, 'pincode') && (str_contains($lower, 'does not belong') || str_contains($lower, 'not belong'))) {
                if (preg_match('/Pincode\s*-?\s*(\d+).*State\s*-?\s*(\d+)/i', $text, $m)) {
                    $bullets[] = 'Pincode '.$m[1].' does not match state '.$m[2].'.';
                } else {
                    $bullets[] = 'Pincode does not match the GSTIN state.';
                }
                continue;
            }

            if (str_contains($lower, 'e-commerce') || str_contains($lower, 'ecommerce')) {
                $bullets[] = 'E-commerce GSTIN is missing or not required — check invoice type.';
                continue;
            }

            if (str_contains($lower, 'supplier gstin') && (str_contains($lower, 'regular') || str_contains($lower, 'casual') || str_contains($lower, 'sed'))) {
                $bullets[] = 'Seller GSTIN must be a regular taxable person (use sandbox seller GSTIN).';
                continue;
            }

            if (str_contains($lower, 'distance') && str_contains($lower, 'too high')) {
                $bullets[] = 'Transport distance is too high for these pincodes — leave distance blank for auto.';
                continue;
            }

            if (str_contains($lower, 'duplicate') || str_contains($lower, 'already generated')) {
                $bullets[] = 'Document already exists on the portal.';
                continue;
            }

            if (str_contains($lower, 'document_number') || str_contains($lower, 'document number')) {
                if (str_contains($lower, '16') || str_contains($lower, 'at most')) {
                    $bullets[] = 'Invoice number is too long (max 16 characters).';
                    continue;
                }
            }

            // Fallback: shorten noisy text
            $short = preg_replace('/\s+/', ' ', $text) ?? $text;
            if (strlen($short) > 120) {
                $short = substr($short, 0, 117).'...';
            }
            $bullets[] = rtrim($short, '.').'.';
        }

        $bullets = array_values(array_unique($bullets));
        if ($bullets === []) {
            return 'Portal rejected '.$context.'.';
        }

        // Keep flash messages short: max 3 bullets
        $bullets = array_slice($bullets, 0, 3);

        return implode(' ', $bullets);
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

        $sellerState = (string) ($company?->state ?: '');
        $sellerPin = (int) ($company?->pincode ?: 400001);
        $sellerCity = (string) ($company?->city ?: ($company?->state ?: 'City'));
        $sellerAddr = (string) ($company?->address ?: 'Head Office Address');

        // Sandbox IRP only accepts Masters India test GSTINs + matching state/pincode.
        if ((bool) config('services.mastersindia.sandbox_mode', true)) {
            $sandboxSeller = strtoupper(trim((string) config('services.mastersindia.gstin', '')));
            if ($sandboxSeller !== '' && strtoupper($sellerGstin) !== $sandboxSeller) {
                $sellerGstin = $sandboxSeller;
            }
            $sellerStateCodeHint = substr($sellerGstin, 0, 2);
            if ($sellerStateCodeHint === '05') {
                $sellerState = 'Uttarakhand';
                $sellerPin = 248001;
                $sellerCity = 'Dehradun';
                $sellerAddr = 'Sandbox Seller Address';
            }
        }

        $sellerStateCode = $this->complianceService->resolveStateCode($sellerState, $sellerGstin);
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
                'PrdDesc' => substr((string) ($item->product?->name ?? $item->description ?? $item->item_name ?? 'Item'), 0, 100),
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

        $payload = [
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
                'Addr1' => substr($sellerAddr, 0, 100),
                'Loc' => substr($sellerCity, 0, 50),
                'Pin' => $sellerPin,
                'Stcd' => $sellerStateCode,
            ],
            'BuyerDtls' => [
                'Gstin' => $buyerGstin,
                'LglNm' => substr((string) ($customer?->name ?: 'Customer'), 0, 100),
                'Pos' => $buyerStateCode,
                'Addr1' => substr((string) ($invoice->billing_address ?: ($customer?->address ?: 'Customer Address')), 0, 100),
                'Loc' => substr((string) ($invoice->bill_to_place ?: ($invoice->destination ?: ($customer?->city ?: ($customer?->state ?: 'City')))), 0, 50),
                'Pin' => (int) ($invoice->billingAddress?->pincode ?: ($customer?->pincode ?: 400001)),
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

        if (filled($invoice->shipping_address) || filled($invoice->ship_to_place)) {
            // Prefer ship-to / buyer GSTIN for IRN ShipDtls. Consignor/consignee E-Way GSTINs
            // can be different sandbox entities and must not poison e-invoice ship state/pin.
            $shipGstin = (string) ($invoice->shippingAddress?->gstin ?: $buyerGstin);
            if ($shipGstin === '' || strtoupper($shipGstin) === 'URP') {
                $shipGstin = $buyerGstin;
            }
            $shipStateCode = $this->complianceService->resolveStateCode(
                $invoice->delivery_state ?: ($invoice->shippingAddress?->state ?: ($customer?->state ?: '')),
                $shipGstin
            );

            $payload['ShipDtls'] = [
                'Gstin' => $shipGstin,
                'LglNm' => substr((string) ($invoice->consignee_name ?: ($customer?->name ?: 'Consignee')), 0, 100),
                'Addr1' => substr((string) ($invoice->shipping_address ?: ($customer?->address ?: 'Delivery Address')), 0, 100),
                'Loc' => substr((string) ($invoice->ship_to_place ?: ($invoice->destination ?: ($customer?->city ?: ($customer?->state ?: 'City')))), 0, 50),
                'Pin' => (int) ($invoice->shippingAddress?->pincode ?: ($customer?->pincode ?: 400001)),
                'Stcd' => $shipStateCode,
            ];
        }

        return $payload;
    }

    /**
     * Masters India /api/v1/einvoice/ snake_case payload (BharatBill-compatible).
     *
     * @return array<string, mixed>
     */
    protected function buildIrnPayloadV1(Invoice $invoice): array
    {
        $legacy = $this->buildIrnPayload($invoice);
        $seller = $legacy['SellerDtls'];
        $buyer = $legacy['BuyerDtls'];
        $val = $legacy['ValDtls'];
        $doc = $legacy['DocDtls'];
        $tran = $legacy['TranDtls'];

        $items = collect($legacy['ItemList'])->map(function (array $item) {
            return [
                'item_serial_number' => (string) $item['SlNo'],
                'product_description' => $item['PrdDesc'],
                'is_service' => $item['IsServc'],
                'hsn_code' => (string) $item['HsnCd'],
                'quantity' => $item['Qty'],
                'unit' => $item['Unit'],
                'unit_price' => $item['UnitPrice'],
                'total_amount' => $item['TotAmt'],
                'discount' => $item['Discount'],
                'assessable_value' => $item['AssAmt'],
                'gst_rate' => $item['GstRt'],
                'igst_amount' => $item['IgstAmt'],
                'cgst_amount' => $item['CgstAmt'],
                'sgst_amount' => $item['SgstAmt'],
                'total_item_value' => $item['TotItemVal'],
            ];
        })->values()->all();

        // E-invoice portal user/pass — fall back to token-auth when same credentials are used.
        $einvUser = trim((string) (
            config('services.mastersindia.username')
            ?: config('services.mastersindia.token_auth_username')
            ?: ''
        ));
        $einvPass = (string) (
            config('services.mastersindia.password')
            ?: config('services.mastersindia.token_auth_password')
            ?: ''
        );

        $payload = [
            'user_gstin' => $seller['Gstin'],
            'data_source' => 'erp',
            'username' => $einvUser,
            'password' => $einvPass,
            'transaction_details' => [
                'supply_type' => $tran['SupTyp'],
                'charge_type' => 'N',
                'igst_on_intra' => 'N',
            ],
            'document_details' => [
                'document_type' => $doc['Typ'],
                'document_number' => $doc['No'],
                'document_date' => $doc['Dt'],
            ],
            'seller_details' => [
                'gstin' => $seller['Gstin'],
                'legal_name' => $seller['LglNm'],
                'address1' => $seller['Addr1'],
                'location' => $seller['Loc'],
                'pincode' => $seller['Pin'],
                'state_code' => $seller['Stcd'],
            ],
            'buyer_details' => [
                'gstin' => $buyer['Gstin'],
                'legal_name' => $buyer['LglNm'],
                'place_of_supply' => $buyer['Pos'],
                'address1' => $buyer['Addr1'],
                'location' => $buyer['Loc'],
                'pincode' => $buyer['Pin'],
                'state_code' => $buyer['Stcd'],
            ],
            'value_details' => [
                'total_assessable_value' => $val['AssVal'],
                'total_igst_value' => $val['IgstVal'],
                'total_cgst_value' => $val['CgstVal'],
                'total_sgst_value' => $val['SgstVal'],
                'total_invoice_value' => $val['TotInvVal'],
            ],
            'item_list' => $items,
        ];

        // dispatch = FROM (seller), ship = TO (consignee). Never map ship-to into dispatch.
        $payload['dispatch_details'] = [
            'company_name' => $seller['LglNm'],
            'address1' => $seller['Addr1'],
            'location' => $seller['Loc'],
            'pincode' => $seller['Pin'],
            'state_code' => $seller['Stcd'],
        ];

        if (isset($legacy['ShipDtls'])) {
            $ship = $legacy['ShipDtls'];
            $payload['ship_details'] = [
                'gstin' => $ship['Gstin'],
                'legal_name' => $ship['LglNm'],
                'address1' => $ship['Addr1'],
                'location' => $ship['Loc'],
                'pincode' => $ship['Pin'],
                'state_code' => $ship['Stcd'],
            ];
        }

        return $payload;
    }

    /**
     * Build E-Way Bill payload generated against an IRN.
     */
    protected function buildEwbByIrnPayload(Invoice $invoice): array
    {
        $eInv = $invoice->eInvoice;

        $docNo = $invoice->lr_rr_no ?: ($invoice->dispatch_doc_no ?: ($invoice->reference_no ?: $invoice->invoice_no));
        $docDate = $invoice->lr_rr_date ? $invoice->lr_rr_date->format('d/m/Y') : $invoice->invoice_date->format('d/m/Y');

        $rawMode = strtolower(trim((string) ($invoice->transport_mode ?? '1')));
        $portalTransMode = match (true) {
            str_contains($rawMode, '2') || str_contains($rawMode, 'rail') => '2',
            str_contains($rawMode, '3') || str_contains($rawMode, 'air') => '3',
            str_contains($rawMode, '4') || str_contains($rawMode, 'ship') => '4',
            default => '1',
        };

        $cleanVehNo = $invoice->vehicle_no ? strtoupper(str_replace([' ', '-'], '', trim($invoice->vehicle_no))) : null;

        return [
            'Irn' => $eInv?->irn,
            'Distance' => $this->resolveDistanceKm($invoice),
            'TransMode' => $portalTransMode,
            'TransId' => $invoice->transporter_id ?: null,
            'TransName' => $invoice->transporter_name ?: null,
            'TransDocNo' => $docNo,
            'TransDocDt' => $docDate,
            'VehNo' => $cleanVehNo,
            'VehType' => $invoice->vehicle_type ?: 'R',
        ];
    }

    /**
     * Build direct E-Way Bill payload (for non-IRN invoices).
     */
    protected function buildDirectEwbPayload(Invoice $invoice): array
    {
        $company = $invoice->selling_company
            ?? Company::query()->find($invoice->company_id)
            ?? Company::resolveCurrent()
            ?? Company::first();

        $customer = $invoice->customer;
        $sellerGstin = (string) ($company?->gstin ?: ($invoice->consignor_gstin ?: config('services.mastersindia.gstin')));
        $rawBuyerGstin = (string) ($invoice->billingAddress?->gstin ?: ($invoice->consignee_gstin ?: ($customer?->gstin ?: '')));
        $buyerGstin = trim($rawBuyerGstin) !== '' ? strtoupper(trim($rawBuyerGstin)) : 'URP';

        $fromStateCode = (int) $this->complianceService->resolveStateCode($invoice->consignor_state ?: $company?->state, $sellerGstin);
        $toStateCode = (int) $this->complianceService->resolveStateCode(
            $invoice->delivery_state ?: ($invoice->shippingAddress?->state ?: ($customer?->state ?: '')),
            $buyerGstin
        );

        $docNo = $invoice->lr_rr_no ?: ($invoice->dispatch_doc_no ?: $invoice->invoice_no);
        $docDate = $invoice->lr_rr_date ? $invoice->lr_rr_date->format('d/m/Y') : $invoice->invoice_date->format('d/m/Y');

        $rawMode = strtolower(trim((string) ($invoice->transport_mode ?? '1')));
        $portalTransMode = match (true) {
            str_contains($rawMode, '2') || str_contains($rawMode, 'rail') => '2',
            str_contains($rawMode, '3') || str_contains($rawMode, 'air') => '3',
            str_contains($rawMode, '4') || str_contains($rawMode, 'ship') => '4',
            default => '1',
        };

        $isIntra = $invoice->tax_type === 'intra' || ($invoice->tax_type === null && ($invoice->cgst_amount > 0 || $invoice->sgst_amount > 0));

        $itemList = $invoice->items->map(function ($item, $i) use ($isIntra) {
            $taxable = (float) ($item->taxable_amount ?? round((float) $item->line_total - (float) $item->tax_amount, 2));
            $taxRate = (float) ($item->tax_rate ?? ($item->product?->tax_rate ?? 0));
            $cgstRate = $isIntra ? ($taxRate / 2) : 0.0;
            $sgstRate = $isIntra ? ($taxRate / 2) : 0.0;
            $igstRate = ! $isIntra ? $taxRate : 0.0;

            $uomCode = strtoupper((string) ($item->uom?->code ?: ($item->uom?->name ?: 'PCS')));
            if ($uomCode === 'PIECES' || $uomCode === 'PIECE') $uomCode = 'PCS';

            $hsn = preg_replace('/\D/', '', (string) ($item->hsn_code ?: ($item->product?->hsn_code ?: '85176200')));

            return [
                'itemNo' => $i + 1,
                'productId' => (int) ($item->product_id ?: ($i + 1)),
                'productName' => substr((string) ($item->product?->name ?: $item->description ?: $item->item_name ?: 'Product'), 0, 100),
                'productDesc' => substr((string) ($item->product?->description ?: ($item->description ?: ($item->product?->name ?: 'Item'))), 0, 100),
                'hsnCode' => (int) ($hsn ?: 85176200),
                'quantity' => (float) $item->quantity,
                'qtyUnit' => $uomCode,
                'cgstRate' => $cgstRate,
                'sgstRate' => $sgstRate,
                'igstRate' => $igstRate,
                'cessRate' => 0.0,
                'taxableAmount' => $taxable,
            ];
        })->values()->all();

        $fromPin = (int) preg_replace('/\D/', '', (string) ($invoice->consignor_pincode ?: ($company?->pincode ?: 400001)));
        $toPin = (int) preg_replace('/\D/', '', (string) ($invoice->shippingAddress?->pincode ?: ($invoice->consignee_pincode ?: ($customer?->pincode ?: 400001))));

        $cleanVehNo = $invoice->vehicle_no ? strtoupper(str_replace([' ', '-'], '', trim($invoice->vehicle_no))) : null;

        return [
            'supplyType' => 'O',
            'subSupplyType' => '1',
            'docType' => 'INV',
            'docNo' => $invoice->invoice_no,
            'docDate' => $invoice->invoice_date->format('d/m/Y'),
            'fromGstin' => $sellerGstin,
            'fromTrdName' => $company?->name ?: 'Seller',
            'fromAddr1' => substr((string) ($invoice->consignor_address_line_1 ?: ($company?->address ?: 'Dispatch Office')), 0, 100),
            'fromAddr2' => substr((string) ($invoice->consignor_address_line_2 ?: ''), 0, 100),
            'fromPlace' => substr((string) ($invoice->consignor_city ?: ($company?->city ?: ($company?->state ?: 'City'))), 0, 50),
            'fromPincode' => $fromPin,
            'fromStateCode' => $fromStateCode,
            'actualFromStateCode' => $fromStateCode,
            'toGstin' => $buyerGstin,
            'toTrdName' => substr((string) ($invoice->consignee_name ?: ($customer?->name ?: 'Customer')), 0, 100),
            'toAddr1' => substr((string) ($invoice->shipping_address ?: ($invoice->consignee_address_line_1 ?: ($customer?->address ?: 'Delivery Address'))), 0, 100),
            'toAddr2' => substr((string) ($invoice->consignee_address_line_2 ?: ''), 0, 100),
            'toPlace' => substr((string) ($invoice->ship_to_place ?: ($invoice->consignee_city ?: ($invoice->destination ?: ($customer?->city ?: ($customer?->state ?: 'City'))))), 0, 50),
            'toPincode' => $toPin,
            'toStateCode' => $toStateCode,
            'actualToStateCode' => $toStateCode,
            'totalValue' => (float) ($invoice->taxable_amount ?? $invoice->subtotal),
            'cgstValue' => (float) $invoice->cgst_amount,
            'sgstValue' => (float) $invoice->sgst_amount,
            'igstValue' => (float) $invoice->igst_amount,
            'cessValue' => 0.0,
            'totInvValue' => (float) $invoice->grand_total,
            'transMode' => $portalTransMode,
            'transDistance' => $this->resolveDistanceKm($invoice),
            'transporterId' => $invoice->transporter_id ?: null,
            'transporterName' => $invoice->transporter_name ?: null,
            'transDocNo' => $docNo,
            'transDocDate' => $docDate,
            'vehicleNo' => $cleanVehNo,
            'vehicleType' => $invoice->vehicle_type ?: 'R',
            'itemList' => $itemList,
        ];
    }

    /**
     * Masters India /api/v1/ewayBillsGenerate/ payload (guide §10.2).
     *
     * @return array<string, mixed>
     */
    protected function buildEwbPayloadV1(Invoice $invoice): array
    {
        $company = $invoice->selling_company
            ?? Company::query()->find($invoice->company_id)
            ?? Company::resolveCurrent()
            ?? Company::first();

        $customer = $invoice->customer;
        $sellerGstin = strtoupper(trim((string) (
            $invoice->consignor_gstin
            ?: ($company?->gstin ?: config('services.mastersindia.gstin'))
        )));
        $buyerGstin = strtoupper(trim((string) ($invoice->consignee_gstin ?: ($invoice->billingAddress?->gstin ?: ($customer?->gstin ?: ''))))) ?: 'URP';

        $fromState = strtoupper(trim((string) ($invoice->consignor_state ?: ($company?->state ?: ''))));
        $toState = strtoupper(trim((string) ($invoice->consignee_state ?: ($invoice->delivery_state ?: ($invoice->place_of_supply_state ?: ($customer?->state ?: ''))))));
        $fromPin = (int) preg_replace('/\D/', '', (string) ($invoice->consignor_pincode ?: ($company?->pincode ?: 400001)));
        $toPin = (int) preg_replace('/\D/', '', (string) ($invoice->consignee_pincode ?: ($invoice->shippingAddress?->pincode ?: ($customer?->pincode ?: 400001))));

        if ((bool) config('services.mastersindia.sandbox_mode', true)) {
            $sandboxSeller = strtoupper(trim((string) config('services.mastersindia.gstin', '')));
            // Prefer explicit consignor; otherwise sandbox seller GSTIN.
            if ($sandboxSeller !== '' && ! filled($invoice->consignor_gstin) && $sellerGstin !== $sandboxSeller) {
                $sellerGstin = $sandboxSeller;
            }
            if (str_starts_with($sellerGstin, '05') && ($fromPin < 248000 || $fromPin > 249999)) {
                $fromPin = 248001;
                $fromState = 'UTTARAKHAND';
            }
            if (str_starts_with($buyerGstin, '05') && ($toPin < 248000 || $toPin > 249999)) {
                $toPin = 248001;
                $toState = 'UTTARAKHAND';
            }
        }

        $rawMode = strtolower(trim((string) ($invoice->transport_mode ?? '1')));
        $modeLabel = match (true) {
            str_contains($rawMode, '2') || str_contains($rawMode, 'rail') => 'rail',
            str_contains($rawMode, '3') || str_contains($rawMode, 'air') => 'air',
            str_contains($rawMode, '4') || str_contains($rawMode, 'ship') => 'ship',
            default => 'road',
        };

        $isIntra = $invoice->tax_type === 'intra' || ($invoice->tax_type === null && ($invoice->cgst_amount > 0 || $invoice->sgst_amount > 0));
        $cleanVehNo = $invoice->vehicle_no ? strtoupper(str_replace([' ', '-'], '', trim($invoice->vehicle_no))) : '';

        $itemList = $invoice->items->map(function ($item) use ($isIntra) {
            $taxable = (float) ($item->taxable_amount ?? round((float) $item->line_total - (float) $item->tax_amount, 2));
            $taxRate = (float) ($item->tax_rate ?? ($item->product?->tax_rate ?? 0));
            $uomCode = strtoupper((string) ($item->uom?->code ?: 'NOS'));
            if ($uomCode === 'PIECES' || $uomCode === 'PIECE') {
                $uomCode = 'PCS';
            }
            $hsn = preg_replace('/\D/', '', (string) ($item->hsn_code ?: ($item->product?->hsn_code ?: '85176200')));

            return [
                'product_name' => substr((string) ($item->product?->name ?: $item->description ?: 'Product'), 0, 100),
                'hsn_code' => (string) ($hsn ?: '85176200'),
                'quantity' => (float) $item->quantity,
                'unit_of_product' => $uomCode,
                'cgst_rate' => $isIntra ? ($taxRate / 2) : 0.0,
                'sgst_rate' => $isIntra ? ($taxRate / 2) : 0.0,
                'igst_rate' => ! $isIntra ? $taxRate : 0.0,
                'taxable_amount' => $taxable,
            ];
        })->values()->all();

        return [
            'userGstin' => $sellerGstin,
            'supply_type' => 'outward',
            'sub_supply_type' => 'Supply',
            'document_type' => 'Tax Invoice',
            'document_number' => $invoice->invoice_no,
            'document_date' => $invoice->invoice_date->format('d/m/Y'),
            'gstin_of_consignor' => $sellerGstin,
            'legal_name_of_consignor' => substr((string) ($company?->name ?: 'Seller'), 0, 100),
            'address1_of_consignor' => substr((string) ($invoice->consignor_address_line_1 ?: ($company?->address ?: 'Dispatch Office')), 0, 100),
            'place_of_consignor' => substr((string) ($invoice->consignor_city ?: ($company?->city ?: 'City')), 0, 50),
            'pincode_of_consignor' => $fromPin,
            'state_of_consignor' => $fromState,
            'actual_from_state_name' => $fromState,
            'gstin_of_consignee' => $buyerGstin,
            'legal_name_of_consignee' => substr((string) ($invoice->consignee_name ?: ($customer?->name ?: 'Customer')), 0, 100),
            'address1_of_consignee' => substr((string) ($invoice->shipping_address ?: ($customer?->address ?: 'Delivery Address')), 0, 100),
            'place_of_consignee' => substr((string) ($invoice->ship_to_place ?: ($invoice->consignee_city ?: ($customer?->city ?: 'City'))), 0, 50),
            'pincode_of_consignee' => $toPin,
            'state_of_consignee' => $toState,
            'actual_to_state_name' => $toState,
            'state_of_supply' => $toState,
            'transportation_mode' => $modeLabel,
            'transportation_distance' => (string) $this->resolveDistanceKm($invoice),
            'vehicle_number' => $cleanVehNo,
            'vehicle_type' => ($invoice->vehicle_type ?: 'R') === 'O' ? 'Over Dimensional Cargo' : 'Regular',
            'transporter_id' => $invoice->transporter_id ?: '',
            'transporter_name' => $invoice->transporter_name ?: '',
            'transaction_type' => 1,
            'generate_status' => 1,
            'data_source' => 'erp',
            'user_ref' => 'invoice-'.$invoice->id.'-'.$invoice->invoice_no,
            'itemList' => $itemList,
        ];
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected function mapExtraToV1(array $extra): array
    {
        $mapped = [];
        if (isset($extra['VehNo'])) {
            $mapped['vehicle_number'] = strtoupper(str_replace([' ', '-'], '', (string) $extra['VehNo']));
        }
        if (isset($extra['VehType'])) {
            $mapped['vehicle_type'] = ((string) $extra['VehType'] === 'O') ? 'Over Dimensional Cargo' : 'Regular';
        }
        if (isset($extra['TransId'])) {
            $mapped['transporter_id'] = $extra['TransId'];
        }
        if (isset($extra['TransName'])) {
            $mapped['transporter_name'] = $extra['TransName'];
        }
        if (array_key_exists('Distance', $extra)) {
            $mapped['transportation_distance'] = (string) (int) $extra['Distance'];
        }

        return $mapped;
    }

    /**
     * Portal auto distance when config allows and invoice distance is blank/zero.
     */
    protected function resolveDistanceKm(Invoice $invoice): int
    {
        $distance = (int) ($invoice->distance_km ?? 0);

        if ($distance > 0) {
            return $distance;
        }

        if (config('ewaybill.auto_distance', true)) {
            return 0;
        }

        return 50;
    }

    /**
     * Part-B vehicle update against Masters India (or local-only stub).
     *
     * @param  array<string, mixed>  $vehicleData
     * @return array<string, mixed>
     */
    public function updateEWayVehicle(Invoice $invoice, array $vehicleData): array
    {
        $eWayBill = $invoice->eWayBill;
        if (! $eWayBill || blank($eWayBill->eway_bill_no)) {
            throw new RuntimeException('Cannot update vehicle: No active E-Way Bill found for this invoice.');
        }

        $cleanVehNo = strtoupper(str_replace([' ', '-'], '', trim((string) ($vehicleData['vehicle_no'] ?? ''))));
        if ($cleanVehNo === '') {
            throw new RuntimeException('Vehicle number is required for Part-B update.');
        }

        if (! $this->isConfigured()) {
            if ((bool) config('services.mastersindia.fallback_to_stub')) {
                $this->auditLogService->record($invoice, 'eway_vehicle_updated_stub', null, [
                    'eway_bill_no' => $eWayBill->eway_bill_no,
                    'vehicle_no' => $cleanVehNo,
                ]);

                return [
                    'eway_bill_no' => $eWayBill->eway_bill_no,
                    'vehicle_no' => $cleanVehNo,
                    'status' => 'updated',
                    'raw' => ['note' => 'Stub vehicle update (no live credentials)'],
                ];
            }

            throw new RuntimeException('Masters India GSP credentials are not configured for vehicle update.');
        }

        $company = $invoice->selling_company
            ?? Company::query()->find($invoice->company_id)
            ?? Company::resolveCurrent()
            ?? Company::first();

        $fromState = (int) $this->complianceService->resolveStateCode(
            $vehicleData['from_state'] ?? $invoice->consignor_state ?? $company?->state,
            (string) ($company?->gstin ?? config('services.mastersindia.gstin'))
        );

        if ($this->usesApiV1()) {
            $payload = [
                'userGstin' => (string) ($company?->gstin ?: config('services.mastersindia.gstin')),
                'eway_bill_number' => $eWayBill->eway_bill_no,
                'vehicle_number' => $cleanVehNo,
                'vehicle_type' => (($vehicleData['vehicle_type'] ?? 'R') === 'O') ? 'Over Dimensional Cargo' : 'Regular',
                'place_of_consignor' => (string) ($vehicleData['from_place'] ?? $invoice->consignor_city ?? $company?->city ?? ''),
                'state_of_consignor' => (string) ($vehicleData['from_state'] ?? $invoice->consignor_state ?? $company?->state ?? ''),
                'reason_code_for_vehicle_updation' => (string) ($vehicleData['reason_code'] ?? '1'),
                'reason_for_vehicle_updation' => (string) ($vehicleData['reason'] ?? $vehicleData['remarks'] ?? 'Transshipment'),
                'transporter_document_number' => (string) ($invoice->lr_rr_no ?? ''),
                'transporter_document_date' => $invoice->lr_rr_date?->format('d/m/Y') ?? '',
                'mode_of_transport' => 'road',
                'data_source' => 'erp',
            ];
            $endpoint = '/api/v1/updateVehicleNumber/';
        } else {
            $payload = [
                'ewbNo' => $eWayBill->eway_bill_no,
                'vehicleNo' => $cleanVehNo,
                'fromPlace' => (string) ($vehicleData['from_place'] ?? $invoice->consignor_city ?? $company?->city ?? ''),
                'fromState' => $fromState,
                'reasonCode' => (string) ($vehicleData['reason_code'] ?? '1'),
                'reasonRem' => (string) ($vehicleData['reason'] ?? $vehicleData['remarks'] ?? 'Transshipment'),
                'transDocNo' => (string) ($invoice->lr_rr_no ?? ''),
                'transDocDate' => $invoice->lr_rr_date?->format('d/m/Y') ?? '',
                'transMode' => '1',
                'vehicleType' => $vehicleData['vehicle_type'] ?? 'R',
            ];
            $endpoint = '/ewb/api/ewaybill/updatevehicle';
        }

        $response = $this->client()->post($endpoint, $payload);
        if (! $response->ok()) {
            $err = $this->extractErrorMessage($response, 'Vehicle update failed');
            if ((bool) config('services.mastersindia.fallback_to_stub')) {
                return [
                    'eway_bill_no' => $eWayBill->eway_bill_no,
                    'vehicle_no' => $cleanVehNo,
                    'status' => 'updated',
                    'raw' => ['note' => 'Fallback stub vehicle update: '.$err],
                ];
            }
            throw new RuntimeException($err);
        }

        $body = $response->json();
        $eWayBill->update([
            'vehicle_no' => $cleanVehNo,
            'payload' => array_merge((array) $eWayBill->payload, ['vehicle_update' => $body]),
        ]);

        $this->auditLogService->record($invoice, 'eway_vehicle_updated', null, [
            'eway_bill_no' => $eWayBill->eway_bill_no,
            'vehicle_no' => $cleanVehNo,
        ]);

        return [
            'eway_bill_no' => $eWayBill->eway_bill_no,
            'vehicle_no' => $cleanVehNo,
            'status' => 'updated',
            'raw' => $body,
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
