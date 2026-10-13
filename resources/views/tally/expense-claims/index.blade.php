<x-tally::layouts.app title="Expense claims">
    <x-tally::shell.page title="Expense claims" section="Payables" :description="$company->name">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ tally_route('books.tally.expense-claims.create') }}">New claim</a>
        </x-slot:actions>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Number</th><th>Date</th><th>Employee</th><th>Status</th><th class="money">Amount</th><th></th></tr></thead>
                <tbody>
                    @forelse ($claims as $row)
                        <tr>
                            <td><a href="{{ tally_route('books.tally.expense-claims.show', $row) }}">{{ $row->number }}</a></td>
                            <td>{{ $row->claim_date->format('d M Y') }}</td>
                            <td>{{ $row->employeeLedger?->name }}</td>
                            <td>{{ ucfirst($row->status) }}</td>
                            <td class="money">{{ $row->amount }}</td>
                            <td><a class="btn" href="{{ tally_route('books.tally.expense-claims.show', $row) }}">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No expense claims yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $claims->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
