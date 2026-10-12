<x-tally::layouts.app title="Quotations">
    <x-tally::shell.page title="Quotations" section="Transactions" :description="$company->name">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ tally_route('books.tally.quotations.create') }}">New quotation</a>
        </x-slot:actions>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Number</th><th>Date</th><th>Customer</th><th>Status</th><th class="money">Amount</th><th></th></tr></thead>
                <tbody>
                    @forelse ($quotations as $quote)
                        <tr>
                            <td><a href="{{ tally_route('books.tally.quotations.show', $quote) }}">{{ $quote->number }}</a></td>
                            <td>{{ $quote->quote_date->format('d M Y') }}</td>
                            <td>{{ $quote->customer?->name }}</td>
                            <td>{{ ucfirst($quote->status) }}</td>
                            <td class="money">{{ $quote->grand_total }}</td>
                            <td><a class="btn" href="{{ tally_route('books.tally.quotations.edit', $quote) }}">Edit</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No quotations yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $quotations->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
