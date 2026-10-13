<x-tally::layouts.app :title="$receipt->number">
    <x-tally::shell.page :title="$receipt->number" section="Purchasing" :description="ucfirst($receipt->status)">
        <x-slot:actions>
            @if (in_array($receipt->status, ['draft', 'received'], true))
                <form method="POST" action="{{ tally_route('books.tally.goods-receipts.post', $receipt) }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">Post</button>
                </form>
            @endif
            @if ($receipt->status === 'posted')
                <form method="POST" action="{{ tally_route('books.tally.goods-receipts.create-bill', $receipt) }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">Create purchase bill</button>
                </form>
            @endif
            <a class="btn" href="{{ tally_route('books.tally.goods-receipts.index') }}">Back</a>
        </x-slot:actions>
        <dl class="kv">
            <dt>Supplier</dt><dd>{{ $receipt->supplier?->name }}</dd>
            <dt>Date</dt><dd>{{ $receipt->receipt_date->format('d M Y') }}</dd>
            @if ($receipt->purchaseOrder)
                <dt>PO</dt><dd>{{ $receipt->purchaseOrder->number }}</dd>
            @endif
            @if ($receipt->stockTransaction)
                <dt>Stock</dt><dd>{{ $receipt->stockTransaction->number }}</dd>
            @endif
        </dl>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Item</th><th class="money">Ordered</th><th class="money">Received</th><th class="money">Rate</th><th class="money">Amount</th></tr></thead>
                <tbody>
                    @foreach ($receipt->lines as $line)
                        <tr>
                            <td>{{ $line->item_name }}</td>
                            <td class="money">{{ $line->ordered_qty }}</td>
                            <td class="money">{{ $line->received_qty }}</td>
                            <td class="money">{{ $line->rate }}</td>
                            <td class="money">{{ $line->line_total }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-tally::shell.page>
</x-tally::layouts.app>
