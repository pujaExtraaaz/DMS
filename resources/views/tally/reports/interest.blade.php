<x-tally::layouts.app title="Interest">
    <x-tally::shell.page title="Interest" section="Reports" :description="$company->name.' · '.$year->name">
        <form class="filters" method="GET">
            <x-tally::form.field name="to" label="As on"><x-tally::form.input name="to" type="date" value="{{ $filters['to'] }}" /></x-form.field>
            <x-tally::form.field name="rate" label="Annual rate %"><x-tally::form.input name="rate" value="{{ $filters['rate'] }}" /></x-form.field>
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
        <p class="form-note">Simple interest on the outstanding amount from the due date. This report does not post an interest voucher.</p>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Kind</th><th>Party</th><th>Bill</th><th>Due</th><th class="money">Outstanding</th><th>Days</th><th class="money">Interest</th></tr></thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>{{ $row['kind'] }}</td>
                            <td>{{ $row['party'] }}</td>
                            <td>{{ $row['bill_number'] }}</td>
                            <td>{{ $row['due_date'] }}</td>
                            <td class="money">{{ $row['outstanding'] }}</td>
                            <td>{{ $row['days'] }}</td>
                            <td class="money">{{ $row['interest'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7">No open bills.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-shell.page>
</x-layouts.app>
