<x-tally::layouts.app title="Manufacturing">
    <x-tally::shell.page title="Manufacturing" section="Transactions" :description="$company->name">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ tally_route('books.tally.manufacturing.create') }}">New manufacturing journal</a>
        </x-slot:actions>
        @if ($orders->isEmpty())
            <x-tally::ui.empty-state title="No manufacturing" message="Produce finished goods from a bill of materials." />
        @else
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr><th>Date</th><th>Number</th><th>Finished product</th><th>Quantity</th><th>From</th><th>To</th><th class="money">Material cost</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr>
                                <td>{{ $order->manufactured_on->format('d M Y') }}</td>
                                <td><a href="{{ tally_route('books.tally.manufacturing.show', $order) }}">{{ $order->number }}</a></td>
                                <td>{{ $order->bill->finishedProduct->name }}</td>
                                <td>{{ $order->quantity }}</td>
                                <td>{{ $order->sourceGodown->name }}</td>
                                <td>{{ $order->destinationGodown->name }}</td>
                                <td class="money">{{ $order->material_cost }}</td>
                                <td>{{ $order->status->label() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $orders->links() }}
        @endif
    </x-shell.page>
</x-layouts.app>
