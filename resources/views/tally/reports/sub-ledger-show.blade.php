<x-tally::layouts.app :title="'Sub-ledger · '.$ledger->name">
    <x-tally::shell.page :title="$ledger->name" section="Sub-ledger" :description="$company->name">
        <form method="GET" class="filters">
            <x-tally::form.input type="date" name="from" value="{{ $from }}" />
            <x-tally::form.input type="date" name="to" value="{{ $to }}" />
            <button class="btn" type="submit">Apply</button>
            <a class="btn" href="{{ tally_route('books.tally.reports.sub-ledgers') }}">Back</a>
        </form>
        <h3>Posted movements</h3>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Date</th><th>Voucher</th><th>Narration</th><th>Debit</th><th>Credit</th></tr></thead>
                <tbody>
                    @forelse ($entries as $entry)
                        <tr>
                            <td>{{ $entry->voucher?->voucher_date?->format('d M Y') }}</td>
                            <td><a href="{{ tally_route('books.tally.vouchers.show', $entry->voucher) }}">{{ $entry->voucher?->voucher_number }}</a></td>
                            <td>{{ $entry->narration ?: $entry->voucher?->narration }}</td>
                            <td>{{ $entry->debit }}</td>
                            <td>{{ $entry->credit }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No movements in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <h3>Bills</h3>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Bill</th><th>Date</th><th>Due</th><th>Amount</th><th>Side</th></tr></thead>
                <tbody>
                    @forelse ($bills as $bill)
                        <tr>
                            <td>{{ $bill->bill_number }}</td>
                            <td>{{ $bill->bill_date?->format('d M Y') }}</td>
                            <td>{{ $bill->due_date?->format('d M Y') ?: '—' }}</td>
                            <td>{{ $bill->original_amount }}</td>
                            <td>{{ $bill->side?->value ?? $bill->side }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No bills.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-tally::shell.page>
</x-tally::layouts.app>
