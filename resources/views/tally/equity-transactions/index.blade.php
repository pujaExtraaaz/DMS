<x-tally::layouts.app title="Equity transactions">
    <x-tally::shell.page title="Equity transactions" section="Equity" :description="$company->name">
        <form method="POST" action="{{ tally_route('books.tally.equity-transactions.store') }}" class="form-grid panel">
            @csrf
            <x-tally::form.field name="kind" label="Kind" required>
                <select class="input" name="kind" required>
                    @foreach (['capital','drawing','dividend','reserve'] as $kind)<option value="{{ $kind }}">{{ ucfirst($kind) }}</option>@endforeach
                </select>
            </x-tally::form.field>
            <x-tally::form.field name="txn_date" label="Date" required><x-tally::form.input type="date" name="txn_date" :value="now()->toDateString()" required /></x-tally::form.field>
            <x-tally::form.field name="amount" label="Amount" required><x-tally::form.input name="amount" required /></x-tally::form.field>
            <x-tally::form.field name="narration" label="Narration"><x-tally::form.input name="narration" /></x-tally::form.field>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Record</button></div>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Date</th><th>Kind</th><th class="money">Amount</th><th>Narration</th></tr></thead>
                <tbody>
                    @forelse ($transactions as $txn)
                        <tr>
                            <td>{{ $txn->txn_date->format('d M Y') }}</td>
                            <td>{{ ucfirst($txn->kind) }}</td>
                            <td class="money">{{ $txn->amount }}</td>
                            <td>{{ $txn->narration }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No equity transactions.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $transactions->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
