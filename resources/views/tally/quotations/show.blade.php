<x-tally::layouts.app :title="$quotation->number">
    <x-tally::shell.page :title="$quotation->number" section="Transactions" :description="ucfirst($quotation->status)">
        <x-slot:actions>
            <a class="btn" href="{{ tally_route('books.tally.quotations.edit', $quotation) }}">Edit</a>
            @if ($quotation->canConvert())
                <form method="POST" action="{{ tally_route('books.tally.quotations.convert', $quotation) }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">Convert to sales order</button>
                </form>
            @endif
        </x-slot:actions>
        <dl class="kv">
            <dt>Customer</dt><dd>{{ $quotation->customer?->name }}</dd>
            <dt>Date</dt><dd>{{ $quotation->quote_date->format('d M Y') }}</dd>
            <dt>Amount</dt><dd>{{ $quotation->grand_total }}</dd>
            @if ($quotation->salesOrder)
                <dt>Sales order</dt><dd><a href="{{ tally_route('books.tally.sales-orders.show', $quotation->salesOrder) }}">{{ $quotation->salesOrder->number }}</a></dd>
            @endif
        </dl>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Item</th><th class="money">Qty</th><th class="money">Rate</th><th class="money">Amount</th></tr></thead>
                <tbody>
                    @foreach ($quotation->lines as $line)
                        <tr>
                            <td>{{ $line->item_name }}</td>
                            <td class="money">{{ $line->quantity }}</td>
                            <td class="money">{{ $line->rate }}</td>
                            <td class="money">{{ $line->line_total }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-tally::shell.page>
</x-tally::layouts.app>
