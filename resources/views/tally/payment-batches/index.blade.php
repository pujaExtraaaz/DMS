<x-tally::layouts.app title="Payment batches">
    <x-tally::shell.page title="Payment batches" section="Payables" :description="$company->name">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ tally_route('books.tally.payment-batches.create') }}">New batch</a>
        </x-slot:actions>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Number</th><th>Date</th><th class="money">Total</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($batches as $row)
                        <tr>
                            <td><a href="{{ tally_route('books.tally.payment-batches.show', $row) }}">{{ $row->number }}</a></td>
                            <td>{{ $row->batch_date->format('d M Y') }}</td>
                            <td class="money">{{ $row->total_amount }}</td>
                            <td>{{ str_replace('_', ' ', ucfirst($row->status)) }}</td>
                            <td><a class="btn" href="{{ tally_route('books.tally.payment-batches.show', $row) }}">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No payment batches yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $batches->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
