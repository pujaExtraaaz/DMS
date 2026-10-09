<x-tally::layouts.app title="Purchase orders">
    <x-tally::shell.page title="Purchase orders" section="Transactions" :description="$company->name">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ tally_route('books.tally.purchase-orders.create') }}">New purchase order</a>
        </x-slot:actions>
        @if ($orders->isEmpty())
            <x-tally::ui.empty-state title="No purchase orders" message="A purchase order is a document only. It does not move stock or post accounts." />
        @else
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>Number</th>
                            <th>Date</th>
                            <th>Supplier</th>
                            <th class="money">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr>
                                <td><a href="{{ tally_route('books.tally.purchase-orders.show', $order) }}">{{ $order->number }}</a></td>
                                <td>{{ $order->order_date->format('d M Y') }}</td>
                                <td>{{ $order->supplier->name ?? '—' }}</td>
                                <td class="money">{{ $order->grand_total }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $orders->links() }}
        @endif
    </x-shell.page>
</x-layouts.app>
