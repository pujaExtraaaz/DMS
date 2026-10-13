<x-tally::layouts.app title="Landed costs">
    <x-tally::shell.page title="Landed costs" section="Purchasing" :description="$company->name">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ tally_route('books.tally.landed-costs.create') }}">New voucher</a>
        </x-slot:actions>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Number</th><th>Date</th><th class="money">Total</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($vouchers as $row)
                        <tr>
                            <td><a href="{{ tally_route('books.tally.landed-costs.show', $row) }}">{{ $row->number }}</a></td>
                            <td>{{ $row->voucher_date->format('d M Y') }}</td>
                            <td class="money">{{ $row->total_charges }}</td>
                            <td>{{ ucfirst($row->status) }}</td>
                            <td><a class="btn" href="{{ tally_route('books.tally.landed-costs.show', $row) }}">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No landed cost vouchers yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $vouchers->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
