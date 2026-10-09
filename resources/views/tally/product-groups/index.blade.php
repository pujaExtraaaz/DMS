<x-tally::layouts.app title="Product groups">
    <x-tally::shell.page title="Product groups" section="Masters" :description="$company->name">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ tally_route('books.tally.product-groups.create') }}">New product group</a>
        </x-slot:actions>
        @if ($groups->isEmpty())
            <x-tally::ui.empty-state title="No product groups" message="Add a group before creating stock items.">
                <a class="btn btn-primary" href="{{ tally_route('books.tally.product-groups.create') }}">New product group</a>
            </x-ui.empty-state>
        @else
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Products</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($groups as $row)
                            @php($group = $row['group'])
                            <tr>
                                <td>
                                    <a href="{{ tally_route('books.tally.product-groups.show', $group) }}" style="padding-left: {{ $row['depth'] * 16 }}px">{{ $group->name }}</a>
                                </td>
                                <td>{{ $group->code ?: '—' }}</td>
                                <td>{{ $group->products_count }}</td>
                                <td><x-tally::ui.status :active="$group->is_active" /></td>
                                <td>
                                    <x-tally::ui.record-actions
                                        :view="tally_route('books.tally.product-groups.show', $group)"
                                        :edit="tally_route('books.tally.product-groups.edit', $group)"
                                        :active="$group->is_active"
                                        :toggle="tally_route('books.tally.product-groups.activation', $group)"
                                        :delete="tally_route('books.tally.product-groups.destroy', $group)"
                                        delete-reason="This product group has products or child groups and cannot be deleted."
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-shell.page>
</x-layouts.app>
