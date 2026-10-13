<x-tally::layouts.app title="Goods receipts">
    <x-tally::shell.page title="Goods receipts" section="Purchasing" :description="$company->name">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ tally_route('books.tally.goods-receipts.create') }}">New GRN</a>
        </x-slot:actions>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Number</th><th>Date</th><th>Supplier</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($receipts as $row)
                        <tr>
                            <td><a href="{{ tally_route('books.tally.goods-receipts.show', $row) }}">{{ $row->number }}</a></td>
                            <td>{{ $row->receipt_date->format('d M Y') }}</td>
                            <td>{{ $row->supplier?->name }}</td>
                            <td>{{ ucfirst($row->status) }}</td>
                            <td><a class="btn" href="{{ tally_route('books.tally.goods-receipts.show', $row) }}">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No goods receipts yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $receipts->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
