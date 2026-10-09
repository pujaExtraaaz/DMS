<x-tally::layouts.app title="Budgets">
    <x-tally::shell.page title="Budgets" section="Masters" :description="$company->name.' · '.$year->name">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ tally_route('books.tally.budgets.create') }}">New budget</a>
        </x-slot:actions>
        @if ($rows->isEmpty())
            <x-tally::ui.empty-state title="No budgets" message="A budget compares a target with posted vouchers in its period." />
        @else
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Name</th><th>Period</th><th>Applies to</th><th class="money">Budget</th><th class="money">Actual</th><th class="money">Variance</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td>{{ $row['budget']->name }}</td>
                                <td>{{ $row['budget']->period_start->format('d M Y') }} – {{ $row['budget']->period_end->format('d M Y') }}</td>
                                <td>{{ $row['budget']->ledger?->name ?? $row['budget']->accountGroup?->name ?? $row['budget']->costCentre?->name }}</td>
                                <td class="money">{{ $row['figures']['budget'] }}</td>
                                <td class="money">{{ $row['figures']['actual'] }}</td>
                                <td class="money">{{ $row['figures']['variance'] }}</td>
                                <td>
                                    <x-tally::ui.record-actions
                                        :edit="tally_route('books.tally.budgets.edit', $row['budget'])"
                                        :delete="tally_route('books.tally.budgets.destroy', $row['budget'])"
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-shell.page>
</x-layouts.app>
