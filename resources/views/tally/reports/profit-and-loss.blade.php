<x-tally::layouts.app title="Profit and loss">
    <x-tally::shell.page title="Profit and loss" section="Reports" :description="$company->name.' · '.$year->name.' · as on '.(\Illuminate\Support\Carbon::parse($filters['to'])->format('d M Y'))">
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
        <div class="sheet-grid">
            <section class="panel">
                <h2>Expenses</h2>
                @foreach (['Cost of goods sold' => $report['cogs'], 'Direct' => $report['direct_expense'], 'Indirect' => $report['indirect_expense']] as $heading => $rows)
                    <h3 class="kicker">{{ $heading }}</h3>
                    <table class="data">
                        <tbody>
                            @forelse ($rows as $row)
                                <tr><td>{{ $row['name'] }}</td><td class="money">{{ $row['amount'] }}</td></tr>
                            @empty
                                <tr><td colspan="2" class="muted">None</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                @endforeach
            </section>
            <section class="panel">
                <h2>Income</h2>
                @foreach (['Direct' => $report['direct_income'], 'Indirect' => $report['indirect_income']] as $heading => $rows)
                    <h3 class="kicker">{{ $heading }}</h3>
                    <table class="data">
                        <tbody>
                            @forelse ($rows as $row)
                                <tr><td>{{ $row['name'] }}</td><td class="money">{{ $row['amount'] }}</td></tr>
                            @empty
                                <tr><td colspan="2" class="muted">None</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                @endforeach
            </section>
        </div>
        <p class="form-note">Gross {{ $report['gross_side'] }} {{ $report['gross_profit'] }}. Net {{ $report['net_side'] }} {{ $report['net_profit'] }}. Closing inventory on the balance sheet is {{ $report['closing_inventory'] }}. Purchases still in stock are not charged to profit. Cost of goods sold is the cost of what was sold.</p>
    </x-shell.page>
</x-layouts.app>
