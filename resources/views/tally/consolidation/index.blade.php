<x-tally::layouts.app title="Consolidation">
    <x-tally::shell.page title="Consolidation" section="Entity" :description="$company->name">
        <x-slot:actions>
            <a class="btn" href="{{ tally_route('books.tally.consolidation.worksheet') }}">Worksheet</a>
        </x-slot:actions>
        <form method="GET" class="filters">
            <label>As of <input class="input" type="date" name="as_of" value="{{ $asOf }}"></label>
            <button class="btn" type="submit">Refresh</button>
        </form>
        <form method="POST" action="{{ tally_route('books.tally.consolidation.store') }}" class="form-grid panel">
            @csrf
            <x-tally::form.field name="subsidiary_company_id" label="Subsidiary" required>
                <select class="input" name="subsidiary_company_id" required>
                    @foreach ($companies as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                </select>
            </x-tally::form.field>
            <x-tally::form.field name="ownership_percent" label="Ownership %"><x-tally::form.input name="ownership_percent" value="100" /></x-tally::form.field>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Map subsidiary</button></div>
        </form>
        <form method="POST" action="{{ tally_route('books.tally.consolidation.eliminations') }}" class="form-grid panel">
            @csrf
            <x-tally::form.field name="from" label="From">
                <x-tally::form.input type="date" name="from" value="{{ $company->financial_year_start?->toDateString() }}" />
            </x-tally::form.field>
            <x-tally::form.field name="to" label="To">
                <x-tally::form.input type="date" name="to" value="{{ $asOf }}" />
            </x-tally::form.field>
            <x-tally::form.field name="elimination_ledger_id" label="Elimination ledger">
                <select class="input" name="elimination_ledger_id">
                    <option value="">Auto</option>
                    @foreach ($elimLedgers as $ledger)
                        <option value="{{ $ledger->id }}">{{ $ledger->name }}</option>
                    @endforeach
                </select>
            </x-tally::form.field>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Post eliminations</button></div>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Company</th>
                        <th class="money">Debit</th>
                        <th class="money">Credit</th>
                        <th class="money">Net</th>
                        <th class="money">Ownership %</th>
                        <th class="money">Group share</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($totals['companies'] as $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td class="money">{{ $row['debit'] }}</td>
                            <td class="money">{{ $row['credit'] }}</td>
                            <td class="money">{{ $row['net'] ?? '—' }}</td>
                            <td class="money">{{ $row['ownership_percent'] ?? '—' }}</td>
                            <td class="money">{{ $row['group_share'] ?? '—' }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td><strong>Total</strong></td>
                        <td class="money"><strong>{{ $totals['debit'] }}</strong></td>
                        <td class="money"><strong>{{ $totals['credit'] }}</strong></td>
                        <td colspan="3"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-tally::shell.page>
</x-tally::layouts.app>
