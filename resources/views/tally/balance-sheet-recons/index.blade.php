<x-tally::layouts.app title="BS reconciliations">
    <x-tally::shell.page title="Balance sheet reconciliations" section="Close" :description="$company->name">
        <form method="POST" action="{{ tally_route('books.tally.balance-sheet-recons.store') }}" class="form-grid panel">
            @csrf
            <x-tally::form.field name="ledger_id" label="Ledger" required>
                <select class="input" name="ledger_id" required>
                    @foreach ($ledgers as $ledger)<option value="{{ $ledger->id }}">{{ $ledger->name }}</option>@endforeach
                </select>
            </x-tally::form.field>
            <x-tally::form.field name="as_of_date" label="As of" required><x-tally::form.input type="date" name="as_of_date" :value="now()->toDateString()" required /></x-tally::form.field>
            <x-tally::form.field name="gl_balance" label="GL balance" required><x-tally::form.input name="gl_balance" required /></x-tally::form.field>
            <x-tally::form.field name="substantiated_balance" label="Substantiated" required><x-tally::form.input name="substantiated_balance" required /></x-tally::form.field>
            <x-tally::form.field name="evidence_note" label="Evidence"><x-tally::form.input name="evidence_note" /></x-tally::form.field>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Save</button></div>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Ledger</th><th>As of</th><th class="money">GL</th><th class="money">Substantiated</th><th class="money">Diff</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>{{ $row->ledger?->name }}</td>
                            <td>{{ $row->as_of_date->format('d M Y') }}</td>
                            <td class="money">{{ $row->gl_balance }}</td>
                            <td class="money">{{ $row->substantiated_balance }}</td>
                            <td class="money">{{ $row->difference }}</td>
                            <td>{{ ucfirst($row->status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No reconciliations.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $rows->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
