<x-tally::layouts.app title="Payroll Report">
    <x-tally::shell.page title="Payroll Report" section="Reports" :description="$company->name">
        @forelse ($runs as $run)
            <h2>{{ $run->period_start->format('d M Y') }} – {{ $run->period_end->format('d M Y') }}</h2>
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Employee</th><th class="money">Earnings</th><th class="money">PF</th><th class="money">Employer PF</th><th class="money">ESI</th><th class="money">Employer ESI</th><th class="money">Deductions</th><th class="money">Net</th></tr></thead>
                    <tbody>
                        @foreach ($run->lines as $line)
                            <tr>
                                <td>{{ $line->employee?->name }}</td>
                                <td class="money">{{ $line->earnings }}</td>
                                <td class="money">{{ $line->pf_amount }}</td>
                                <td class="money">{{ $line->employer_pf_amount }}</td>
                                <td class="money">{{ $line->esi_amount }}</td>
                                <td class="money">{{ $line->employer_esi_amount }}</td>
                                <td class="money">{{ $line->deductions }}</td>
                                <td class="money">{{ $line->net }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @empty
            <p>No processed payroll in this financial year.</p>
        @endforelse
        <p><a href="{{ tally_route('books.tally.payroll.challan') }}">PF and ESI challan</a></p>
    </x-shell.page>
</x-layouts.app>
