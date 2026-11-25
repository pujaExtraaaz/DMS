<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Order - {{ $order->order_no }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm;
        }
        * { box-sizing: border-box; }
        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 10px;
            color: #1e293b;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .title {
            font-size: 18px;
            font-weight: bold;
            color: #4f46e5;
            text-transform: uppercase;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
            background: #eef2ff;
            color: #4338ca;
        }
        .grid-2 {
            width: 100%;
            margin-bottom: 12px;
        }
        .grid-2 td {
            vertical-align: top;
            width: 50%;
        }
        .box {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 10px;
            background: #f8fafc;
            min-height: 100px;
        }
        .box-title {
            font-size: 10px;
            font-weight: bold;
            color: #475569;
            text-transform: uppercase;
            margin-bottom: 4px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 2px;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 12px;
        }
        table.items th {
            background: #1e293b;
            color: #ffffff;
            font-weight: bold;
            font-size: 9px;
            padding: 6px 8px;
            text-align: left;
            border: 1px solid #1e293b;
        }
        table.items td {
            padding: 6px 8px;
            border: 1px solid #cbd5e1;
            font-size: 9.5px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totals-table {
            width: 45%;
            margin-left: auto;
            border-collapse: collapse;
            margin-top: 8px;
        }
        .totals-table td {
            padding: 4px 8px;
            font-size: 10px;
        }
        .totals-table tr.grand-total td {
            border-top: 2px solid #4f46e5;
            border-bottom: 2px solid #4f46e5;
            font-weight: bold;
            font-size: 11px;
            color: #4f46e5;
            background: #f8fafc;
        }
        .words {
            margin-top: 10px;
            padding: 6px 10px;
            background: #f1f5f9;
            border-radius: 4px;
            font-style: italic;
            font-size: 9.5px;
        }
        .footer {
            margin-top: 30px;
            width: 100%;
        }
        .footer td {
            width: 50%;
            vertical-align: bottom;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td>
                <div class="title">SALES ORDER</div>
                <div style="font-size: 12px; font-weight: bold; margin-top: 2px;"># {{ $order->order_no }}</div>
                <div style="color: #64748b; font-size: 9px; margin-top: 2px;">
                    Date: {{ $order->order_date?->format('d M Y') ?? '—' }} |
                    Status: <span class="badge">{{ ucfirst($order->status) }}</span>
                </div>
            </td>
            <td class="text-right">
                <div style="font-size: 14px; font-weight: bold;">{{ $company?->name ?? 'Avit Digital' }}</div>
                <div style="color: #64748b; font-size: 9px;">
                    @if($company?->gstin) GSTIN: {{ $company->gstin }}<br> @endif
                    @if($company?->phone) Phone: {{ $company->phone }}<br> @endif
                    @if($company?->email) Email: {{ $company->email }} @endif
                </div>
            </td>
        </tr>
    </table>

    <table class="grid-2">
        <tr>
            <td style="padding-right: 6px;">
                <div class="box">
                    <div class="box-title">Buyer / Customer Details</div>
                    <div style="font-weight: bold; font-size: 11px;">{{ $order->customer?->name }}</div>
                    @if($order->customer?->code)<div><span style="color: #64748b;">Code:</span> {{ $order->customer->code }}</div>@endif
                    @if($order->customer?->gstin)<div><span style="color: #64748b;">GSTIN:</span> {{ $order->customer->gstin }}</div>@endif
                    @if($order->customer?->phone)<div><span style="color: #64748b;">Phone:</span> {{ $order->customer->phone }}</div>@endif
                    <div style="margin-top: 4px;"><span style="color: #64748b;">Billing Address:</span> {{ $order->billing_address ?: ($order->customer?->address ?: '—') }}</div>
                </div>
            </td>
            <td style="padding-left: 6px;">
                <div class="box">
                    <div class="box-title">Order & Delivery Information</div>
                    <div><span style="color: #64748b;">Due Date:</span> {{ $order->due_date?->format('d M Y') ?? '—' }}</div>
                    <div><span style="color: #64748b;">Salesperson:</span> {{ $order->salesperson?->name ?? ($order->salesperson_name ?? '—') }}</div>
                    <div><span style="color: #64748b;">Warehouse:</span> {{ $order->warehouse?->name ?? '—' }}</div>
                    <div><span style="color: #64748b;">Payment Terms:</span> {{ $order->payment_terms ?: '—' }}</div>
                    @if($order->terms_of_delivery)<div><span style="color: #64748b;">Terms of Delivery:</span> {{ $order->terms_of_delivery }}</div>@endif
                    <div style="margin-top: 4px;"><span style="color: #64748b;">Delivery Address:</span> {{ $order->shipping_address ?: ($order->billing_address ?: ($order->customer?->address ?: '—')) }}</div>
                </div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th class="text-center" style="width: 30px;">#</th>
                <th>Item Description</th>
                <th>Brand / SKU</th>
                <th class="text-center" style="width: 50px;">UOM</th>
                <th class="text-right" style="width: 60px;">Ordered Qty</th>
                <th class="text-right" style="width: 60px;">Delivered</th>
                <th class="text-right" style="width: 75px;">Unit Price (₹)</th>
                <th class="text-right" style="width: 80px;">Line Total (₹)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($order->items as $idx => $item)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td>
                        <div style="font-weight: bold;">{{ $item->product?->name ?? '—' }}</div>
                        @if($item->product?->hsn_code)<div style="color: #64748b; font-size: 8.5px;">HSN: {{ $item->product->hsn_code }}</div>@endif
                    </td>
                    <td>
                        <div>{{ $item->product?->brand?->name ?? '—' }}</div>
                        <div style="color: #64748b; font-size: 8.5px;">{{ $item->product?->sku ?? '' }}</div>
                    </td>
                    <td class="text-center">{{ $item->uom?->code ?? ($item->product?->baseUom?->code ?? '—') }}</td>
                    <td class="text-right">{{ number_format((float) $item->quantity, 2) }}</td>
                    <td class="text-right">{{ number_format((float) $item->delivered_qty, 2) }}</td>
                    <td class="text-right">₹{{ number_format((float) $item->unit_price, 2) }}</td>
                    <td class="text-right" style="font-weight: bold;">₹{{ number_format((float) $item->line_total, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 12px; color: #94a3b8;">No line items recorded for this order.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table style="width: 100%;">
        <tr>
            <td style="vertical-align: top; width: 55%; padding-right: 14px;">
                @if($order->notes)
                    <div style="margin-bottom: 8px;">
                        <span style="font-weight: bold; color: #475569;">Special Instructions / Notes:</span>
                        <div style="font-size: 9.5px; color: #334155; margin-top: 2px;">{{ $order->notes }}</div>
                    </div>
                @endif
                <div class="words">
                    <strong>Amount in Words:</strong> {{ \App\Support\DocumentExporter::numberToIndianWords($order->grand_total) }}
                </div>
            </td>
            <td style="vertical-align: top; width: 45%;">
                <table class="totals-table">
                    <tr>
                        <td>Subtotal (Taxable):</td>
                        <td class="text-right">₹{{ number_format((float) $order->subtotal, 2) }}</td>
                    </tr>
                    @if((float)$order->discount_amount > 0)
                        <tr>
                            <td>Discount:</td>
                            <td class="text-right">-₹{{ number_format((float) $order->discount_amount, 2) }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td>Tax (GST):</td>
                        <td class="text-right">₹{{ number_format((float) $order->tax_amount, 2) }}</td>
                    </tr>
                    <tr class="grand-total">
                        <td>Grand Total:</td>
                        <td class="text-right">₹{{ number_format((float) $order->grand_total, 2) }}</td>
                    </tr>
                    @if((float)$order->advance_amount > 0)
                        <tr>
                            <td>Advance Paid:</td>
                            <td class="text-right">₹{{ number_format((float) $order->advance_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; color: #b91c1c;">Balance Due:</td>
                            <td class="text-right" style="font-weight: bold; color: #b91c1c;">₹{{ number_format((float) ($order->balance_amount ?? ($order->grand_total - $order->advance_amount)), 2) }}</td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <table class="footer">
        <tr>
            <td>
                <div style="font-size: 9px; color: #64748b;">Terms: Subject to terms and conditions agreed.</div>
            </td>
            <td class="text-right">
                <div style="margin-top: 40px; border-top: 1px solid #cbd5e1; display: inline-block; padding-top: 4px; min-width: 140px; text-align: center;">
                    Authorized Signatory
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
