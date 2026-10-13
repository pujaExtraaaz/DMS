<x-tally::layouts.app title="Payment due calendar">
    <x-tally::shell.page title="Payment due calendar" section="Reports" :description="$company->name">
        <form method="GET" class="form-grid panel">
            <x-tally::form.field name="from" label="From"><x-tally::form.input type="date" name="from" value="{{ $filters['from'] }}" /></x-tally::form.field>
            <x-tally::form.field name="to" label="To"><x-tally::form.input type="date" name="to" value="{{ $filters['to'] }}" /></x-tally::form.field>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Show</button></div>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Due date</th><th>Supplier</th><th>Bill</th><th class="money">Amount</th></tr></thead>
                <tbody>
                    @forelse ($bills as $bill)
                        <tr>
                            <td>{{ $bill->due_date?->format('d M Y') }}</td>
                            <td>{{ $bill->ledger?->name }}</td>
                            <td>{{ $bill->bill_number }}</td>
                            <td class="money">{{ $bill->original_amount }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No bills due in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-tally::shell.page>
</x-tally::layouts.app>
