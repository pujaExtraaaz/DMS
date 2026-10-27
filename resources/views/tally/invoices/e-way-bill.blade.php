<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>E-Way Bill - {{ $number }}</title>
    <style>
        body { margin: 0; background: #f3f4f6; font-family: DejaVu Sans, Helvetica, Arial, sans-serif; color: #0f172a; }
        .toolbar { max-width: 56rem; margin: 20px auto 12px; display: flex; justify-content: space-between; }
        .toolbar a, .toolbar button { border: 1px solid #d1d5db; background: #fff; color: #111827; padding: 8px 14px; border-radius: 6px; font-size: 12.5px; text-decoration: none; cursor: pointer; }
        .toolbar .primary { background: #4f46e5; border-color: #4f46e5; color: #fff; }
        .sheet { max-width: 56rem; margin: 0 auto 30px; background: #fff; border: 1px solid #cbd5e1; padding: 32px; font-size: 14px; }
        .head { display: flex; justify-content: space-between; gap: 16px; border-bottom: 2px solid #0f172a; padding-bottom: 16px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; padding: 20px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 8px 12px; text-align: left; }
        th { background: #f8fafc; }
        .right { text-align: right; }
        .total { margin-top: 20px; text-align: right; font-weight: 700; }
        @media print { .toolbar { display: none; } body { background: #fff; } .sheet { border: none; max-width: none; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="{{ $back }}">Back to invoice</a>
        <button class="primary" type="button" onclick="window.print()">Print</button>
    </div>
    <div class="sheet">
        <div class="head">
            <div>
                <p style="font-size: 18px; font-weight: 700; margin: 0;">E-Way Bill</p>
                <p style="margin: 4px 0 0; color: #475569;">Generated against {{ $documentTitle }} {{ $number }}</p>
            </div>
            <div style="text-align: right;">
                <p style="margin: 0; font-weight: 600;">E-Way Bill No.</p>
                <p style="margin: 4px 0 0;">{{ $ewayNo }}</p>
                <p style="margin: 4px 0 0; font-size: 12px; color: #64748b;">{{ $date }}</p>
            </div>
        </div>
        <div class="grid">
            <div>
                <p style="font-weight: 700; margin: 0 0 8px;">From</p>
                <p style="margin: 0;">{{ $seller['name'] }}</p>
                <p style="margin: 4px 0 0;">{{ $seller['address'] }}</p>
                <p style="margin: 4px 0 0;">{{ $seller['state'] }}</p>
                <p style="margin: 4px 0 0;">GSTIN: {{ $seller['gstin'] }}</p>
            </div>
            <div>
                <p style="font-weight: 700; margin: 0 0 8px;">To</p>
                <p style="margin: 0;">{{ $buyer['name'] }}</p>
                <p style="margin: 4px 0 0;">{{ $buyer['address'] }}</p>
                <p style="margin: 4px 0 0;">{{ $buyer['state'] }}</p>
                <p style="margin: 4px 0 0;">GSTIN: {{ $buyer['gstin'] }}</p>
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>HSN</th>
                    <th class="right">Quantity</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($lines as $line)
                    <tr>
                        <td>{{ $line['name'] }}</td>
                        <td>{{ $line['hsn'] }}</td>
                        <td class="right">{{ $line['qty'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="total">Consignment Value: {{ $currencySymbol ?? '₹' }}{{ $grand }}</div>
    </div>
</body>
</html>
