<x-tally::layouts.app title="Payroll">
    <x-tally::shell.page title="Payroll" section="Transactions" :description="$run->period_start->format('d M Y').' – '.$run->period_end->format('d M Y')">
        <p>Status: {{ ucfirst($run->status) }}</p>
        @if ($run->voucher)
            <p>Journal <a href="{{ tally_route('books.tally.vouchers.show', $run->voucher) }}">{{ $run->voucher->voucher_number }}</a></p>
        @endif
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Employee</th><th class="money">Days</th><th class="money">Earnings</th><th class="money">PF</th><th class="money">ESI</th><th class="money">Deductions</th><th class="money">Net</th></tr></thead>
                <tbody>
                    @foreach ($run->lines as $line)
                        <tr>
                            <td>{{ $line->employee?->name }}</td>
                            <td class="money">{{ $line->payable_days }}</td>
                            <td class="money">{{ $line->earnings }}</td>
                            <td class="money">{{ $line->pf_amount }}</td>
                            <td class="money">{{ $line->esi_amount }}</td>
                            <td class="money">{{ $line->deductions }}</td>
                            <td class="money">{{ $line->net }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($run->status !== 'processed')
            <form method="POST" action="{{ tally_route('books.tally.payroll.process', $run) }}">
                @csrf
                <button class="btn btn-primary" type="submit">Post payroll journal</button>
            </form>
        @endif
    </x-shell.page>
</x-layouts.app>
