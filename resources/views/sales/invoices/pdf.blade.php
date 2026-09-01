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
        $companyName = config('app.company_name', config('app.name'));
        $companyGstin = config('app.company_gstin', 'Not configured');
        $companyAddress = config('app.company_address', 'Address not configured');
        $companyState = config('app.company_state', 'Not configured');
        $companyPincode = config('app.company_pincode', 'Not configured');
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
                    @php
                        $qrData = urlencode("IRN:" . ($invoice->eInvoice->irn ?? 'PENDING') . "|GSTIN:" . ($invoice->customer->gstin ?? '27AAAAA0000A1Z5') . "|INV:" . $invoice->invoice_no);
                    @endphp
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=90x90&data={{ $qrData }}" alt="QR Code" width="85" height="85" style="border:1px solid #ccc;">
                </td>
                <td style="width: 60%; text-align: center;">
                    <div style="font-size: 9px; color: #4b5563;">e-Invoice System</div>
                    <div class="header-title">e-Invoice</div>
                    <div style="font-size: 10px; font-weight: bold;">TAX INVOICE</div>
                </td>
                <td style="width: 20%; text-align: right; vertical-align: bottom; font-size: 9px;">
                    Date: {{ $invoice->invoice_date->format('d/m/Y') }}
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
                    <div><span class="bold">Name:</span> {{ $customer->name }}</div>
                    <div><span class="bold">Address:</span> {{ $customer->address ?: '-' }}</div>
                    <div><span class="bold">State:</span> {{ $customer->state ?: '-' }} &nbsp;&nbsp; <span class="bold">Pin Code:</span> {{ $customer->pincode ?: '-' }}</div>
                    <div><span class="bold">GSTIN/Unique ID:</span> {{ $customer->gstin ?: '-' }}</div>
                </td>
                <td>
                    <div><span class="bold">Name:</span> {{ $customer->shipping_name ?: $customer->name }}</div>
                    <div><span class="bold">Address:</span> {{ $customer->shipping_address ?: ($customer->address ?: '-') }}</div>
                    <div><span class="bold">State:</span> {{ $customer->shipping_state ?: ($customer->state ?: '-') }} &nbsp;&nbsp; <span class="bold">Pin Code:</span> {{ $customer->shipping_pincode ?: ($customer->pincode ?: '-') }}</div>
                    <div><span class="bold">GSTIN/Unique ID:</span> {{ $customer->shipping_gstin ?: ($customer->gstin ?: '-') }}</div>
                </td>
            </tr>
        </table>

        <div style="font-size: 9px; margin-bottom: 6px;">
            <span class="bold">Supply type:</span> Outward &nbsp;&nbsp;&nbsp;&nbsp;
            <span class="bold">Transaction mode:</span> Tax Invoice
        </div>

        <table class="grid-table">
            <thead>
                <tr class="bg-gray text-center">
                    <th style="width: 4%;">S.No</th>
                    <th style="width: 24%;">Description of supply / Item description</th>
                    <th style="width: 8%;">HSN Code</th>
                    <th style="width: 8%;">Qty</th>
                    <th style="width: 10%;">Rate per unit</th>
                    <th style="width: 10%;">Taxable Value</th>
                    <th style="width: 12%;">CGST</th>
                    <th style="width: 12%;">SGST</th>
                    <th style="width: 12%;">Total (₹)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $idx => $item)
                    @php
                        $taxRate = (float) ($item->product->tax_rate ?? 18);
                        $halfTax = $taxRate / 2;
                        $lineTaxable = (float) $item->line_total;
                        $cgstAmount = $lineTaxable * ($halfTax / 100);
                        $sgstAmount = $lineTaxable * ($halfTax / 100);
                        $lineFinal = $lineTaxable + $cgstAmount + $sgstAmount;
                    @endphp
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td>{{ $item->product->name }}</td>
                        <td class="text-center">{{ $item->product->hsn_code ?: '-' }}</td>
                        <td class="text-right">{{ number_format($item->quantity, 2) }} {{ $item->uom->code }}</td>
                        <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-right">{{ number_format($lineTaxable, 2) }}</td>
                        <td class="text-right">{{ number_format($halfTax, 2) }}% ({{ number_format($cgstAmount, 2) }})</td>
                        <td class="text-right">{{ number_format($halfTax, 2) }}% ({{ number_format($sgstAmount, 2) }})</td>
                        <td class="text-right bold">{{ number_format($lineFinal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-gray">
                    <td colspan="5" class="text-right bold">Total:</td>
                    <td class="text-right bold">₹{{ number_format($invoice->subtotal, 2) }}</td>
                    <td colspan="2" class="text-right bold">Tax: ₹{{ number_format($invoice->tax_amount, 2) }}</td>
                    <td class="text-right bold">₹{{ number_format($invoice->grand_total, 2) }}</td>
                </tr>
            </tfoot>
        </table>

        <table class="grid-table">
            <tr>
                <td style="width: 55%;">
                    <div class="bold" style="border-bottom: 1px solid #ccc; padding-bottom: 2px; margin-bottom: 4px;">Payee Information</div>
                    <div><span class="bold">Payee name:</span> {{ $companyName }}</div>
                    <div><span class="bold">Account number:</span> {{ config('app.company_bank_account', '-') }}</div>
                    <div><span class="bold">Bank Name & IFSC:</span> {{ config('app.company_bank_details', '-') }}</div>
                    <div><span class="bold">Payment mode:</span> UPI / NEFT / RTGS</div>
                </td>
                <td style="width: 45%;">
                    <table style="width: 100%; border: none;">
                        <tr><td style="border:none;" class="bold">Total Taxable Amount:</td><td style="border:none;" class="text-right">₹{{ number_format($invoice->subtotal, 2) }}</td></tr>
                        <tr><td style="border:none;" class="bold">Total Tax Amount:</td><td style="border:none;" class="text-right">₹{{ number_format($invoice->tax_amount, 2) }}</td></tr>
                        <tr style="border-top: 1px solid #000;"><td style="border:none;" class="bold">Final Amount:</td><td style="border:none;" class="text-right bold">₹{{ number_format($invoice->grand_total, 2) }}</td></tr>
                        <tr><td style="border:none;" class="bold">Amount Paid:</td><td style="border:none;" class="text-right">₹{{ number_format($invoice->paid_amount, 2) }}</td></tr>
                        <tr><td style="border:none;" class="bold">Amount Outstanding:</td><td style="border:none;" class="text-right bold">₹{{ number_format(max(0, $invoice->grand_total - $invoice->paid_amount), 2) }}</td></tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
