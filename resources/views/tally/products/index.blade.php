<x-tally::layouts.app title="Products">
    <x-tally::shell.page title="Products" section="Masters" :description="$company->name">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ tally_route('books.tally.products.create') }}">New product</a>
        </x-slot:actions>
        <form class="filters" method="GET" action="{{ tally_route('books.tally.products.index') }}">
            <x-tally::form.field name="q" label="Search">
                <x-tally::form.input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Name, SKU, barcode" />
            </x-form.field>
            <x-tally::form.field name="product_group_id" label="Group">
                <select id="product_group_id" name="product_group_id" class="input">
                    <option value="">All groups</option>
                    @foreach ($groups as $row)
                        <option value="{{ $row['group']->id }}" @selected((string) ($filters['product_group_id'] ?? '') === (string) $row['group']->id)>
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
            <button class="btn btn-primary" type="submit">Apply</button>
            <a class="btn" href="{{ tally_route('books.tally.products.index') }}">Reset</a>
        </form>
        @if ($products->isEmpty())
            <x-tally::ui.empty-state title="No products" message="No stock items match this company and filter.">
                <a class="btn btn-primary" href="{{ tally_route('books.tally.products.create') }}">New product</a>
            </x-ui.empty-state>
        @else
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>SKU</th>
                            <th>Group</th>
                            <th>Unit</th>
                            <th class="money">Sales rate</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $product)
                            <tr>
                                <td><a href="{{ tally_route('books.tally.products.show', $product) }}">{{ $product->name }}</a></td>
                                <td>{{ $product->code }}</td>
                                <td>{{ $product->productGroup->name }}</td>
                                <td>{{ $product->primaryUnit->symbol }}</td>
                                <td class="money">{{ $product->sales_rate }}</td>
                                <td><x-tally::ui.status :active="$product->is_active" /></td>
                                <td>
                                    <x-tally::ui.record-actions
                                        :view="tally_route('books.tally.products.show', $product)"
                                        :edit="tally_route('books.tally.products.edit', $product)"
                                        :active="$product->is_active"
                                        :toggle="tally_route('books.tally.products.activation', $product)"
                                        :delete="tally_route('books.tally.products.destroy', $product)"
                                        delete-reason="This product is used by stock, invoices, or manufacturing and cannot be deleted."
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $products->links() }}
        @endif
    </x-shell.page>
</x-layouts.app>
