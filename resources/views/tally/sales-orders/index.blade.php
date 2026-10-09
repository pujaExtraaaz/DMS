<x-tally::layouts.app title="Sales Orders">
    <x-tally::shell.page title="Sales Orders" section="Transactions" :description="$company->name">
        <x-slot:actions><a class="btn btn-primary" href="{{ tally_route('books.tally.sales-orders.create') }}">Create</a></x-slot:actions>
        <form class="filters" method="GET">
            <x-tally::form.field name="status" label="Status">
                <select id="status" name="status" class="input">
                    <option value="">All</option>
                    @foreach (['pending' => 'Pending', 'fulfilled' => 'Fulfilled', 'cancelled' => 'Cancelled'] as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </x-form.field>
            <button class="btn btn-primary" type="submit">Apply</button>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Number</th><th>Date</th><th>Customer</th><th class="money">Amount</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td><a href="{{ tally_route('books.tally.sales-orders.show', $order) }}">{{ $order->number }}</a></td>
                            <td>{{ $order->order_date->format('d M Y') }}</td>
                            <td>{{ $order->customer?->name }}</td>
                            <td class="money">{{ $order->grand_total }}</td>
                            <td>{{ ucfirst($order->status) }}</td>
                            <td>
                                <x-tally::ui.record-actions :edit="tally_route('books.tally.sales-orders.edit', $order)" :delete="tally_route('books.tally.sales-orders.destroy', $order)" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No sales orders.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $orders->links() }}
    </x-shell.page>
</x-layouts.app>
