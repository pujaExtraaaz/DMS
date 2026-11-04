<?php

namespace App\Domains\Sales\Services;

use App\Domains\Organization\Models\Company;
use App\Domains\Sales\Models\Invoice;
use Illuminate\Support\Str;

class GstComplianceService
{
    /**
     * Map of Indian state/UT names to official 2-digit GST state codes.
     */
    public const STATE_CODES = [
        'jammu and kashmir' => '01',
        'jammu & kashmir' => '01',
        'himachal pradesh' => '02',
        'punjab' => '03',
        'chandigarh' => '04',
        'uttarakhand' => '05',
        'uttaranchal' => '05',
        'haryana' => '06',
        'delhi' => '07',
        'rajasthan' => '08',
        'uttar pradesh' => '09',
        'bihar' => '10',
        'sikkim' => '11',
        'arunachal pradesh' => '12',
        'nagaland' => '13',
        'manipur' => '14',
        'mizoram' => '15',
        'tripura' => '16',
        'meghalaya' => '17',
        'assam' => '18',
        'west bengal' => '19',
        'jharkhand' => '20',
        'odisha' => '21',
        'orissa' => '21',
        'chhattisgarh' => '22',
        'madhya pradesh' => '23',
        'gujarat' => '24',
        'daman and diu' => '25',
        'dadra and nagar haveli' => '26',
        'dadra and nagar haveli and daman and diu' => '26',
        'maharashtra' => '27',
        'andhra pradesh' => '37',
        'karnataka' => '29',
        'goa' => '30',
        'lakshadweep' => '31',
        'kerala' => '32',
        'tamil nadu' => '33',
        'puducherry' => '34',
        'pondicherry' => '34',
        'andaman and nicobar islands' => '35',
        'telangana' => '36',
        'ladakh' => '38',
        'other territory' => '97',
    ];

    /**
     * E-Way Bill statutory consignment value threshold (₹50,000).
     */
    public const EWAY_BILL_THRESHOLD = 50000.00;

    /**
     * Determine official 2-digit GST state code.
     */
    public function resolveStateCode(?string $stateName, ?string $gstin = null): string
    {
        if ($gstin && strlen(trim($gstin)) >= 2 && ctype_digit(substr(trim($gstin), 0, 2))) {
            return substr(trim($gstin), 0, 2);
        }

        if ($stateName) {
            $normalized = strtolower(trim(preg_replace('/[^a-zA-Z0-9&]/', ' ', $stateName)));
            $normalized = preg_replace('/\s+/', ' ', $normalized);

            if (isset(self::STATE_CODES[$normalized])) {
                return self::STATE_CODES[$normalized];
            }
        }

        return '99';
    }

    /**
     * Determine whether an invoice is eligible/mandated for E-Invoicing (IRP).
     *
     * @return array{
     *   applicable: bool,
     *   status: string,
     *   reason: string,
     *   buyer_type: string,
     *   details: array<string, mixed>
     * }
     */
    public function evaluateEInvoiceEligibility(Invoice $invoice): array
    {
        // 1. Check if already generated or cancelled
        if ($invoice->eInvoice && $invoice->eInvoice->status === 'generated') {
            return [
                'applicable' => true,
                'eligible' => true,
                'status' => 'generated',
                'reason' => 'E-Invoice IRN already generated.',
                'buyer_type' => 'B2B',
                'details' => [
                    'irn' => $invoice->eInvoice->irn,
                    'ack_no' => $invoice->eInvoice->ack_no,
                ],
            ];
        }

        if ($invoice->eInvoice && $invoice->eInvoice->status === 'cancelled') {
            return [
                'applicable' => true,
                'eligible' => false,
                'status' => 'cancelled',
                'reason' => 'E-Invoice was generated and subsequently cancelled.',
                'buyer_type' => 'B2B',
                'details' => [
                    'cancelled_at' => $invoice->eInvoice->cancelled_at,
                    'cancel_reason' => $invoice->eInvoice->cancel_reason,
                ],
            ];
        }

        // 2. Validate Seller Registration
        $company = $invoice->selling_company
            ?? Company::query()->find($invoice->company_id)
            ?? Company::resolveCurrent()
            ?? Company::first();

        $sellerGstin = trim((string) ($company?->gstin ?? config('services.mastersindia.gstin', '')));
        if ($sellerGstin === '') {
            return [
                'applicable' => false,
                'eligible' => false,
                'status' => 'not_applicable',
                'reason' => 'Seller/Company GSTIN is not configured. E-Invoicing requires a registered regular GSTIN.',
                'buyer_type' => 'Unknown',
                'details' => ['missing' => 'seller_gstin'],
            ];
        }

        // 3. Validate Buyer Registration (B2B vs B2C)
        $customer = $invoice->customer;
        $buyerGstin = trim((string) ($invoice->billingAddress?->gstin ?: ($customer?->gstin ?: '')));
        $isGstinValid = $buyerGstin !== '' && strtoupper($buyerGstin) !== 'URP' && strlen($buyerGstin) === 15;

        if (! $isGstinValid) {
            return [
                'applicable' => false,
                'eligible' => false,
                'status' => 'not_applicable',
                'reason' => 'B2C / Unregistered Customer. Under GST Rule 48(4), E-Invoicing is only applicable to B2B supplies, SEZ units, and export transactions.',
                'buyer_type' => 'B2C',
                'details' => [
                    'buyer_gstin' => $buyerGstin ?: 'None (Unregistered)',
                ],
            ];
        }

        // 4. Validate Line Items & Values
        if ($invoice->items->isEmpty()) {
            return [
                'applicable' => false,
                'eligible' => false,
                'status' => 'not_applicable',
                'reason' => 'Invoice has no line items.',
                'buyer_type' => 'B2B',
                'details' => [],
            ];
        }

        $missingHsnCount = $invoice->items->filter(fn ($item) => blank($item->hsn_code ?? $item->product?->hsn_code))->count();
        if ($missingHsnCount > 0) {
            return [
                'applicable' => true,
                'eligible' => false,
                'status' => 'pending',
                'reason' => "Eligible B2B supply, but {$missingHsnCount} item(s) are missing HSN/SAC codes required for IRP registration.",
                'buyer_type' => 'B2B',
                'details' => ['missing_hsn_items' => $missingHsnCount],
            ];
        }

        return [
            'applicable' => true,
            'eligible' => true,
            'status' => 'pending',
            'reason' => 'Eligible B2B supply (Turnover > ₹5 Cr & Registered Buyer). E-Invoice generation is mandatory.',
            'buyer_type' => 'B2B',
            'details' => [
                'seller_gstin' => $sellerGstin,
                'buyer_gstin' => $buyerGstin,
                'items_count' => $invoice->items->count(),
                'grand_total' => (float) $invoice->grand_total,
            ],
        ];
    }

    /**
     * Determine whether an invoice requires or is eligible for an E-Way Bill.
     *
     * @param  array<string, mixed>  $overrides
     * @return array{
     *   applicable: bool,
     *   mandatory: bool,
     *   status: string,
     *   reason: string,
     *   missing_fields: array<string>,
     *   details: array<string, mixed>
     * }
     */
    public function evaluateEWayBillEligibility(Invoice $invoice, array $overrides = []): array
    {
        // 1. Check if already generated or cancelled
        if ($invoice->eWayBill && $invoice->eWayBill->status === 'generated') {
            return [
                'applicable' => true,
                'eligible' => true,
                'mandatory' => false,
                'status' => 'generated',
                'reason' => 'E-Way Bill already generated.',
                'missing_fields' => [],
                'details' => [
                    'eway_bill_no' => $invoice->eWayBill->eway_bill_no,
                    'valid_upto' => $invoice->eWayBill->valid_upto,
                ],
            ];
        }

        if ($invoice->eWayBill && $invoice->eWayBill->status === 'cancelled') {
            return [
                'applicable' => true,
                'eligible' => false,
                'mandatory' => false,
                'status' => 'cancelled',
                'reason' => 'E-Way Bill was generated and subsequently cancelled.',
                'missing_fields' => [],
                'details' => [
                    'cancelled_at' => $invoice->eWayBill->cancelled_at,
                    'cancel_reason' => $invoice->eWayBill->cancel_reason,
                ],
            ];
        }

        $grandTotal = (float) $invoice->grand_total;
        $userRequirement = strtolower((string) ($overrides['eway_bill_required'] ?? $invoice->eway_bill_required ?? 'auto'));

        // 2. Value threshold check
        $isOverThreshold = $grandTotal >= self::EWAY_BILL_THRESHOLD;

        // User explicit override check
        if ($userRequirement === 'no' && ! $isOverThreshold) {
            return [
                'applicable' => false,
                'eligible' => false,
                'mandatory' => false,
                'status' => 'not_applicable',
                'reason' => 'Not Required (Invoice total ₹' . number_format($grandTotal, 2) . ' is within the ₹' . number_format(self::EWAY_BILL_THRESHOLD, 0) . ' statutory exemption threshold, and user chose not to generate).',
                'missing_fields' => [],
                'details' => ['threshold' => self::EWAY_BILL_THRESHOLD, 'total' => $grandTotal],
            ];
        }

        if ($userRequirement === 'no' && $isOverThreshold) {
            // Cannot bypass mandatory statutory threshold
            $userRequirement = 'auto';
        }

        $isMandatory = $isOverThreshold;
        $isApplicable = $isOverThreshold || ($userRequirement === 'yes');

        if (! $isApplicable) {
            return [
                'applicable' => false,
                'eligible' => false,
                'mandatory' => false,
                'status' => 'not_applicable',
                'reason' => 'Consignment value ₹' . number_format($grandTotal, 2) . ' is below statutory threshold of ₹' . number_format(self::EWAY_BILL_THRESHOLD, 0) . '. E-Way Bill is optional.',
                'missing_fields' => [],
                'details' => ['threshold' => self::EWAY_BILL_THRESHOLD, 'total' => $grandTotal],
            ];
        }

        // 3. Missing transport fields check
        $missing = [];

        $company = $invoice->selling_company
            ?? Company::query()->find($invoice->company_id)
            ?? Company::resolveCurrent()
            ?? Company::first();

        $fromPin = trim((string) ($company?->pincode ?? ''));
        if ($fromPin === '') {
            $missing[] = 'Seller/Dispatch Pincode';
        }

        $toPin = trim((string) ($invoice->shippingAddress?->pincode ?: ($invoice->customer?->shipping_pincode ?: ($invoice->customer?->pincode ?: ''))));
        if ($toPin === '') {
            $missing[] = 'Delivery/Destination Pincode';
        }

        $rawMode = strtolower(trim((string) ($overrides['transport_mode'] ?? $invoice->transport_mode ?? '1')));
        $transportMode = match ($rawMode) {
            '2', 'rail' => '2',
            '3', 'air' => '3',
            '4', 'ship' => '4',
            default => '1',
        };

        $transporterId = trim((string) ($overrides['transporter_id'] ?? $invoice->transporter_id ?? ''));
        $vehicleNo = trim((string) ($overrides['vehicle_no'] ?? $invoice->vehicle_no ?? ''));
        $docNo = trim((string) ($overrides['lr_rr_no'] ?? ($overrides['dispatch_doc_no'] ?? ($invoice->lr_rr_no ?? $invoice->dispatch_doc_no ?? ''))));

        if ($transportMode === '1') { // Road
            if ($vehicleNo === '' && $transporterId === '') {
                $missing[] = 'Vehicle Number or Transporter ID (GSTIN)';
            }
        } else { // Rail, Air, Ship
            if ($docNo === '') {
                $missing[] = 'Transport Document Number (LR / RR / Airway Bill / Bill of Lading)';
            }
        }

        $distance = (int) ($overrides['distance_km'] ?? $invoice->distance_km ?? 0);
        if ($distance <= 0) {
            $missing[] = 'Distance (in KM)';
        }

        $reason = $isMandatory
            ? 'Mandatory: Consignment value exceeds ₹' . number_format(self::EWAY_BILL_THRESHOLD, 0) . ' threshold under GST Rule 138.'
            : 'Voluntary generation requested for consignment value ₹' . number_format($grandTotal, 2) . '.';

        if (! empty($missing)) {
            $reason .= ' Additional transport details required: ' . implode(', ', $missing) . '.';
        }

        return [
            'applicable' => true,
            'eligible' => true,
            'mandatory' => $isMandatory,
            'status' => 'pending',
            'reason' => $reason,
            'missing_fields' => $missing,
            'details' => [
                'grand_total' => $grandTotal,
                'transport_mode' => $transportMode,
                'vehicle_no' => $vehicleNo,
                'transporter_id' => $transporterId,
                'distance_km' => $distance,
            ],
        ];
    }
}
