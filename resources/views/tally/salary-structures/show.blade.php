<x-tally::layouts.app title="Salary structure">
    <x-tally::shell.page title="{{ $structure->name }}" section="Payroll" :description="$company->name">
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Pay head</th><th class="money">Amount</th><th>% basic</th><th>Type</th></tr></thead>
                <tbody>
                    @forelse ($structure->lines as $line)
                        <tr>
                            <td>{{ $line->payHead?->name }}</td>
                            <td class="money">{{ $line->amount }}</td>
                            <td>{{ $line->percent_of_basic }}</td>
                            <td>{{ $line->is_earning ? 'Earning' : 'Deduction' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No lines.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-tally::shell.page>
</x-tally::layouts.app>
