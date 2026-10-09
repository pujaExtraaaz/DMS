<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 16mm 12mm 18mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a2332; }
        h1 { font-size: 18px; margin: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border-bottom: 1px solid #d3d7e0; padding: 4px; text-align: left; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        .money { text-align: right; }
        .muted { color: #5c6778; }
    </style>
</head>
<body>
    <p class="muted">{{ $voucher->company->legal_name ?: $voucher->company->name }} · {{ $voucher->branch->name }} · {{ $voucher->financialYear->name }}</p>
    @if ($voucher->company->gstin)
        <p>GSTIN {{ $voucher->company->gstin }}</p>
    @endif
    <h1>{{ $voucher->voucher_type->label() }} {{ $voucher->voucher_number }}</h1>
    <p>{{ $voucher->voucher_date->format($dateFormat) }} · {{ $voucher->status->label() }}</p>
    <p>Reference: {{ $voucher->reference_number ?: '—' }}</p>
    <p>Narration: {{ $voucher->narration ?: '—' }}</p>
    <table>
        <thead>
            <tr>
                <th>Ledger</th>
                <th class="money">Debit</th>
                <th class="money">Credit</th>
                <th>Narration</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($voucher->entries as $entry)
                <tr>
                    <td>{{ $entry->ledger->name }}</td>
                    <td class="money">{{ \Tally\Support\IndianCurrency::format($entry->debit) }}</td>
                    <td class="money">{{ \Tally\Support\IndianCurrency::format($entry->credit) }}</td>
                    <td>{{ $entry->narration ?: '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p>Amount in words: {{ \Tally\Support\IndianCurrency::words($voucher->total_debit) }}</p>
    <script type="text/php">
        if (isset($pdf)) {
            $pdf->page_text(480, 810, "Page {PAGE_NUM} of {PAGE_COUNT}", null, 9, [0.35, 0.4, 0.47]);
        }
    </script>
</body>
</html>
