<x-tally::layouts.app title="RFQs">
    <x-tally::shell.page title="Request for quotations" section="Purchasing" :description="$company->name">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ tally_route('books.tally.rfqs.create') }}">New RFQ</a>
        </x-slot:actions>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Number</th><th>Date</th><th>Due</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($rfqs as $row)
                        <tr>
                            <td><a href="{{ tally_route('books.tally.rfqs.show', $row) }}">{{ $row->number }}</a></td>
                            <td>{{ $row->rfq_date->format('d M Y') }}</td>
                            <td>{{ $row->due_date?->format('d M Y') }}</td>
                            <td>{{ ucfirst($row->status) }}</td>
                            <td><a class="btn" href="{{ tally_route('books.tally.rfqs.show', $row) }}">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No RFQs yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $rfqs->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
