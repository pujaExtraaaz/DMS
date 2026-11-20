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

        $fromPin = trim((string) ($overrides['consignor_pincode'] ?? $invoice->consignor_pincode ?? $company?->pincode ?? ''));
        if ($fromPin === '') {
            $missing[] = 'Seller/Dispatch Pincode';
        }

        $toPin = trim((string) ($overrides['consignee_pincode'] ?? $invoice->consignee_pincode ?? $invoice->shippingAddress?->pincode ?: ($invoice->customer?->shipping_pincode ?: ($invoice->customer?->pincode ?: ''))));
        if ($toPin === '') {
            $missing[] = 'Delivery/Destination Pincode';
        }

        $rawMode = strtolower(trim((string) ($overrides['transport_mode'] ?? $invoice->transport_mode ?? '1')));
        $isNotApplicable = str_contains($rawMode, 'not applicable') || $rawMode === 'na';
        $transportMode = match (true) {
            str_contains($rawMode, '2') || str_contains($rawMode, 'rail') => '2',
            str_contains($rawMode, '3') || str_contains($rawMode, 'air') => '3',
            str_contains($rawMode, '4') || str_contains($rawMode, 'ship') => '4',
            default => '1',
        };

        $transporterId = trim((string) ($overrides['transporter_id'] ?? $invoice->transporter_id ?? ''));
        $vehicleNo = trim((string) ($overrides['vehicle_no'] ?? $invoice->vehicle_no ?? ''));
        $docNo = trim((string) ($overrides['lr_rr_no'] ?? ($overrides['dispatch_doc_no'] ?? ($invoice->lr_rr_no ?? $invoice->dispatch_doc_no ?? ''))));

        if (! $isNotApplicable) {
            if ($transportMode === '1') { // Road
                if ($vehicleNo === '' && $transporterId === '') {
                    $missing[] = 'Vehicle Number or Transporter ID (GSTIN)';
                }
            } else { // Rail, Air, Ship
                if ($docNo === '') {
                    $missing[] = 'Transport Document Number (LR / RR / Airway Bill / Bill of Lading)';
                }
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

    /**
     * Comprehensive validation of required invoice and transport information
     * prior to dispatching an E-Way Bill generation request to the government portal.
     *
     * @param  array<string, mixed>  $overrides
     * @return array{
     *   valid: bool,
     *   errors: array<string, string>,
     *   missing_fields: array<int, string>
     * }
     */
    public function validateForEWayBillGeneration(Invoice $invoice, array $overrides = []): array
    {
        $errors = [];

        // 1. Invoice Number, Date & Totals
        if (blank($invoice->invoice_no)) {
            $errors['invoice_no'] = 'Invoice number is required.';
        }

        if (! $invoice->invoice_date) {
            $errors['invoice_date'] = 'Invoice date is required.';
        }

        $grandTotal = (float) $invoice->grand_total;
        if ($grandTotal <= 0) {
            $errors['grand_total'] = 'Invoice total consignment value must be greater than zero.';
        }

        // 2. Selling Company / Supplier GSTIN
        $company = $invoice->selling_company
            ?? Company::query()->find($invoice->company_id)
            ?? Company::resolveCurrent()
            ?? Company::first();

        $sellerGstin = trim((string) ($overrides['consignor_gstin'] ?? $invoice->consignor_gstin ?? $company?->gstin ?? config('services.mastersindia.gstin') ?? ''));
        if (blank($sellerGstin)) {
            $errors['supplier_gstin'] = 'Supplier GSTIN is required on Company Master or Consignor details.';
        } elseif (strlen($sellerGstin) !== 15 || ! preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/i', $sellerGstin)) {
            $errors['supplier_gstin'] = "Supplier GSTIN '{$sellerGstin}' is invalid. It must be a standard 15-character GSTIN.";
        }

        // 3. Buyer / Recipient Details
        $customer = $invoice->customer;
        $rawBuyerGstin = trim((string) ($overrides['consignee_gstin'] ?? $invoice->consignee_gstin ?? $invoice->billingAddress?->gstin ?: ($customer?->gstin ?: '')));
        $buyerGstin = $rawBuyerGstin !== '' ? strtoupper($rawBuyerGstin) : 'URP';

        if ($buyerGstin !== 'URP' && (strlen($buyerGstin) !== 15 || ! preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/i', $buyerGstin))) {
            $errors['recipient_gstin'] = "Recipient GSTIN '{$buyerGstin}' is invalid. It must be a valid 15-character GSTIN or 'URP' for unregistered customers.";
        }

        // 4. Products, HSN, Quantity and Tax Details
        $items = $invoice->items;
        if ($items->isEmpty()) {
            $errors['items'] = 'Invoice must contain at least one product item to generate an E-Way Bill.';
        } else {
            foreach ($items as $idx => $item) {
                $lineNo = $idx + 1;
                $prodName = trim((string) ($item->product?->name ?: $item->item_name ?: "Item #{$lineNo}"));

                $hsn = trim((string) ($item->hsn_code ?: $item->product?->hsn_code ?: ''));
                if (blank($hsn)) {
                    $errors["item_{$lineNo}_hsn"] = "Product '{$prodName}' (Row #{$lineNo}) is missing an HSN code in Product Master.";
                } elseif (strlen(preg_replace('/\D/', '', $hsn)) < 2) {
                    $errors["item_{$lineNo}_hsn"] = "Product '{$prodName}' (Row #{$lineNo}) has an invalid HSN code '{$hsn}'. Must contain at least 2 digits.";
                }

                if ((float) $item->quantity <= 0) {
                    $errors["item_{$lineNo}_qty"] = "Product '{$prodName}' (Row #{$lineNo}) quantity must be greater than zero.";
                }

                $uom = trim((string) ($item->uom?->code ?: ($item->uom?->name ?: ($item->product?->baseUom?->code ?: ''))));
                if (blank($uom)) {
                    $errors["item_{$lineNo}_uom"] = "Product '{$prodName}' (Row #{$lineNo}) is missing a Unit of Measurement (UOM).";
                }

                $taxRate = $item->tax_rate !== null ? $item->tax_rate : ($item->product?->tax_rate ?? null);
                if ($taxRate === null) {
                    $errors["item_{$lineNo}_tax"] = "Product '{$prodName}' (Row #{$lineNo}) is missing tax rate configuration.";
                }
            }
        }

        // 5. Place of Dispatch & Delivery
        $fromPin = preg_replace('/\D/', '', (string) ($overrides['consignor_pincode'] ?? $invoice->consignor_pincode ?? $company?->pincode ?? ''));
        if (blank($fromPin) || strlen($fromPin) !== 6) {
            $errors['from_pincode'] = 'Seller/Dispatch Pincode is required and must be a valid 6-digit number.';
        }

        $fromPlace = trim((string) ($overrides['consignor_city'] ?? $invoice->consignor_city ?? $company?->city ?: ($company?->state ?: '')));
        if (blank($fromPlace)) {
            $errors['from_place'] = 'Seller/Dispatch Place (City) is required.';
        }

        $toPin = preg_replace('/\D/', '', (string) ($overrides['consignee_pincode'] ?? $invoice->consignee_pincode ?? $invoice->shippingAddress?->pincode ?: ($customer?->shipping_pincode ?: ($customer?->pincode ?: ''))));
        if (blank($toPin) || strlen($toPin) !== 6) {
            $errors['to_pincode'] = 'Recipient/Delivery Pincode is required and must be a valid 6-digit number.';
        }

        $toPlace = trim((string) ($overrides['consignee_city'] ?? $invoice->ship_to_place ?: ($invoice->consignee_city ?: ($invoice->destination ?: ($customer?->shipping_city ?: ($customer?->city ?: ($customer?->state ?: '')))))));
        if (blank($toPlace)) {
            $errors['to_place'] = 'Recipient/Delivery Place (City) is required.';
        }

        // 6. Transport Mode, Distance & Vehicle/Doc Details
        $rawMode = strtolower(trim((string) ($overrides['transport_mode'] ?? $invoice->transport_mode ?? '1')));
        $transportMode = match (true) {
            str_contains($rawMode, '2') || str_contains($rawMode, 'rail') => '2',
            str_contains($rawMode, '3') || str_contains($rawMode, 'air') => '3',
            str_contains($rawMode, '4') || str_contains($rawMode, 'ship') => '4',
            default => '1',
        };

        $distance = (int) ($overrides['distance_km'] ?? $invoice->distance_km ?? 0);
        if ($distance <= 0 || $distance > 9999) {
            $errors['distance_km'] = 'Transport Distance (in KM) is required and must be between 1 and 9999 KM.';
        }

        $vehicleNo = trim((string) ($overrides['vehicle_no'] ?? $invoice->vehicle_no ?? ''));
        $transporterId = trim((string) ($overrides['transporter_id'] ?? $invoice->transporter_id ?? ''));
        $docNo = trim((string) ($overrides['lr_rr_no'] ?? ($overrides['dispatch_doc_no'] ?? ($invoice->lr_rr_no ?? $invoice->dispatch_doc_no ?? ''))));
        $docDate = $overrides['lr_rr_date'] ?? $invoice->lr_rr_date ?? null;

        if ($transportMode === '1') { // Road
            if (blank($vehicleNo) && blank($transporterId)) {
                $errors['vehicle_or_transporter'] = 'For Road transport, either a Vehicle Number (for Part-B) or Transporter ID (GSTIN) must be provided.';
            }

            if (filled($vehicleNo)) {
                $cleanVeh = strtoupper(str_replace([' ', '-'], '', $vehicleNo));
                if (strlen($cleanVeh) < 4 || strlen($cleanVeh) > 16 || ! preg_match('/^[A-Z0-9]+$/', $cleanVeh)) {
                    $errors['vehicle_no'] = "Vehicle number '{$vehicleNo}' format is invalid. E.g. MH-12-AB-1234 or DL01AB1234.";
                }
            }

            if (filled($transporterId)) {
                if (strlen($transporterId) !== 15) {
                    $errors['transporter_id'] = "Transporter ID '{$transporterId}' is invalid. It must be a 15-character GSTIN or TRANSIN.";
                }
            }
        } else { // Rail, Air, Ship
            if (blank($docNo)) {
                $errors['transport_doc_no'] = 'Transport Document Number (LR / RR / Airway Bill / Bill of Lading) is required for Rail/Air/Ship transport.';
            }
            if (blank($docDate)) {
                $errors['transport_doc_date'] = 'Transport Document Date is required for Rail/Air/Ship transport.';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'missing_fields' => array_keys($errors),
        ];
    }
}
