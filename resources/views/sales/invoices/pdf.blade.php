<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>e-Invoice - {{ $invoice->invoice_no }}</title>
    <style>
        @page { size: A4; margin: 8mm; }
        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            min-height: 100%;
            background: #f3f4f6;
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #111;
            line-height: 1.5;
        }
        body {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 20px 16px;
            box-sizing: border-box;
        }
        .invoice-box {
            width: 100%;
            max-width: 1000px;
            min-height: 100%;
            border: 1px solid #111;
            padding: 12px 14px 14px;
            box-sizing: border-box;
            background: #fff;
        }
        .header-table { width: 100%; border-bottom: 2px solid #000; padding-bottom: 14px; margin-bottom: 14px; }
        .header-title { font-size: 22px; font-weight: bold; text-align: center; text-transform: uppercase; margin: 10px 0; }
        .sub-header { font-size: 9px; text-align: right; color: #374151; margin-bottom: 8px; }
        .recipient-box { border: 1px solid #000; padding: 4px 8px; font-weight: bold; font-size: 10px; display: inline-block; float: right; }
        .clear { clear: both; }
        .grid-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 9px; }
        .grid-table td, .grid-table th { border: 1px solid #111; padding: 6px 7px; vertical-align: top; }
        .bg-gray { background-color: #f3f4f6; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }
        @media print {
            html, body {
                background: #fff;
                padding: 0;
            }
            body {
                display: block;
                padding: 0;
            }
            .invoice-box {
                max-width: none;
                width: 100%;
                border: none;
            }
        }
    </style>
</head>
<body>
    @php
        $company = $company ?? \App\Domains\Organization\Models\Company::query()->first();
        $companyName = $company?->name ?? config('app.name');
        $companyGstin = $company?->gstin ?? 'Not configured';
        $companyAddress = $company?->address ?? 'Address not configured';
        $companyState = $company?->state ?? 'NA';
        $companyPincode = $company?->pincode ?? '-';
        $customer = $invoice->customer;
    @endphp
    <div class="invoice-box">
        <div class="sub-header">
            <span class="recipient-box">Original For Recipient</span>
            Schema version: 1.0 &nbsp;|&nbsp; Tax scheme: GST
        </div>
        <div class="clear"></div>

        <table class="header-table">
            <tr>
                <td style="width: 20%;">
                   @if(! empty($invoiceQrDataUri))
                        <img
                            src="{{ $invoiceQrDataUri }}"
                            alt="Scan to view invoice"
                            width="90"
                            height="90"
                            style="border:1px solid #ccc;"
                        >
                        <div style="font-size:8px; color:#6b7280; text-align:center; margin-top:2px;width:90px;">
                            Scan to view invoice
                        </div>
                    @endif
                </td>
                <td style="width: 60%; text-align: center;">
                    @if($company?->logo_url)
                        <div style="margin-bottom: 4px;">
                            <img src="{{ $company->logo_url }}" alt="{{ $companyName }}" style="max-height: 44px; max-width: 160px; object-fit: contain;">
                        </div>
                    @endif
                    <div style="font-size: 9px; color: #4b5563;">e-Invoice System</div>
                    <div class="header-title">e-Invoice</div>
                    <div style="font-size: 10px; font-weight: bold;">TAX INVOICE</div>
                </td>
                <td style="width: 20%; text-align: right; vertical-align: bottom; font-size: 9px;">
                    <div>Date: {{ $invoice->invoice_date->format('d/m/Y') }}</div>
                    @if($invoice->reference_no)<div>Ref: {{ $invoice->reference_no }}</div>@endif
                    @if($invoice->vehicle_no)<div>Vehicle: {{ $invoice->vehicle_no }}</div>@endif
                    @if($invoice->transport_mode)<div>Transport: {{ $invoice->transport_mode }}</div>@endif
                </td>
            </tr>
        </table>

        <table class="grid-table">
            <tr>
                <td style="width: 50%;">
                    <div><span class="bold">1. GSTIN:</span> {{ $companyGstin }}</div>
                    <div><span class="bold">2. Name:</span> {{ $companyName }}</div>
                    <div><span class="bold">3. Address:</span> {{ $companyAddress }}</div>
                    <div><span class="bold">4. Serial No. of Invoice:</span> {{ $invoice->invoice_no }}</div>
                    <div><span class="bold">5. Date of Invoice:</span> {{ $invoice->invoice_date->format('d/m/Y') }}</div>
                    <div><span class="bold">6. IRN No.:</span> {{ $invoice->eInvoice->irn ?? 'IRN-'.strtoupper(substr(md5($invoice->invoice_no), 0, 16)) }}</div>
                </td>
                <td style="width: 50%;">
                    <div><span class="bold">Dispatch from:</span> {{ $companyName }} ({{ $companyGstin }})</div>
                    <div><span class="bold">Address:</span> {{ $companyAddress }}</div>
                    <div><span class="bold">State:</span> {{ $companyState }} &nbsp;&nbsp; <span class="bold">Pincode:</span> {{ $companyPincode }}</div>
                </td>
            </tr>
        </table>

        <table class="grid-table">
            <tr class="bg-gray">
                <th style="width: 50%;">Details Of Receiver (Billed to)</th>
                <th style="width: 50%;">Details Of Consignee (Shipped to)</th>
            </tr>
            <tr>
                <td>
                    <div><span class="bold">Name:</span> {{ $invoice->billingAddress?->contact_person ?: $customer->name }}</div>
                    <div><span class="bold">Address:</span> {!! nl2br(e($invoice->billing_address ?: ($customer->address ?: '-'))) !!}</div>
                    <div><span class="bold">State:</span> {{ $invoice->billingAddress?->state ?: ($customer->state ?: '-') }} &nbsp;&nbsp; <span class="bold">Pin Code:</span> {{ $invoice->billingAddress?->pincode ?: ($customer->pincode ?: '-') }}</div>
                    <div><span class="bold">GSTIN/Unique ID:</span> {{ $invoice->billingAddress?->gstin ?: ($customer->gstin ?: '-') }}</div>
                </td>
                <td>
                    <div><span class="bold">Name:</span> {{ $invoice->shippingAddress?->contact_person ?: ($customer->shipping_name ?: $customer->name) }}</div>
                    <div><span class="bold">Address:</span> {!! nl2br(e($invoice->shipping_address ?: ($invoice->billing_address ?: ($customer->shipping_address ?: ($customer->address ?: '-'))))) !!}</div>
                    <div><span class="bold">State:</span> {{ $invoice->shippingAddress?->state ?: ($invoice->delivery_state ?: ($customer->shipping_state ?: ($customer->state ?: '-'))) }} &nbsp;&nbsp; <span class="bold">Pin Code:</span> {{ $invoice->shippingAddress?->pincode ?: ($customer->shipping_pincode ?: ($customer->pincode ?: '-')) }}</div>
                    <div><span class="bold">GSTIN/Unique ID:</span> {{ $invoice->shippingAddress?->gstin ?: ($customer->shipping_gstin ?: ($customer->gstin ?: '-')) }}</div>
                </td>
            </tr>
        </table>

        <table class="grid-table">
            <tr class="bg-gray">
                <th style="width: 4%;">#</th>
                <th style="width: 32%;">Item Description</th>
                <th style="width: 10%;">HSN</th>
                <th style="width: 8%;" class="text-right">Qty</th>
                <th style="width: 10%;" class="text-right">Unit Price</th>
                <th style="width: 8%;" class="text-right">Discount</th>
                <th style="width: 8%;" class="text-right">Taxable</th>
                <th style="width: 10%;" class="text-right">GST Rate</th>
                <th style="width: 10%;" class="text-right">Total</th>
            </tr>
            @foreach($invoice->items as $i => $item)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>
                        <div class="bold">{{ $item->item_name }}</div>
                        @if($item->batch_no)<div style="color: #4b5563;">Batch: {{ $item->batch_no }}</div>@endif
                        @if($item->serial_no)<div style="color: #4b5563;">S/N: {{ $item->serial_no }}</div>@endif
                    </td>
                    <td>{{ $item->hsn_code ?: '-' }}</td>
                    <td class="text-right">{{ number_format($item->quantity, 0) }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">{{ number_format($item->discount_amount, 2) }}</td>
                    <td class="text-right">{{ number_format($item->taxable_amount, 2) }}</td>
                    <td class="text-right">{{ number_format($item->gst_rate, 2) }}%</td>
                    <td class="text-right bold">{{ number_format($item->total_amount, 2) }}</td>
                </tr>
            @endforeach
        </table>

        <table class="grid-table">
            <tr class="bg-gray">
                <th class="text-right">Taxable Amount</th>
                <th class="text-right">CGST</th>
                <th class="text-right">SGST</th>
                <th class="text-right">IGST</th>
                <th class="text-right">Round Off</th>
                <th class="text-right">Invoice Total</th>
            </tr>
            <tr>
                <td class="text-right">{{ number_format($invoice->taxable_amount, 2) }}</td>
                <td class="text-right">{{ number_format($invoice->cgst_amount, 2) }}</td>
                <td class="text-right">{{ number_format($invoice->sgst_amount, 2) }}</td>
                <td class="text-right">{{ number_format($invoice->igst_amount, 2) }}</td>
                <td class="text-right">{{ number_format($invoice->round_off, 2) }}</td>
                <td class="text-right bold" style="font-size: 11px;">INR {{ number_format($invoice->total_amount, 2) }}</td>
            </tr>
        </table>

        <div style="font-size: 9px; margin-bottom: 12px;">
            <span class="bold">Amount in Words:</span>
            {{ \App\Support\NumberToWords::convert($invoice->total_amount) }}
        </div>

        <table class="grid-table" style="margin-bottom: 0;">
            <tr>
                <td style="width: 50%;">
                    <div class="bold">Bank Details:</div>
                    <div>Bank: {{ $company?->bank_name ?: 'HDFC Bank' }}</div>
                    <div>A/C: {{ $company?->bank_account_no ?: '-' }}</div>
                    <div>IFSC: {{ $company?->bank_ifsc ?: '-' }}</div>
                    @if($company?->upi_id)<div>UPI: {{ $company->upi_id }}</div>@endif
                    <div style="margin-top: 6px;" class="bold">Terms &amp; Conditions:</div>
                    <div style="color: #4b5563;">{{ $company?->selling_terms_and_conditions ?: '1. Subject to local jurisdiction. 2. Goods once sold will not be taken back.' }}</div>
                </td>
                <td style="width: 50%; text-align: right; vertical-align: bottom;">
                    <div style="margin-bottom: 40px;">For <span class="bold">{{ $companyName }}</span></div>
                    <div class="bold">Authorized Signatory</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>