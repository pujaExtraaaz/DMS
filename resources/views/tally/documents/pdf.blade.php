<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 16mm 12mm 18mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a2332; }
        h1 { font-size: 18px; margin: 0; }
        h2 { font-size: 12px; margin: 14px 0 4px; }
        p { margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border-bottom: 1px solid #d3d7e0; text-align: left; padding: 4px; vertical-align: top; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        .money { text-align: right; }
        .muted { color: #5c6778; }
        .grand td { font-weight: bold; }
    </style>
</head>
<body>
    <p class="muted">{{ $document['company'] }}@if ($document['branch']) · {{ $document['branch'] }}@endif</p>
    @foreach ($document['address'] as $line)
        <p class="muted">{{ $line }}</p>
    @endforeach
    @if ($document['gstin'])
        <p>GSTIN {{ $document['gstin'] }}</p>
    @endif
    <h1>{{ $document['title'] }} {{ $document['number'] }}</h1>
    <p>{{ $document['date'] }}</p>
    <p><strong>{{ $document['party_label'] }}</strong> {{ $document['party'] ?: '—' }}
        @if ($document['party_gstin']) · GSTIN {{ $document['party_gstin'] }}@endif
        @if ($document['party_state']) · {{ $document['party_state'] }}@endif
    </p>
    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>HSN</th>
                <th class="money">Qty</th>
                <th class="money">Rate</th>
                <th class="money">Discount</th>
                <th class="money">Taxable</th>
                <th class="money">CGST</th>
                <th class="money">SGST</th>
                <th class="money">IGST</th>
                <th class="money">Cess</th>
                <th class="money">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($document['lines'] as $line)
                <tr>
                    <td>{{ $line['item'] }}</td>
                    <td>{{ $line['hsn'] ?: '—' }}</td>
                    <td class="money">{{ $line['quantity'] }}</td>
                    <td class="money">{{ $line['rate'] }}</td>
                    <td class="money">{{ $line['discount'] }}</td>
                    <td class="money">{{ $line['taxable'] }}</td>
                    <td class="money">{{ $line['cgst'] }}</td>
                    <td class="money">{{ $line['sgst'] }}</td>
                    <td class="money">{{ $line['igst'] }}</td>
                    <td class="money">{{ $line['cess'] }}</td>
                    <td class="money">{{ $line['amount'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <table>
        <tr><td>Subtotal</td><td class="money">{{ $document['subtotal'] }}</td></tr>
        <tr><td>Discount</td><td class="money">{{ $document['discount'] }}</td></tr>
        <tr><td>Tax</td><td class="money">{{ $document['tax'] }}</td></tr>
        <tr class="grand"><td>Total</td><td class="money">{{ $document['grand'] }}</td></tr>
    </table>
    @if (! empty($document['words']))
        <p>Amount in words: {{ $document['words'] }}</p>
    @endif
    @if ($document['terms'])
        <h2>Terms</h2>
        <p>{{ $document['terms'] }}</p>
    @endif
    <script type="text/php">
        if (isset($pdf)) {
            $pdf->page_text(480, 810, "Page {PAGE_NUM} of {PAGE_COUNT}", null, 9, [0.35, 0.4, 0.47]);
        }
    </script>
</body>
</html>
