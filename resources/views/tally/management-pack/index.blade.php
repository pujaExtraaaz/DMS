<x-tally::layouts.app title="Management pack">
    <x-tally::shell.page title="Management pack" section="Reports" :description="$company->name.' · '.$from.' to '.$to">
        <form method="GET" class="form-grid panel">
            <x-tally::form.field name="from" label="From">
                <x-tally::form.input type="date" name="from" value="{{ $from }}" />
            </x-tally::form.field>
            <x-tally::form.field name="to" label="To">
                <x-tally::form.input type="date" name="to" value="{{ $to }}" />
            </x-tally::form.field>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Refresh</button>
                <button class="btn" type="button" onclick="window.print()">Print pack</button>
            </div>
        </form>

        <h3 style="margin-top:1.5rem">KPIs</h3>
        <dl class="kv">
            <dt>DSO</dt><dd>{{ $kpi->dso ?? '—' }}</dd>
            <dt>DPO</dt><dd>{{ $kpi->dpo ?? '—' }}</dd>
            <dt>Working capital</dt><dd>{{ $kpi->working_capital ?? '—' }}</dd>
        </dl>

        <h3 style="margin-top:1.5rem">Quick links</h3>
        <p>
            <a class="btn" href="{{ tally_route('books.tally.reports.trial-balance') }}">Trial Balance</a>
            <a class="btn" href="{{ tally_route('books.tally.reports.profit-and-loss') }}">Profit &amp; Loss</a>
            <a class="btn" href="{{ tally_route('books.tally.reports.balance-sheet') }}">Balance Sheet</a>
            <a class="btn" href="{{ tally_route('books.tally.kpi-dashboard.index') }}">KPI Dashboard</a>
            <a class="btn" href="{{ tally_route('books.tally.profitability.index') }}">Profitability</a>
            <a class="btn" href="{{ tally_route('books.tally.reports.comparative') }}">Comparative</a>
        </p>

        <h3 style="margin-top:1.5rem">Trial balance (as on {{ $to }})</h3>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Ledger</th><th class="money">Debit</th><th class="money">Credit</th></tr></thead>
                <tbody>
                    @foreach (array_slice($trialBalance['rows'] ?? [], 0, 40) as $row)
                        <tr>
                            <td>{{ $row['ledger'] ?? '—' }}</td>
                            <td class="money">{{ $row['debit'] ?: '—' }}</td>
                            <td class="money">{{ $row['credit'] ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="form-note">Full statements remain on their report screens; this pack is the period-end management summary.</p>
    </x-tally::shell.page>
</x-tally::layouts.app>
