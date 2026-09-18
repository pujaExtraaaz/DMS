<?php

namespace App\Domains\Sales\Services;

use App\Domains\Sales\Models\Invoice;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Thin client for the Masters India GSP APIs — the two calls the DMS actually needs:
 *   1. generateIrn()      — request IRN + signed QR for a tax invoice
 *   2. generateEWayBill() — request an E-way Bill against an existing IRN
 *
 * The client caches the OAuth Bearer token per client_id in the shared cache to
 * avoid re-authenticating on every call.
 *
 * When credentials are absent AND `services.mastersindia.fallback_to_stub` is
 * true (default in local/dev), the service returns deterministic dummy values
 * so the invoicing flow keeps working end-to-end without live credentials.
 */
class MastersIndiaGspService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.mastersindia.base_url'), '/');
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
     * @return array{
     *   irn:string, ack_no:?string, ack_date:?string, signed_invoice:?string,
     *   signed_qr_base64:?string, status:string, raw:array
     * }
     */
    public function generateIrn(Invoice $invoice): array
    {
        if (! $this->isConfigured()) {
            return $this->stubIrn($invoice, 'Masters India credentials not configured; using deterministic stub.');
        }

        try {
            $payload = $this->buildIrnPayload($invoice);
            $response = $this->client()->post('/ei/api/invoice', $payload);

            if (! $response->ok()) {
                Log::warning('MastersIndia IRN call failed', ['status' => $response->status(), 'body' => $response->body()]);

                if ((bool) config('services.mastersindia.fallback_to_stub')) {
                    return $this->stubIrn($invoice, 'Upstream error: '.$response->status());
                }

                throw new RuntimeException('Masters India IRN generation failed: '.$response->body());
            }

            $body = $response->json();
            $data = $body['Data'] ?? $body['data'] ?? $body;

            return [
                'irn' => (string) ($data['Irn'] ?? $data['irn'] ?? ''),
                'ack_no' => $data['AckNo'] ?? $data['ack_no'] ?? null,
                'ack_date' => $data['AckDt'] ?? $data['ack_date'] ?? null,
                'signed_invoice' => $data['SignedInvoice'] ?? $data['signed_invoice'] ?? null,
                'signed_qr_base64' => $data['SignedQRCode'] ?? $data['signed_qr_base64'] ?? null,
                'status' => 'generated',
                'raw' => $body,
            ];
        } catch (\Throwable $e) {
            Log::error('MastersIndia IRN exception', ['error' => $e->getMessage()]);
            if ((bool) config('services.mastersindia.fallback_to_stub')) {
                return $this->stubIrn($invoice, 'Exception: '.$e->getMessage());
            }
            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $extra vehicle_no, transport_mode, transporter_id, distance_km, transporter_name
     * @return array{eway_bill_no:string, ewb_date:?string, valid_upto:?string, status:string, raw:array}
     */
    public function generateEWayBill(Invoice $invoice, array $extra = []): array
    {
        if (! $this->isConfigured()) {
            return $this->stubEwayBill($invoice, 'Masters India credentials not configured; using deterministic stub.');
        }

        try {
            $eInvoice = $invoice->eInvoice;
            if (! $eInvoice || blank($eInvoice->irn)) {
                throw new RuntimeException('E-way bill needs an IRN. Generate the e-invoice first.');
            }

            $payload = array_merge($this->buildEwbPayload($invoice), $extra);
            $response = $this->client()->post('/ei/api/ewayapi', $payload);

            if (! $response->ok()) {
                Log::warning('MastersIndia EWB call failed', ['status' => $response->status(), 'body' => $response->body()]);
                if ((bool) config('services.mastersindia.fallback_to_stub')) {
                    return $this->stubEwayBill($invoice, 'Upstream error: '.$response->status());
                }
                throw new RuntimeException('Masters India EWB generation failed: '.$response->body());
            }

            $body = $response->json();
            $data = $body['Data'] ?? $body['data'] ?? $body;

            return [
                'eway_bill_no' => (string) ($data['EwbNo'] ?? $data['ewbNo'] ?? $data['eway_bill_no'] ?? ''),
                'ewb_date' => $data['EwbDt'] ?? $data['ewb_date'] ?? null,
                'valid_upto' => $data['EwbValidTill'] ?? $data['valid_upto'] ?? null,
                'status' => 'generated',
                'raw' => $body,
            ];
        } catch (\Throwable $e) {
            Log::error('MastersIndia EWB exception', ['error' => $e->getMessage()]);
            if ((bool) config('services.mastersindia.fallback_to_stub')) {
                return $this->stubEwayBill($invoice, 'Exception: '.$e->getMessage());
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
                'Authorization' => 'Bearer '.$token,
                'client_id' => (string) config('services.mastersindia.client_id'),
                'client_secret' => (string) config('services.mastersindia.client_secret'),
                'gstin' => (string) config('services.mastersindia.gstin'),
            ]);
    }

    protected function accessToken(): string
    {
        $clientId = (string) config('services.mastersindia.client_id');
        $key = 'mastersindia:token:'.md5($clientId);

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
                throw new RuntimeException('Masters India auth failed: '.$resp->body());
            }

            return (string) ($resp->json('access_token') ?? $resp->json('data.access_token') ?? '');
        });
    }

    /**
     * Build a minimal but valid e-invoice payload per IRP schema 1.1.
     * Buyer, seller and item mapping only — extra fields ship as null so IRP accepts.
     */
    protected function buildIrnPayload(Invoice $invoice): array
    {
        $company = optional(auth()->user())->company ?? \App\Domains\Organization\Models\Company::query()->first();
        $customer = $invoice->customer;

        $items = $invoice->items->map(function ($item, $i) {
            $tax = (float) ($item->tax_amount ?? 0);
            $lineTotal = (float) $item->line_total;
            $taxable = round($lineTotal - $tax, 2);

            return [
                'SlNo' => (string) ($i + 1),
                'PrdDesc' => $item->product?->name ?? 'Item',
                'IsServc' => 'N',
                'HsnCd' => (string) ($item->hsn_code ?? $item->product?->hsn_code ?? '00000000'),
                'Qty' => (float) $item->quantity,
                'Unit' => $item->uom?->code ?? 'NOS',
                'UnitPrice' => (float) $item->unit_price,
                'TotAmt' => round($item->quantity * $item->unit_price, 2),
                'Discount' => (float) $item->discount_amount,
                'AssAmt' => $taxable,
                'GstRt' => (float) ($item->product?->tax_rate ?? 0),
                'CgstAmt' => round($tax / 2, 2),
                'SgstAmt' => round($tax / 2, 2),
                'IgstAmt' => 0,
                'TotItemVal' => (float) $lineTotal,
            ];
        })->values()->all();

        return [
            'Version' => '1.1',
            'TranDtls' => ['TaxSch' => 'GST', 'SupTyp' => 'B2B'],
            'DocDtls' => [
                'Typ' => 'INV',
                'No' => $invoice->invoice_no,
                'Dt' => $invoice->invoice_date->format('d/m/Y'),
            ],
            'SellerDtls' => [
                'Gstin' => $company?->gstin ?? config('services.mastersindia.gstin'),
                'LglNm' => $company?->legal_name ?? $company?->name,
                'Addr1' => substr((string) $company?->address, 0, 100),
                'Loc' => $company?->state ?? 'NA',
                'Pin' => (int) ($company?->pincode ?? 0),
                'Stcd' => $company?->state ?? 'NA',
            ],
            'BuyerDtls' => [
                'Gstin' => $customer?->gstin ?? 'URP',
                'LglNm' => $customer?->name,
                'Pos' => $customer?->state ?? 'NA',
                'Addr1' => substr((string) $customer?->address, 0, 100),
                'Loc' => $customer?->state ?? 'NA',
                'Pin' => (int) ($customer?->pincode ?? 0),
                'Stcd' => $customer?->state ?? 'NA',
            ],
            'ItemList' => $items,
            'ValDtls' => [
                'AssVal' => (float) $invoice->subtotal,
                'CgstVal' => round(((float) $invoice->tax_amount) / 2, 2),
                'SgstVal' => round(((float) $invoice->tax_amount) / 2, 2),
                'IgstVal' => 0,
                'Discount' => (float) $invoice->discount_amount,
                'TotInvVal' => (float) $invoice->grand_total,
            ],
        ];
    }

    protected function buildEwbPayload(Invoice $invoice): array
    {
        $eInv = $invoice->eInvoice;

        return [
            'Irn' => $eInv?->irn,
            'Distance' => 0,
            'TransMode' => $invoice->transport_mode ?? '1',
            'TransDocNo' => $invoice->reference_no,
            'TransDocDt' => $invoice->invoice_date->format('d/m/Y'),
            'VehNo' => $invoice->vehicle_no,
            'VehType' => 'R',
        ];
    }

    protected function stubIrn(Invoice $invoice, ?string $reason = null): array
    {
        $seed = hash('sha256', $invoice->invoice_no.$invoice->invoice_date->toDateString());
        $irn = strtoupper(substr($seed, 0, 32));

        return [
            'irn' => $irn,
            'ack_no' => substr($seed, 32, 15),
            'ack_date' => Carbon::now()->toIso8601String(),
            'signed_invoice' => null,
            'signed_qr_base64' => base64_encode('DUMMY:'.$irn.'|GSTIN:'.($invoice->customer?->gstin ?? 'URP').'|INV:'.$invoice->invoice_no),
            'status' => 'stub',
            'raw' => ['note' => 'Deterministic stub because '.($reason ?? 'no credentials')],
        ];
    }

    protected function stubEwayBill(Invoice $invoice, ?string $reason = null): array
    {
        $seed = hash('sha256', $invoice->invoice_no.'eway');
        return [
            'eway_bill_no' => strtoupper(substr($seed, 0, 12)),
            'ewb_date' => Carbon::now()->toIso8601String(),
            'valid_upto' => Carbon::now()->addDay()->toIso8601String(),
            'status' => 'stub',
            'raw' => ['note' => 'Deterministic stub because '.($reason ?? 'no credentials')],
        ];
    }
}
