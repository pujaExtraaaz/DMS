<x-tally::layouts.app title="Outstanding">
    <x-tally::shell.page title="Outstanding" section="Reports" :description="$company->name.' · '.$year->name">
        <form class="filters" method="GET">
            <x-tally::form.field name="to" label="As on"><x-tally::form.input name="to" type="date" value="{{ $filters['to'] }}" /></x-form.field>
            <x-tally::form.field name="branch_id" label="Branch">
                <select id="branch_id" name="branch_id" class="input">
                    <option value="all" @selected($filters['branch_id'] === null)>All branches</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) $filters['branch_id'] === (string) $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </x-form.field>
            @if ($filters['ledger_id'])
                <input type="hidden" name="ledger_id" value="{{ $filters['ledger_id'] }}">
            @endif
            <button class="btn btn-primary" type="submit">Apply</button>
        </form>
        @if ($filters['ledger_id'])
            <p class="form-note">Showing one party. <a href="{{ tally_route('books.tally.reports.outstanding') }}">Show every party</a></p>
        @endif
        @foreach (['Receivables' => $receivables, 'Payables' => $payables] as $title => $report)
            <h2>{{ $title }}</h2>
            <p>Open bills {{ $report['total'] }}. Advances {{ $report['advances'] }}. Net {{ $report['net'] }}.</p>
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Party</th><th>Bill</th><th>Date</th><th>Due</th><th class="money">Original</th><th class="money">Paid</th><th class="money">Outstanding</th></tr></thead>
                    <tbody>
                        @forelse ($report['rows'] as $row)
                            <tr>
                                <td>{{ $row['party'] }}</td>
                                <td>{{ $row['bill_number'] }}</td>
                                <td>{{ $row['bill_date'] }}</td>
                                <td>{{ $row['due_date'] }}</td>
                                <td class="money">{{ $row['original'] }}</td>
                                <td class="money">{{ $row['paid'] }}</td>
                                <td class="money">{{ $row['outstanding'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7">Nothing outstanding.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endforeach
    </x-shell.page>
</x-layouts.app>
