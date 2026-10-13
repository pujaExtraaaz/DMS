<x-tally::layouts.app title="Purchase requisitions">
    <x-tally::shell.page title="Purchase requisitions" section="Purchasing" :description="$company->name">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ tally_route('books.tally.purchase-requisitions.create') }}">New requisition</a>
        </x-slot:actions>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Number</th><th>Date</th><th>Priority</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($requisitions as $row)
                        <tr>
                            <td><a href="{{ tally_route('books.tally.purchase-requisitions.show', $row) }}">{{ $row->number }}</a></td>
                            <td>{{ $row->requisition_date->format('d M Y') }}</td>
                            <td>{{ ucfirst($row->priority) }}</td>
                            <td>{{ ucfirst($row->status) }}</td>
                            <td><a class="btn" href="{{ tally_route('books.tally.purchase-requisitions.show', $row) }}">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No purchase requisitions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $requisitions->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
