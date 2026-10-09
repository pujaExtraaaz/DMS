<x-tally::layouts.app :title="$order->number">
    <x-tally::shell.page :title="$order->number" section="Transactions" :description="$order->bill->finishedProduct->name">
        <x-slot:actions>
            @if ($order->status === \Tally\Accounting\VoucherStatus::Posted)
                <form method="POST" action="{{ tally_route('books.tally.manufacturing.cancel', $order) }}" onsubmit="return confirm('Cancel this production? Stock and the cost journal will be reversed.');">
                    @csrf
                    <button class="btn" type="submit">Cancel</button>
                </form>
            @endif
        </x-slot:actions>
        <dl class="sheet">
            <div><dt>Date</dt><dd>{{ $order->manufactured_on->format('d M Y') }}</dd></div>
            <div><dt>Quantity</dt><dd>{{ $order->quantity }}</dd></div>
            <div><dt>From</dt><dd>{{ $order->sourceGodown->name }}</dd></div>
            <div><dt>To</dt><dd>{{ $order->destinationGodown->name }}</dd></div>
            <div><dt>Material cost</dt><dd>{{ $order->material_cost }}</dd></div>
            <div><dt>Journal</dt><dd>{{ $order->voucher?->voucher_number ?? '—' }}</dd></div>
        </dl>
        <h2>Stock impact</h2>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Product</th><th>Batch</th><th>Quantity</th><th class="money">Value</th></tr></thead>
                <tbody>
                    @foreach ($order->movements as $movement)
                        <tr>
                            <td>{{ $movement->product->name }}</td>
                            <td>{{ $movement->batch?->batch_number ?? '—' }}</td>
                            <td>{{ $movement->quantity }}</td>
                            <td class="money">{{ $movement->value }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-shell.page>
</x-layouts.app>
