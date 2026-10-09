<x-tally::layouts.app title="Parties">
    <x-tally::shell.page title="Parties" section="Masters" :description="$company->name">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ tally_route('books.tally.parties.create') }}">New party</a>
        </x-slot:actions>
        <form class="filters" method="GET">
            <x-tally::form.field name="q" label="Search"><x-tally::form.input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Name, code, GSTIN" /></x-form.field>
            <x-tally::form.field name="type" label="Type">
                <select id="type" name="type" class="input">
                    <option value="">All</option>
                    <option value="customer" @selected(($filters['type'] ?? '') === 'customer')>Customers</option>
                    <option value="supplier" @selected(($filters['type'] ?? '') === 'supplier')>Suppliers</option>
                </select>
            </x-form.field>
            <x-tally::form.field name="status" label="Status">
                <select id="status" name="status" class="input">
                    <option value="">All</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
                </select>
            </x-form.field>
            <button class="btn btn-primary" type="submit">Apply</button>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Name</th><th>Type</th><th>GSTIN</th><th class="money">Opening</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($parties as $party)
                        <tr>
                            <td><a href="{{ tally_route('books.tally.parties.show', $party) }}">{{ $party->ledger->name }}</a><br><span class="muted">{{ $party->ledger->code }}</span></td>
                            <td>{{ ucfirst($party->type) }}</td>
                            <td>{{ $party->gstin ?: '—' }}</td>
                            <td class="money">{{ $party->ledger->openingBalanceLabel() }}</td>
                            <td>{{ $party->is_active ? 'Active' : 'Inactive' }}</td>
                            <td>
                                <x-tally::ui.record-actions
                                    :view="tally_route('books.tally.parties.show', $party)"
                                    :edit="tally_route('books.tally.parties.edit', $party)"
                                    :toggle="tally_route('books.tally.parties.activation', $party)"
                                    :active="$party->is_active"
                                    :delete="tally_route('books.tally.parties.destroy', $party)"
                                    :delete-reason="$party->ledger->canBeDeleted() ? null : 'Opening balance or history'"
                                />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No parties yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $parties->links() }}
    </x-shell.page>
</x-layouts.app>
