<x-tally::layouts.app title="Units">
    <x-tally::shell.page title="Units" section="Masters" :description="$company->name">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ tally_route('books.tally.units.create') }}">New unit</a>
        </x-slot:actions>
        <form class="filters" method="GET" action="{{ tally_route('books.tally.units.index') }}">
            <x-tally::form.field name="q" label="Search">
                <x-tally::form.input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Name or symbol" />
            </x-form.field>
            <x-tally::form.field name="status" label="Status">
                <select id="status" name="status" class="input">
                    <option value="">All</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
                </select>
            </x-form.field>
            <button class="btn btn-primary" type="submit">Apply</button>
            <a class="btn" href="{{ tally_route('books.tally.units.index') }}">Reset</a>
        </form>
        @if ($units->isEmpty())
            <x-tally::ui.empty-state title="No units" message="Add the first unit of measure for this company.">
                <a class="btn btn-primary" href="{{ tally_route('books.tally.units.create') }}">New unit</a>
            </x-ui.empty-state>
        @else
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Symbol</th>
                            <th>Decimals</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($units as $unit)
                            <tr>
                                <td><a href="{{ tally_route('books.tally.units.show', $unit) }}">{{ $unit->name }}</a></td>
                                <td>{{ $unit->symbol }}</td>
                                <td>{{ $unit->decimal_places }}</td>
                                <td><x-tally::ui.status :active="$unit->is_active" /></td>
                                <td>
                                    <x-tally::ui.record-actions
                                        :view="tally_route('books.tally.units.show', $unit)"
                                        :edit="tally_route('books.tally.units.edit', $unit)"
                                        :active="$unit->is_active"
                                        :toggle="tally_route('books.tally.units.activation', $unit)"
                                        :delete="tally_route('books.tally.units.destroy', $unit)"
                                        delete-reason="This unit is used by products and cannot be deleted."
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $units->links() }}
        @endif
    </x-shell.page>
</x-layouts.app>
