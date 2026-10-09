<x-tally::layouts.app title="Godowns">
    <x-tally::shell.page title="Godowns" section="Masters" :description="$company->name">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ tally_route('books.tally.godowns.create') }}">New godown</a>
        </x-slot:actions>
        <form class="filters" method="GET" action="{{ tally_route('books.tally.godowns.index') }}">
            <x-tally::form.field name="q" label="Search">
                <x-tally::form.input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Name, code, address" />
            </x-form.field>
            <x-tally::form.field name="status" label="Status">
                <select id="status" name="status" class="input">
                    <option value="">All</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
                </select>
            </x-form.field>
            <button class="btn btn-primary" type="submit">Apply</button>
            <a class="btn" href="{{ tally_route('books.tally.godowns.index') }}">Reset</a>
        </form>
        @if ($godowns->isEmpty())
            <x-tally::ui.empty-state title="No godowns" message="Add a warehouse or location for this company.">
                <a class="btn btn-primary" href="{{ tally_route('books.tally.godowns.create') }}">New godown</a>
            </x-ui.empty-state>
        @else
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Address</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($godowns as $godown)
                            <tr>
                                <td><a href="{{ tally_route('books.tally.godowns.show', $godown) }}">{{ $godown->name }}</a></td>
                                <td>{{ $godown->code ?: '—' }}</td>
                                <td>{{ $godown->address ?: '—' }}</td>
                                <td><x-tally::ui.status :active="$godown->is_active" /></td>
                                <td>
                                    <x-tally::ui.record-actions
                                        :view="tally_route('books.tally.godowns.show', $godown)"
                                        :edit="tally_route('books.tally.godowns.edit', $godown)"
                                        :active="$godown->is_active"
                                        :toggle="tally_route('books.tally.godowns.activation', $godown)"
                                        :delete="tally_route('books.tally.godowns.destroy', $godown)"
                                        delete-reason="This godown has stock movements and cannot be deleted."
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $godowns->links() }}
        @endif
    </x-shell.page>
</x-layouts.app>
