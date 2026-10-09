<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $order->number }}</title>
    <style>body{font-family:sans-serif;font-size:12px} table{width:100%;border-collapse:collapse} td,th{border:1px solid #ccc;padding:4px;text-align:left}</style>
</head>
<body>
    <h1>Sales order {{ $order->number }}</h1>
    <p>{{ $order->company?->name }} · {{ $order->customer?->name }} · {{ $order->order_date->format('d M Y') }} · {{ ucfirst($order->status) }}</p>
    <table>
        <thead><tr><th>Item</th><th>Qty</th><th>Rate</th><th>Amount</th></tr></thead>
        <tbody>
            @foreach ($order->lines as $line)
                <tr><td>{{ $line->item_name }}</td><td>{{ $line->quantity }}</td><td>{{ $line->rate }}</td><td>{{ $line->line_total }}</td></tr>
            @endforeach
        </tbody>
    </table>
    <p>Total {{ $order->grand_total }}</p>
</body>
</html>
