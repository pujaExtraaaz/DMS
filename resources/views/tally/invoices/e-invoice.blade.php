<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>E-Invoice - {{ $number }}</title>
    <style>
        @page { size: A4; margin: 8mm; }
        html, body { margin: 0; padding: 0; background: #f3f4f6; font-family: DejaVu Sans, Helvetica, Arial, sans-serif; font-size: 10px; color: #111; line-height: 1.5; }
        body { padding: 20px 16px; }
        .toolbar { max-width: 1000px; margin: 0 auto 12px; display: flex; justify-content: space-between; gap: 8px; }
        .toolbar a, .toolbar button { border: 1px solid #d1d5db; background: #fff; color: #111827; padding: 8px 14px; border-radius: 6px; font-size: 12.5px; text-decoration: none; cursor: pointer; }
        .toolbar .primary { background: #4f46e5; border-color: #4f46e5; color: #fff; }
        .sheet { max-width: 1000px; margin: 0 auto 30px; background: #fff; border: 1px solid #111; padding: 14px; box-sizing: border-box; }
        .sub { text-align: right; color: #374151; margin-bottom: 8px; font-size: 9px; }
        .badge { border: 1px solid #000; padding: 4px 8px; font-weight: bold; display: inline-block; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        td, th { border: 1px solid #111; padding: 6px 7px; vertical-align: top; }
        .plain td { border: 0; padding: 0 0 8px; }
        .bg { background: #f3f4f6; font-weight: bold; }
        .right { text-align: right; }
        .center { text-align: center; }
        .title { font-size: 22px; font-weight: bold; text-align: center; text-transform: uppercase; margin: 6px 0; }
        .pi-title { font-size: 22px; font-weight: bold; text-align: right; text-transform: uppercase; }
        @media print { .toolbar { display: none; } body { background: #fff; padding: 0; } .sheet { max-width: none; border: none; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="{{ $back }}">Back to invoice</a>
        <button class="primary" type="button" onclick="window.print()">Print</button>
    </div>

    @if ($purchase)
        <div class="sheet">
            <table class="plain">
                <tr>
                    <td style="width: 58%;">
                        <div style="font-size: 16px; font-weight: bold;">{{ $buyer['name'] }}</div>
                        <div>GSTIN: {{ $buyer['gstin'] }}</div>
                        <div>{{ $buyer['address'] }}</div>
                        <div>{{ $buyer['state'] }} @if ($buyer['pincode'] !== '—') — {{ $buyer['pincode'] }} @endif</div>
                    </td>
                    <td style="width: 42%; text-align: right;">
                        <div class="pi-title">Purchase Invoice</div>
                        <div>Invoice No: {{ $number }}</div>
                        <div>Date: {{ $date }}</div>
                        @if ($reference)<div>Supplier Inv No: {{ $reference }}</div>@endif
                        <div>IRN: {{ $irn }}</div>
                    </td>
                </tr>
            </table>
            <table>
                <tr>
                    <td style="width: 50%;">
                        <div class="bg" style="margin: -6px -7px 6px; padding: 6px 7px;">Vendor / Supplier</div>
                        <div style="font-weight: bold;">{{ $seller['name'] }}</div>
                        <div>GSTIN: {{ $seller['gstin'] }}</div>
                        <div>{{ $seller['address'] }}</div>
                        <div>{{ $seller['state'] }}</div>
                        @if ($seller['phone'])<div>Phone: {{ $seller['phone'] }}</div>@endif
                    </td>
                    <td style="width: 50%;">
                        <div class="bg" style="margin: -6px -7px 6px; padding: 6px 7px;">Billed To</div>
                        <div style="font-weight: bold;">{{ $buyer['name'] }}</div>
                        <div>GSTIN: {{ $buyer['gstin'] }}</div>
                        <div>{{ $buyer['address'] }}</div>
                        <div>{{ $buyer['state'] }} @if ($buyer['pincode'] !== '—') — {{ $buyer['pincode'] }} @endif</div>
                    </td>
                </tr>
            </table>
    @else
        <div class="sheet">
            <div class="sub"><span class="badge">Original For Recipient</span> &nbsp; Schema version: 1.0 &nbsp;|&nbsp; Tax scheme: GST</div>
            <div style="font-size: 9px; color: #4b5563; text-align: center;">e-Invoice System</div>
            <div class="title">e-Invoice</div>
            <div class="center" style="font-weight: bold; margin-bottom: 10px;">{{ strtoupper($documentTitle) }}</div>
            <table>
                <tr>
                    <td style="width: 50%;">
                        <div><strong>1. GSTIN:</strong> {{ $seller['gstin'] }}</div>
                        <div><strong>2. Name:</strong> {{ $seller['name'] }}</div>
                        <div><strong>3. Address:</strong> {{ $seller['address'] }}</div>
                        <div><strong>4. Serial No. of Invoice:</strong> {{ $number }}</div>
                        <div><strong>5. Date of Invoice:</strong> {{ $date }}</div>
                        <div><strong>6. IRN No.:</strong> {{ $irn }}</div>
                    </td>
                    <td style="width: 50%;">
                        <div><strong>Dispatch from:</strong> {{ $seller['name'] }} ({{ $seller['gstin'] }})</div>
                        <div><strong>Address:</strong> {{ $seller['address'] }}</div>
                        <div><strong>State:</strong> {{ $seller['state'] }} &nbsp;&nbsp; <strong>Pincode:</strong> {{ $seller['pincode'] }}</div>
                        @if ($reference)<div><strong>Reference:</strong> {{ $reference }}</div>@endif
                    </td>
                </tr>
            </table>
            <table>
                <tr class="bg">
                    <th style="width: 50%;">Details Of Receiver (Billed To)</th>
                    <th style="width: 50%;">Details Of Consignee (Shipped To)</th>
                </tr>
                <tr>
                    <td>
                        <div><strong>Name:</strong> {{ $buyer['name'] }}</div>
                        <div><strong>Address:</strong> {{ $buyer['address'] }}</div>
                        <div><strong>State:</strong> {{ $buyer['state'] }}</div>
                        <div><strong>GSTIN:</strong> {{ $buyer['gstin'] }}</div>
                    </td>
                    <td>
                        <div><strong>Name:</strong> {{ $buyer['name'] }}</div>
                        <div><strong>Address:</strong> {{ $buyer['address'] }}</div>
                        <div><strong>State:</strong> {{ $buyer['state'] }}</div>
                        <div><strong>GSTIN:</strong> {{ $buyer['gstin'] }}</div>
                    </td>
                </tr>
            </table>
    @endif

            <table>
                <tr class="bg">
                    <th style="width: 4%;">#</th>
                    <th>Item Description</th>
                    <th>HSN</th>
                    <th class="right">Qty</th>
                    <th class="right">Unit Price</th>
                    <th class="right">Discount</th>
                    <th class="right">Taxable</th>
                    <th class="right">GST Rate</th>
                    <th class="right">Total</th>
                </tr>
                @foreach ($lines as $index => $line)
                    <tr>
                        <td class="center">{{ $index + 1 }}</td>
                        <td>{{ $line['name'] }}</td>
                        <td>{{ $line['hsn'] }}</td>
                        <td class="right">{{ $line['qty'] }}</td>
                        <td class="right">{{ $line['rate'] }}</td>
                        <td class="right">{{ $line['discount'] }}</td>
                        <td class="right">{{ $line['taxable'] }}</td>
                        <td class="right">{{ $line['gst'] }}%</td>
                        <td class="right"><strong>{{ $line['total'] }}</strong></td>
                    </tr>
                @endforeach
            </table>
            <table>
                <tr class="bg">
                    <th class="right">Taxable Amount</th>
                    <th class="right">CGST</th>
                    <th class="right">SGST</th>
                    <th class="right">IGST</th>
                    <th class="right">Invoice Total</th>
                </tr>
                <tr>
                    <td class="right">{{ $subtotal }}</td>
                    <td class="right">{{ $cgst }}</td>
                    <td class="right">{{ $sgst }}</td>
                    <td class="right">{{ $igst }}</td>
                    <td class="right"><strong>{{ $currencyName ?? 'INR' }} {{ $grand }}</strong></td>
                </tr>
            </table>
            @if ($words)
                <div style="margin-bottom: 12px;"><strong>Amount in Words:</strong> {{ $words }}</div>
            @endif
            @if ($narration)<div style="margin-bottom: 12px;"><strong>Narration:</strong> {{ $narration }}</div>@endif
            <table>
                <tr>
                    <td style="width: 50%;">
                        @if ($terms)
                            <div><strong>Terms &amp; Conditions</strong></div>
                            <div>{{ $terms }}</div>
                        @endif
                    </td>
                    <td style="width: 50%; text-align: right; vertical-align: bottom;">
                        <div style="margin-bottom: 36px;">For <strong>{{ $purchase ? $buyer['name'] : $seller['name'] }}</strong></div>
                        <div><strong>Authorized Signatory</strong></div>
                    </td>
                </tr>
            </table>
        </div>
</body>
</html>
