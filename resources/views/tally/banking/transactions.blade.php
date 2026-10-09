<x-tally::layouts.app title="Bank transactions">
    <x-tally::shell.page title="Bank transactions" section="Transactions" :description="$company->name.' · '.$year->name">
        <x-slot:actions>
            <button class="btn" type="button" onclick="window.print()">Print</button>
        </x-slot:actions>
        @include('tally::banking._filters', ['action' => tally_route('books.tally.banking.transactions')])
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Voucher</th>
                        <th>Ledger</th>
                        <th>Narration</th>
                        <th class="money">Book amount</th>
                        <th>Status</th>
                        <th class="money">Difference</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>{{ $row['date'] }}</td>
                            <td><a href="{{ $row['url'] }}">{{ $row['number'] }}</a></td>
                            <td>{{ $row['ledger'] }}</td>
                            <td>{{ $row['narration'] ?: '—' }}</td>
                            <td class="money">{{ $row['book_amount'] }} {{ $row['book_side'] }}</td>
                            <td>{{ $row['status']->label() }}</td>
                            <td class="money">{{ $row['difference'] ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7">No posted bank transactions in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-shell.page>
</x-layouts.app>
