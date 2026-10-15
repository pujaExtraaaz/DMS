<x-tally::layouts.app title="Recurring invoices">
    <x-tally::shell.page title="Recurring invoices" section="Transactions" :description="$company->name">
        <x-slot:actions>
            <form method="POST" action="{{ tally_route('books.tally.recurring-invoices.run') }}">@csrf<button class="btn" type="submit">Run due</button></form>
            <a class="btn btn-primary" href="{{ tally_route('books.tally.recurring-invoices.create') }}">New template</a>
        </x-slot:actions>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Name</th><th>Customer</th><th>Frequency</th><th>Next Run</th><th></th></tr></thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td>{{ $item->name }}</td>
                            <td>{{ $item->customer?->name }}</td>
                            <td>{{ ucfirst($item->frequency) }}</td>
                            <td>{{ optional($item->next_run_on)->format('d M Y') }}</td>
                            <td><a class="btn" href="{{ tally_route('books.tally.recurring-invoices.edit', $item) }}">Edit</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No recurring invoice templates.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $items->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
