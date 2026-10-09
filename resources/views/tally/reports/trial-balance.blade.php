<x-tally::layouts.app title="Trial balance">
    <x-tally::shell.page title="Trial balance" section="Reports" :description="$company->name.' · '.$year->name.' · as on '.(\Illuminate\Support\Carbon::parse($filters['to'])->format('d M Y'))">
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
            <button class="btn btn-primary" type="submit">Apply</button>
        </form>
        <p class="form-note">Opening balances are the figures stored for this financial year. A branch filter includes only openings owned by that branch, plus that branch’s posted vouchers.</p>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Ledger</th>
                        <th>Group</th>
                        <th class="money">Debit</th>
                        <th class="money">Credit</th>
                        @if ($report['foreign'] ?? false)
                            <th class="money">Foreign debit</th>
                            <th class="money">Foreign credit</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report['rows'] as $row)
                        <tr>
                            <td>{{ $row['ledger'] }}</td>
                            <td>{{ $row['group'] }}</td>
                            <td class="money">{{ $row['debit'] }}</td>
                            <td class="money">{{ $row['credit'] }}</td>
                            @if ($report['foreign'] ?? false)
                                <td class="money">{{ $row['foreign_debit'] }}</td>
                                <td class="money">{{ $row['foreign_credit'] }}</td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ ($report['foreign'] ?? false) ? 6 : 4 }}">No balances yet.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2">Total</th>
                        <th class="money">{{ $report['debit'] }}</th>
                        <th class="money">{{ $report['credit'] }}</th>
                        @if ($report['foreign'] ?? false)
                            <th></th>
                            <th></th>
                        @endif
                    </tr>
                </tfoot>
            </table>
        </div>
        @if ($report['debit'] !== $report['credit'])
            <p class="flash is-error" role="alert">Debit {{ $report['debit'] }} does not equal credit {{ $report['credit'] }}.</p>
        @endif
    </x-shell.page>
</x-layouts.app>
