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
        @if ($errors->any())
            <p class="flash is-error" role="alert">{{ $errors->first() }}</p>
        @endif
        <p class="form-note">Simple interest on the outstanding amount from the due date. A ledger with interest turned on uses its own rate and style. Other bills use the annual rate above. Post interest writes one journal: customers are debited and Interest Received is credited, or Interest Paid is debited and suppliers are credited. The same bill is not charged twice for this date.</p>
        <form method="POST" action="{{ tally_route('books.tally.reports.interest.post') }}">
            @csrf
            <input type="hidden" name="to" value="{{ $filters['to'] }}">
            <input type="hidden" name="rate" value="{{ $filters['rate'] }}">
            <input type="hidden" name="branch_id" value="{{ $filters['branch_id'] ?? 'all' }}">
            <button class="btn btn-primary" type="submit">Post interest voucher</button>
        </form>
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
