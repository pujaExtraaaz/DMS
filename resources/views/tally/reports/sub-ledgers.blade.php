<x-tally::layouts.app title="Sub-ledgers">
    <x-tally::shell.page title="Sub-ledgers (AR / AP)" section="Reports" :description="$company->name">
        <form method="GET" class="filters">
            <select class="input" name="kind">
                <option value="receivables" @selected($kind === 'receivables')>Receivables</option>
                <option value="payables" @selected($kind === 'payables')>Payables</option>
                <option value="control" @selected($kind === 'control')>Control accounts</option>
            </select>
            <x-tally::form.input type="date" name="as_on" value="{{ $asOn }}" />
            <button class="btn btn-primary" type="submit">Apply</button>
        </form>

        @if ($kind === 'control')
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Control Account</th><th>Sub-Ledgers</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($controlAccounts as $control)
                            <tr>
                                <td>{{ $control->name }}</td>
                                <td>{{ $control->subLedgers->count() }}</td>
                                <td class="row-actions">
                                    <a href="{{ tally_route('books.tally.reports.sub-ledgers.show', $control) }}">Open</a>
                                </td>
                            </tr>
                            @foreach ($control->subLedgers as $child)
                                <tr>
                                    <td class="muted">↳ {{ $child->name }}</td>
                                    <td>—</td>
                                    <td class="row-actions">
                                        <a href="{{ tally_route('books.tally.reports.sub-ledgers.show', $child) }}">Open</a>
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr><td colspan="3">Mark ledgers as control accounts on the ledger form, then attach sub-ledgers via Control account.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <p>Total outstanding: <strong>{{ $total }}</strong></p>
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Party / Bill</th><th>Date</th><th>Due</th><th>Original</th><th>Outstanding</th></tr></thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td>{{ $row['party'] ?? '—' }} · {{ $row['bill_number'] ?? '' }}</td>
                                <td>{{ $row['bill_date'] ?? '—' }}</td>
                                <td>{{ $row['due_date'] ?? '—' }}</td>
                                <td>{{ $row['original'] ?? '—' }}</td>
                                <td>{{ $row['outstanding'] ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5">No open bills.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <h3>Party / sub-ledgers</h3>
            <ul>
                @foreach ($ledgers as $ledger)
                    <li>
                        <a href="{{ tally_route('books.tally.reports.sub-ledgers.show', $ledger) }}">{{ $ledger->name }}</a>
                        @if ($ledger->parentLedger)
                            <span class="muted">· under {{ $ledger->parentLedger->name }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </x-tally::shell.page>
</x-tally::layouts.app>
