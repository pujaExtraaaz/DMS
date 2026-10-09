<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $product->name }} barcodes</title>
    <style>
        body { font-family: "Segoe UI", sans-serif; margin: 24px; color: #1a2332; }
        .label { display: inline-block; border: 1px solid #d3d7e0; padding: 12px; margin: 8px; page-break-inside: avoid; }
        h1 { font-size: 16px; margin: 0 0 8px; }
        .actions { margin-bottom: 16px; }
        @media print { .actions { display: none; } }
    </style>
</head>
<body>
    <div class="actions"><button type="button" onclick="window.print()">Print</button></div>
    <h1>{{ $product->name }} · {{ $product->code }}</h1>
    @forelse ($codes as $code)
        <section class="label">{!! $code['svg'] !!}</section>
    @empty
        <p>This product has no barcode yet.</p>
    @endforelse
</body>
</html>
