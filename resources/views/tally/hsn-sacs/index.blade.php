<x-tally::layouts.app title="HSN / SAC">
    <x-tally::shell.page title="HSN / SAC" section="Masters" :description="$company->name">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ tally_route('books.tally.hsn-sacs.create') }}">New HSN / SAC</a>
        </x-slot:actions>
        <form class="filters" method="GET" action="{{ tally_route('books.tally.hsn-sacs.index') }}">
            <x-tally::form.field name="q" label="Search">
                <x-tally::form.input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Code or description" />
            </x-form.field>
            <button class="btn btn-primary" type="submit">Apply</button>
            <a class="btn" href="{{ tally_route('books.tally.hsn-sacs.index') }}">Reset</a>
        </form>
        @if ($records->isEmpty())
            <x-tally::ui.empty-state title="No HSN or SAC codes" message="Add a code before attaching it to a product.">
                <a class="btn btn-primary" href="{{ tally_route('books.tally.hsn-sacs.create') }}">New HSN / SAC</a>
            </x-ui.empty-state>
        @else
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Code</th><th>Kind</th><th>Description</th><th>Tax rate</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($records as $record)
                            <tr>
                                <td><a href="{{ tally_route('books.tally.hsn-sacs.show', $record) }}">{{ $record->code }}</a></td>
                                <td>{{ $record->kind->label() }}</td>
                                <td>{{ $record->description ?: '—' }}</td>
                                <td>{{ $record->taxRate->name ?? '—' }}</td>
                                <td><x-tally::ui.status :active="$record->is_active" /></td>
                                <td>
                                    <x-tally::ui.record-actions
                                        :view="tally_route('books.tally.hsn-sacs.show', $record)"
                                        :edit="tally_route('books.tally.hsn-sacs.edit', $record)"
                                        :active="$record->is_active"
                                        :toggle="tally_route('books.tally.hsn-sacs.activation', $record)"
                                        :delete="tally_route('books.tally.hsn-sacs.destroy', $record)"
                                        delete-reason="This HSN/SAC is used by products or invoices and cannot be deleted."
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $records->links() }}
        @endif
    </x-shell.page>
</x-layouts.app>
