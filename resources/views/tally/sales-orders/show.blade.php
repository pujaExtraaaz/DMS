<x-tally::layouts.app :title="$order->number">
    <x-tally::shell.page :title="$order->number" section="Transactions" :description="ucfirst($order->status)">
        <x-slot:actions>
            <a class="btn" href="{{ tally_route('books.tally.sales-orders.print', $order) }}">Print</a>
            <a class="btn" href="{{ tally_route('books.tally.sales-orders.pdf', $order) }}">PDF</a>
            <a class="btn" href="{{ tally_route('books.tally.sales-orders.edit', $order) }}">Alter</a>
            @if ($order->status === 'pending')
                <a class="btn btn-primary" href="{{ tally_route('books.tally.invoices.sales.create', ['sales_order' => $order->id]) }}">Sales Invoice</a>
            @endif
        </x-slot:actions>
        <dl class="kv">
            <dt>Customer</dt><dd>{{ $order->customer?->name }}</dd>
            <dt>Date</dt><dd>{{ $order->order_date->format('d M Y') }}</dd>
            <dt>Amount</dt><dd>{{ $order->grand_total }}</dd>
            <dt>Tax</dt><dd>{{ $order->tax_total }}</dd>
        </dl>
        <form method="POST" action="{{ tally_route('books.tally.sales-orders.status', $order) }}">
            @csrf
            @method('PATCH')
            <x-tally::form.field name="status" label="Status">
                <select id="status" name="status" class="input">
                    @foreach (['pending', 'fulfilled', 'cancelled'] as $status)
                        <option value="{{ $status }}" @selected($order->status === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </x-form.field>
            <button class="btn" type="submit">Update status</button>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Item</th><th class="money">Ordered</th><th class="money">Fulfilled</th><th class="money">Cancelled</th><th class="money">Pending</th><th class="money">Rate</th><th class="money">Amount</th></tr></thead>
                <tbody>
                    @foreach ($order->lines as $line)
                        <tr>
                            <td>{{ $line->item_name }}</td>
                            <td class="money">{{ $line->quantity }}</td>
                            <td class="money">{{ $line->fulfilled_quantity }}</td>
                            <td class="money">{{ $line->cancelled_quantity }}</td>
                            <td class="money">{{ number_format((float) $line->quantity - (float) $line->fulfilled_quantity - (float) $line->cancelled_quantity, 4, '.', '') }}</td>
                            <td class="money">{{ $line->rate }}</td>
                            <td class="money">{{ $line->line_total }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-shell.page>
</x-layouts.app>
