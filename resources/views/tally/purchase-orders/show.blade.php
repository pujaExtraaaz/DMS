<x-tally::layouts.app :title="$order->number">
    <x-tally::shell.page :title="$order->number" section="Transactions" :description="'Purchase order · '.$order->order_date->format('d M Y')">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ tally_route('books.tally.purchase-orders.print', $order) }}" target="_blank">Print</a>
            <a class="btn" href="{{ tally_route('books.tally.purchase-orders.pdf', $order) }}">Download PDF</a>
            <a class="btn" href="{{ tally_route('books.tally.purchase-orders.index') }}">Back</a>
        </x-slot:actions>
        <dl class="sheet">
            <div><dt>Supplier</dt><dd>{{ $order->supplier->name ?? '—' }}</dd></div>
            <div><dt>GSTIN</dt><dd>{{ $order->supplier->gstin ?: '—' }}</dd></div>
            <div><dt>Reference</dt><dd>{{ $order->reference_number ?: '—' }}</dd></div>
            <div><dt>Total</dt><dd>{{ $order->grand_total }}</dd></div>
            <div><dt>Narration</dt><dd>{{ $order->narration ?: '—' }}</dd></div>
        </dl>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="money">Qty</th>
                        <th class="money">Rate</th>
                        <th class="money">Discount</th>
                        <th class="money">Tax</th>
                        <th class="money">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->lines as $line)
                        <tr>
                            <td>{{ $line->item_name }}</td>
                            <td class="money">{{ $line->quantity }}</td>
                            <td class="money">{{ $line->rate }}</td>
                            <td class="money">{{ $line->discount }}</td>
                            <td class="money">{{ $line->tax_amount }}</td>
                            <td class="money">{{ $line->line_total }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="form-note">A purchase order does not move stock and does not post to the accounts. Print uses the company purchase terms.</p>
    </x-shell.page>
</x-layouts.app>
