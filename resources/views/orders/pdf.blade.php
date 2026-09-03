<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">

    <title>Orders</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #111827;
        }

        h1 {
            font-size: 16px;
            margin-bottom: 4px;
        }

        p {
            margin: 0 0 12px;
            color: #6b7280;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 5px;
        }

        th {
            background: #f3f4f6;
            font-weight: bold;
            text-align: left;
        }

        .right {
            text-align: right;
        }
    </style>
</head>

<body>
    <h1>Orders</h1>

    <p>
        Generated:
        {{ now()->format('d M Y H:i') }}
    </p>

    <table>
        <thead>
            <tr>
                <th>Order</th>
                <th>Date</th>
                <th>Customer</th>
                <th>Salesperson</th>
                <th>Status</th>
                <th class="right">Subtotal</th>
                <th class="right">Discount</th>
                <th class="right">Tax</th>
                <th class="right">Total</th>
            </tr>
        </thead>

        <tbody>
            @foreach($orders as $order)
                <tr>
                    <td>{{ $order->order_no }}</td>
                    <td>{{ $order->order_date?->format('d M Y') }}</td>
                    <td>
                        {{ $order->customer?->name }}
                        ({{ $order->customer?->code }})
                    </td>
                    <td>{{ $order->salesperson?->name ?? '—' }}</td>
                    <td>{{ ucfirst($order->status) }}</td>
                    <td class="right">
                        ₹{{ number_format($order->subtotal, 2) }}
                    </td>
                    <td class="right">
                        ₹{{ number_format($order->discount_amount, 2) }}
                    </td>
                    <td class="right">
                        ₹{{ number_format($order->tax_amount, 2) }}
                    </td>
                    <td class="right">
                        ₹{{ number_format($order->grand_total, 2) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>