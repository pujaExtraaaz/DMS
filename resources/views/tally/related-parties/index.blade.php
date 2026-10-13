<x-tally::layouts.app title="Related parties">
    <x-tally::shell.page title="Related parties" section="Entity" :description="$company->name">
        <form method="POST" action="{{ tally_route('books.tally.related-parties.store') }}" class="form-grid panel">
            @csrf
            <x-tally::form.field name="party_ledger_id" label="Party ledger" required>
                <select class="input" name="party_ledger_id" required>
                    @foreach ($ledgers as $ledger)<option value="{{ $ledger->id }}">{{ $ledger->name }}</option>@endforeach
                </select>
            </x-tally::form.field>
            <x-tally::form.field name="relationship" label="Relationship" required><x-tally::form.input name="relationship" required /></x-tally::form.field>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Add</button></div>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Party</th><th>Relationship</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($parties as $party)
                        <tr>
                            <td>{{ $party->partyLedger?->name }}</td>
                            <td>{{ $party->relationship }}</td>
                            <td>{{ $party->is_active ? 'Active' : 'Inactive' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3">No related parties.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $parties->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
