<x-tally::layouts.app title="Day book">
    <x-tally::shell.page title="Day book" section="Reports" :description="$company->name.' · '.$year->name">
        <a class="esc-back" data-esc href="{{ tally_route('books.tally.reports.menu') }}">Quit</a>
        <form class="filters" method="GET">
            <x-tally::form.field name="q" label="Search"><x-tally::form.input name="q" value="{{ $filters['q'] }}" placeholder="Number, narration, or reference" /></x-form.field>
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
        <p class="form-note">Posted vouchers only. Each row is one voucher. Debit and credit on a posted voucher are equal.</p>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Number</th>
                        <th>Type</th>
                        <th>Particulars</th>
                        <th class="money">Debit</th>
                        <th class="money">Credit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report['rows'] as $row)
                        <tr>
                            <td>{{ $row['date'] }}</td>
                            <td><a href="{{ $row['url'] }}">{{ $row['number'] }}</a></td>
                            <td>{{ $row['type'] }}</td>
                            <td>{{ $row['particulars'] }}@if ($row['narration'])<div class="muted">{{ $row['narration'] }}</div>@endif</td>
                            <td class="money">{{ $row['debit'] }}</td>
                            <td class="money">{{ $row['credit'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No posted vouchers in this period.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4">Total</th>
                        <th class="money">{{ $report['debit'] }}</th>
                        <th class="money">{{ $report['credit'] }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
        {{ $report['paginator']->links() }}
    </x-shell.page>
</x-layouts.app>
