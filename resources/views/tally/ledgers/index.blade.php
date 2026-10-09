<x-tally::layouts.app title="Ledgers">
    <x-tally::shell.page title="Ledgers" section="Masters" :description="$company->name">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ tally_route('books.tally.ledgers.create') }}">New ledger</a>
        </x-slot:actions>
        <form class="filters" method="GET" action="{{ tally_route('books.tally.ledgers.index') }}">
            <x-tally::form.field name="q" label="Search">
                <x-tally::form.input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Name, code, phone, email" />
            </x-form.field>
            <x-tally::form.field name="account_group_id" label="Group">
                <select id="account_group_id" name="account_group_id" class="input">
                    <option value="">All groups</option>
                    @foreach ($groups as $row)
                        <option value="{{ $row['group']->id }}" @selected((string) ($filters['account_group_id'] ?? '') === (string) $row['group']->id)>
                            {{ str_repeat('· ', $row['depth']) }}{{ $row['group']->name }}
                        </option>
                    @endforeach
                </select>
            </x-form.field>
            <x-tally::form.field name="status" label="Status">
                <select id="status" name="status" class="input">
                    <option value="">All</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
                </select>
            </x-form.field>
            <x-tally::form.field name="sort" label="Sort">
                <select id="sort" name="sort" class="input">
                    <option value="name" @selected(($filters['sort'] ?? 'name') === 'name')>Name</option>
                    <option value="code" @selected(($filters['sort'] ?? '') === 'code')>Code</option>
                    <option value="opening_balance" @selected(($filters['sort'] ?? '') === 'opening_balance')>Opening balance</option>
                </select>
            </x-form.field>
            <x-tally::form.field name="direction" label="Order">
                <select id="direction" name="direction" class="input">
                    <option value="asc" @selected(($filters['direction'] ?? 'asc') === 'asc')>Ascending</option>
                    <option value="desc" @selected(($filters['direction'] ?? '') === 'desc')>Descending</option>
                </select>
            </x-form.field>
            <button class="btn btn-primary" type="submit">Apply</button>
            <a class="btn" href="{{ tally_route('books.tally.ledgers.index') }}">Reset</a>
        </form>
        @if ($ledgers->isEmpty())
            <x-tally::ui.empty-state title="No ledgers" message="No ledgers match this company and filter.">
                <a class="btn btn-primary" href="{{ tally_route('books.tally.ledgers.create') }}">New ledger</a>
            </x-ui.empty-state>
        @else
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Group</th>
                            <th>Opening balance</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($ledgers as $ledger)
                            <tr>
                                <td>
                                    <a href="{{ tally_route('books.tally.ledgers.show', $ledger) }}">{{ $ledger->name }}</a>
                                    @if ($ledger->is_system)
                                        <span class="tag">System</span>
                                    @endif
                                </td>
                                <td>{{ $ledger->code ?: '—' }}</td>
                                <td>{{ $ledger->accountGroup->name }}</td>
                                <td>{{ $ledger->openingBalanceLabel() }}</td>
                                <td><x-tally::ui.status :active="$ledger->is_active" /></td>
                                <td>
                                    <x-tally::ui.record-actions
                                        :view="tally_route('books.tally.ledgers.show', $ledger)"
                                        :edit="tally_route('books.tally.ledgers.edit', $ledger)"
                                        :active="$ledger->is_active"
                                        :toggle="tally_route('books.tally.ledgers.activation', $ledger)"
                                        :delete="tally_route('books.tally.ledgers.destroy', $ledger)"
                                        delete-reason="This ledger is used by vouchers or opening balances and cannot be deleted."
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $ledgers->links() }}
        @endif
    </x-shell.page>
</x-layouts.app>
