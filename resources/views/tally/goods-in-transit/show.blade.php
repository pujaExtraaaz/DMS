<x-tally::layouts.app title="Goods in transit">
    <x-tally::shell.page title="Goods in transit" section="Inventory" :description="str_replace('_', ' ', ucfirst($record->status))">
        <x-slot:actions>
            @if ($record->isOpen())
                <form method="POST" action="{{ tally_route('books.tally.goods-in-transit.receive', $record) }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">Mark received</button>
                </form>
            @endif
            <a class="btn" href="{{ tally_route('books.tally.goods-in-transit.index') }}">Back</a>
        </x-slot:actions>
        <dl class="kv">
            <dt>Product</dt><dd>{{ $record->product?->name }}</dd>
            <dt>Quantity</dt><dd>{{ $record->quantity }}</dd>
            <dt>From</dt><dd>{{ $record->fromGodown?->name ?: '—' }}</dd>
            <dt>To</dt><dd>{{ $record->toGodown?->name ?: '—' }}</dd>
            <dt>Dispatch</dt><dd>{{ $record->dispatch_date->format('d M Y') }}</dd>
            <dt>Expected</dt><dd>{{ $record->expected_receipt_date?->format('d M Y') ?: '—' }}</dd>
            <dt>Received</dt><dd>{{ $record->received_date?->format('d M Y') ?: '—' }}</dd>
            <dt>Reference</dt><dd>{{ $record->reference ?: '—' }}</dd>
            <dt>Consignment</dt><dd>{{ $record->is_consignment ? 'Yes' : 'No' }}</dd>
        </dl>
    </x-tally::shell.page>
</x-tally::layouts.app>
