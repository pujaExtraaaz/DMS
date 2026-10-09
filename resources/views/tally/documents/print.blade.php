<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ $document['title'] }} {{ $document['number'] }}</title>
    <style>
        @page { size: A4; margin: 14mm; }
        * { box-sizing: border-box; }
        body { font-family: "Segoe UI", sans-serif; color: #1a2332; margin: 0; font-size: 12px; line-height: 1.4; }
        h1 { font-size: 22px; margin: 0; letter-spacing: 0.02em; }
        h2 { font-size: 13px; margin: 0 0 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border-bottom: 1px solid #d3d7e0; text-align: left; padding: 6px 4px; vertical-align: top; }
        thead { display: table-header-group; }
        tr, .party, .totals, .terms { break-inside: avoid; page-break-inside: avoid; }
        th { font-size: 11px; text-transform: uppercase; letter-spacing: 0.03em; color: #5c6778; }
        .money { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .muted { color: #5c6778; }
        .head { display: flex; justify-content: space-between; gap: 24px; border-bottom: 2px solid #1a2332; padding-bottom: 12px; }
        .meta { margin-top: 16px; display: flex; justify-content: space-between; gap: 24px; }
        .party { min-width: 220px; }
        .totals { margin-top: 8px; margin-left: auto; width: 260px; }
        .totals td { border: 0; padding: 3px 0; }
        .grand td { border-top: 2px solid #1a2332; font-weight: 700; padding-top: 6px; }
        .terms { margin-top: 28px; }
        .actions { margin-top: 20px; }
        button, a.btn { font: inherit; padding: 6px 10px; }
        @media print {
            .actions { display: none !important; }
        }
    </style>
</head>
<body>
    <header class="head">
        <div>
            <h1>{{ $document['company'] }}</h1>
            @foreach ($document['address'] as $line)
                <div class="muted">{{ $line }}</div>
            @endforeach
            @if ($document['gstin'])
                <div>GSTIN {{ $document['gstin'] }}</div>
            @endif
            @if ($document['branch'])
                <div class="muted">{{ $document['branch'] }}</div>
            @endif
        </div>
        <div>
            <h2>{{ $document['title'] }}</h2>
            <div><strong>{{ $document['number'] }}</strong></div>
            <div>{{ $document['date'] }}</div>
            @if ($document['reference'])
                <div class="muted">Ref {{ $document['reference'] }}</div>
            @endif
            @if ($document['place'])
                <div class="muted">Place of supply {{ $document['place'] }}</div>
            @endif
        </div>
    </header>

    <section class="meta">
        <div class="party">
            <h2>{{ $document['party_label'] }}</h2>
            <div>{{ $document['party'] ?: '—' }}</div>
            @if ($document['party_gstin'])
                <div>GSTIN {{ $document['party_gstin'] }}</div>
            @endif
            @if ($document['party_state'])
                <div class="muted">{{ $document['party_state'] }}</div>
            @endif
        </div>
    </section>

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

    <table class="totals">
        <tr><td>Subtotal</td><td class="money">{{ $document['subtotal'] }}</td></tr>
        <tr><td>Discount</td><td class="money">{{ $document['discount'] }}</td></tr>
        @if (! empty($document['delivery']) && $document['delivery'] !== '0.00' && $document['delivery'] !== '0')
            <tr><td>Delivery charges</td><td class="money">{{ $document['delivery'] }}</td></tr>
        @endif
        @if (! empty($document['purchase_ledger']))
            <tr><td>Purchase ledger</td><td>{{ $document['purchase_ledger'] }}</td></tr>
        @endif
        <tr><td>Tax</td><td class="money">{{ $document['tax'] }}</td></tr>
        <tr class="grand"><td>Total</td><td class="money">{{ $document['grand'] }}</td></tr>
    </table>
    @if (! empty($document['words']))
        <p>Amount in words: {{ $document['words'] }}</p>
    @endif

    @if ($document['narration'])
        <p>{{ $document['narration'] }}</p>
    @endif

    @if ($document['terms'])
        <section class="terms">
            <h2>Terms</h2>
            <p>{{ $document['terms'] }}</p>
        </section>
    @endif

    <div class="actions">
        <button type="button" onclick="window.print()">Print</button>
        <a href="{{ $back }}">Back</a>
    </div>
</body>
</html>
