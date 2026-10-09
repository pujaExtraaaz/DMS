<x-tally::layouts.app title="Cost centres">
    <x-tally::shell.page title="Cost centres" section="Reports" :description="$company->name.' · '.$year->name">
        <form class="filters" method="GET">
            <x-tally::form.field name="from" label="From"><x-tally::form.input name="from" type="date" value="{{ $filters['from'] }}" /></x-form.field>
            <x-tally::form.field name="to" label="To"><x-tally::form.input name="to" type="date" value="{{ $filters['to'] }}" /></x-form.field>
            <x-tally::form.field name="cost_category_id" label="Category">
                <select id="cost_category_id" name="cost_category_id" class="input">
                    <option value="">All</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) $filters['cost_category_id'] === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </x-form.field>
            <x-tally::form.field name="branch_id" label="Branch">
                <select id="branch_id" name="branch_id" class="input">
                    <option value="all" @selected($filters['branch_id'] === null)>All branches</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) $filters['branch_id'] === (string) $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </x-form.field>
            <button class="btn btn-primary" type="submit">Apply</button>
        </form>
        <p class="form-note">Posted voucher lines that carry a cost centre. Drafts and cancelled vouchers are excluded.</p>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Centre</th><th>Category</th><th class="money">Debit</th><th class="money">Credit</th><th class="money">Net</th></tr></thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>{{ $row['centre'] }}</td>
                            <td>{{ $row['category'] }}</td>
                            <td class="money">{{ $row['debit'] }}</td>
                            <td class="money">{{ $row['credit'] }}</td>
                            <td class="money">{{ $row['net'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No cost-centre allocations in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-shell.page>
</x-layouts.app>
