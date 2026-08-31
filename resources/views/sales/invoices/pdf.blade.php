<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>{{ $invoice->invoice_no }}</title>
<style>body{font-family:sans-serif;padding:40px;color:#111}table{width:100%;border-collapse:collapse;margin-top:20px}th,td{border:1px solid #ddd;padding:8px;text-align:left}th{background:#f5f5f5}.right{text-align:right}.total{font-weight:bold;font-size:1.1em}</style></head>
<body>
<h1>Tax Invoice</h1>
<p><strong>{{ $invoice->invoice_no }}</strong> · {{ $invoice->invoice_date->format('d M Y') }}</p>
<p><strong>{{ $invoice->customer->name }}</strong><br>{{ $invoice->customer->address }}<br>GSTIN: {{ $invoice->customer->gstin ?? '—' }}</p>
<table><thead><tr><th>Product</th><th>UOM</th><th class="right">Qty</th><th class="right">Rate</th><th class="right">Amount</th></tr></thead><tbody>
@foreach($invoice->items as $item)<tr><td>{{ $item->product->name }}</td><td>{{ $item->uom->code }}</td><td class="right">{{ $item->quantity }}</td><td class="right">{{ number_format($item->unit_price, 2) }}</td><td class="right">{{ number_format($item->line_total, 2) }}</td></tr>@endforeach
</tbody></table>
<p class="right total">Grand Total: ₹{{ number_format($invoice->grand_total, 2) }}</p>
</body></html>
