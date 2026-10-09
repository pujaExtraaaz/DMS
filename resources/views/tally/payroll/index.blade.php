<x-tally::layouts.app title="Payroll">
    <x-tally::shell.page title="Payroll" section="Transactions" :description="$company->name">
        <x-slot:actions>
            <a class="btn" href="{{ tally_route('books.tally.employees.index') }}">Employees</a>
            <a class="btn" href="{{ tally_route('books.tally.pay-heads.index') }}">Pay heads</a>
            <a class="btn btn-primary" href="{{ tally_route('books.tally.payroll.create') }}">Process payroll</a>
        </x-slot:actions>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Period</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($runs as $run)
                        <tr>
                            <td><a href="{{ tally_route('books.tally.payroll.show', $run) }}">{{ $run->period_start->format('d M Y') }} – {{ $run->period_end->format('d M Y') }}</a></td>
                            <td>{{ ucfirst($run->status) }}</td>
                            <td>
                                @if ($run->status !== 'processed')
                                    <x-tally::ui.record-actions :delete="tally_route('books.tally.payroll.destroy', $run)" />
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3">No payroll runs.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $runs->links() }}
    </x-shell.page>
</x-layouts.app>
