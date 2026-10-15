<x-tally::layouts.app title="Ledger mirrors">
    <x-tally::shell.page title="Intercompany ledger mirrors" section="Utilities" :description="$company->name">
        <p class="form-note">Map source ledgers to counterparty ledgers for multi-line intercompany mirroring. Name/code match is used when no map exists.</p>
        <form method="POST" action="{{ tally_route('books.tally.ledger-mirrors.store') }}" class="filters">
            @csrf
            <select class="input" name="source_ledger_id" required>
                <option value="">Source ledger</option>
                @foreach ($ledgers as $ledger)
                    <option value="{{ $ledger->id }}">{{ $ledger->name }}</option>
                @endforeach
            </select>
            <select class="input" name="counterparty_company_id" required>
                <option value="">Counterparty company</option>
                @foreach ($companies as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </select>
            <select class="input" name="counterparty_ledger_id" required>
                <option value="">Counterparty ledger id</option>
                @foreach ($companies as $c)
                    @foreach ($c->ledgers()->where('is_active', true)->orderBy('name')->get(['id','name']) as $ledger)
                        <option value="{{ $ledger->id }}">{{ $c->name }} · {{ $ledger->name }}</option>
                    @endforeach
                @endforeach
            </select>
            <button class="btn btn-primary" type="submit">Save mirror</button>
        </form>

        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Source</th><th>Counterparty</th><th>Mapped Ledger</th><th></th></tr></thead>
                <tbody>
                    @forelse ($mirrors as $mirror)
                        <tr>
                            <td>{{ $mirror->sourceLedger?->name }}</td>
                            <td>{{ $mirror->counterpartyCompany?->name }}</td>
                            <td>{{ $mirror->counterpartyLedger?->name }}</td>
                            <td class="row-actions">
                                <form method="POST" action="{{ tally_route('books.tally.ledger-mirrors.destroy', $mirror) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No mirrors yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $mirrors->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
