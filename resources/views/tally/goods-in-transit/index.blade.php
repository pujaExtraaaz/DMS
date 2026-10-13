<x-tally::layouts.app title="Goods in transit">
    <x-tally::shell.page title="Goods in transit" section="Inventory" :description="$company->name">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ tally_route('books.tally.goods-in-transit.create') }}">New transit</a>
        </x-slot:actions>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Product</th><th>From</th><th>To</th><th class="money">Qty</th><th>Dispatch</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td><a href="{{ tally_route('books.tally.goods-in-transit.show', $row) }}">{{ $row->product?->name }}</a></td>
                            <td>{{ $row->fromGodown?->name ?: '—' }}</td>
                            <td>{{ $row->toGodown?->name ?: '—' }}</td>
                            <td class="money">{{ $row->quantity }}</td>
                            <td>{{ $row->dispatch_date->format('d M Y') }}</td>
                            <td>{{ str_replace('_', ' ', ucfirst($row->status)) }}</td>
                            <td>
                                @if ($row->isOpen())
                                    <form method="POST" action="{{ tally_route('books.tally.goods-in-transit.receive', $row) }}">
                                        @csrf
                                        <button class="btn" type="submit">Receive</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">No goods in transit.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $rows->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
