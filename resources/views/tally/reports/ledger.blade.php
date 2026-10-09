<x-tally::layouts.app title="Ledger">
    <x-tally::shell.page title="Ledger" section="Reports" :description="$company->name.' · '.$year->name">
        <form class="filters" method="GET">
            <x-tally::form.field name="ledger_id" label="Ledger">
                <select id="ledger_id" name="ledger_id" class="input">
                    <option value="">Select a ledger</option>
                    @foreach ($ledgers as $ledger)
                        <option value="{{ $ledger->id }}" @selected((string) $filters['ledger_id'] === (string) $ledger->id)>{{ $ledger->name }}</option>
                    @endforeach
                </select>
            </x-form.field>
            <x-tally::form.field name="from" label="From"><x-tally::form.input name="from" type="date" value="{{ $filters['from'] }}" /></x-form.field>
            <x-tally::form.field name="to" label="To"><x-tally::form.input name="to" type="date" value="{{ $filters['to'] }}" /></x-form.field>
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
        @if (! $report['ledger'])
            <section class="empty"><h2>Choose a ledger</h2><p>The statement shows the opening balance, posted lines, and the closing balance.</p></section>
        @else
            <p class="form-note">Opening {{ $report['opening'] }} {{ $report['opening_side'] }}. Closing {{ $report['closing'] }} {{ $report['closing_side'] }}.</p>
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Number</th>
                            <th>Type</th>
                            <th>Narration</th>
                            <th class="money">Debit</th>
                            <th class="money">Credit</th>
                            <th class="money">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($report['rows'] as $row)
                            <tr>
                                <td>{{ $row['date'] }}</td>
                                <td><a href="{{ $row['url'] }}">{{ $row['number'] }}</a></td>
                                <td>{{ $row['type'] }}</td>
                                <td>{{ $row['narration'] }}</td>
                                <td class="money">{{ $row['debit'] }}</td>
                                <td class="money">{{ $row['credit'] }}</td>
                                <td class="money">{{ $row['balance'] }} {{ $row['side'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7">No posted lines in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </x-shell.page>
</x-layouts.app>
