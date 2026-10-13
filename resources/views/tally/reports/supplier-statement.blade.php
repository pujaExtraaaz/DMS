<x-tally::layouts.app title="Supplier statement">
    <x-tally::shell.page title="Supplier statement" section="Reports" :description="$company->name">
        <form method="GET" class="form-grid panel">
            <x-tally::form.field name="ledger_id" label="Supplier" required>
                <select class="input" name="ledger_id" required>
                    <option value="">Select</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected((string) ($filters['ledgerId'] ?? '') === (string) $supplier->id)>{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </x-tally::form.field>
            <x-tally::form.field name="from" label="From"><x-tally::form.input type="date" name="from" value="{{ $filters['from'] }}" /></x-tally::form.field>
            <x-tally::form.field name="to" label="To"><x-tally::form.input type="date" name="to" value="{{ $filters['to'] }}" /></x-tally::form.field>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Show</button>
                @if ($statement)
                    <a class="btn" href="{{ tally_route('books.tally.reports.supplier-confirmation', ['ledger_id' => $filters['ledgerId'], 'as_on' => $filters['to']]) }}">Confirmation letter</a>
                @endif
            </div>
        </form>
        @if ($statement)
            <p class="muted">Opening {{ $statement['opening'] }} · Closing {{ $statement['closing'] }}</p>
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Date</th><th>Particulars</th><th class="money">Debit</th><th class="money">Credit</th><th class="money">Balance</th></tr></thead>
                    <tbody>
                        @foreach ($statement['rows'] as $row)
                            <tr>
                                <td>{{ $row['date'] }}</td>
                                <td>{{ $row['particulars'] }}</td>
                                <td class="money">{{ $row['debit'] }}</td>
                                <td class="money">{{ $row['credit'] }}</td>
                                <td class="money">{{ $row['balance'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-tally::shell.page>
</x-tally::layouts.app>
