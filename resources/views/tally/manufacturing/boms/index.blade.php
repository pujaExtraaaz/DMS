<x-tally::layouts.app title="Bills of materials">
    <x-tally::shell.page title="Bills of materials" section="Masters" :description="$company->name">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ tally_route('books.tally.boms.create') }}">New BOM</a>
        </x-slot:actions>
        <form class="filters" method="GET">
            <x-tally::form.field name="status" label="Status">
                <select id="status" name="status" class="input">
                    <option value="">All</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
                </select>
            </x-form.field>
            <button class="btn btn-primary" type="submit">Apply</button>
        </form>
        @if ($bills->isEmpty())
            <x-tally::ui.empty-state title="No bills of materials" message="Create a BOM before recording production." />
        @else
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr><th>Name</th><th>Finished product</th><th>Wastage %</th><th>Orders</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($bills as $bill)
                            <tr>
                                <td><a href="{{ tally_route('books.tally.boms.show', $bill) }}">{{ $bill->name }}</a></td>
                                <td>{{ $bill->finishedProduct->name }}</td>
                                <td>{{ $bill->wastage_percent }}</td>
                                <td>{{ $bill->orders_count }}</td>
                                <td><x-tally::ui.status :active="$bill->is_active" /></td>
                                <td>
                                    <x-tally::ui.record-actions
                                        :view="tally_route('books.tally.boms.show', $bill)"
                                        :edit="tally_route('books.tally.boms.edit', $bill)"
                                        :active="$bill->is_active"
                                        :toggle="tally_route('books.tally.boms.activation', $bill)"
                                        :delete="tally_route('books.tally.boms.destroy', $bill)"
                                        delete-reason="This bill of materials has manufacturing history and cannot be deleted."
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $bills->links() }}
        @endif
    </x-shell.page>
</x-layouts.app>
