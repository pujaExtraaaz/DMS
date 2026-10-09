<x-tally::layouts.app title="Cost centres">
    <x-tally::shell.page title="Cost centres" section="Masters" :description="$company->name">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ tally_route('books.tally.cost-centres.create') }}">New cost centre</a>
        </x-slot:actions>
        <form class="filters" method="GET">
            <x-tally::form.field name="q" label="Search"><x-tally::form.input name="q" value="{{ $filters['q'] }}" /></x-form.field>
            <button class="btn btn-primary" type="submit">Apply</button>
        </form>
        @if ($centres->isEmpty())
            <x-tally::ui.empty-state title="No cost centres" message="Create a centre before allocating voucher lines." />
        @else
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Name</th><th>Category</th><th>Code</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($centres as $centre)
                            <tr>
                                <td>{{ $centre->name }}</td>
                                <td>{{ $centre->category?->name ?: '—' }}</td>
                                <td>{{ $centre->code ?: '—' }}</td>
                                <td><x-tally::ui.status :active="$centre->is_active" /></td>
                                <td>
                                    <x-tally::ui.record-actions
                                        :edit="tally_route('books.tally.cost-centres.edit', $centre)"
                                        :active="$centre->is_active"
                                        :toggle="tally_route('books.tally.cost-centres.activation', $centre)"
                                        :delete="tally_route('books.tally.cost-centres.destroy', $centre)"
                                        delete-reason="This cost centre is used by vouchers or budgets and cannot be deleted."
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $centres->links() }}
        @endif
    </x-shell.page>
</x-layouts.app>
