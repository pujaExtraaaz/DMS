<x-tally::layouts.app title="Consolidation worksheet">
    <x-tally::shell.page title="Consolidation worksheet" section="Entity" :description="$company->name">
        <x-slot:actions>
            <a class="btn" href="{{ tally_route('books.tally.consolidation.index') }}">Back</a>
        </x-slot:actions>
        <form method="GET" class="filters">
            <label>From <input class="input" type="date" name="from" value="{{ $sheet['from'] }}"></label>
            <label>To <input class="input" type="date" name="to" value="{{ $sheet['to'] }}"></label>
            <button class="btn" type="submit">Refresh</button>
        </form>

        <h3>Parent IC pairs</h3>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Pair</th><th>Counterparty</th><th>Date</th><th class="money">Amount</th><th>Eliminated</th></tr></thead>
                <tbody>
                    @forelse (($sheet['parent_ic']['rows'] ?? []) as $row)
                        <tr>
                            <td>{{ \Illuminate\Support\Str::limit($row['pair_id'] ?? '', 12) }}</td>
                            <td>{{ $row['counterparty'] ?? '' }}</td>
                            <td>{{ $row['date'] ?? '' }}</td>
                            <td class="money">{{ $row['amount'] ?? '0.00' }}</td>
                            <td>{{ ! empty($row['eliminated']) ? 'Yes' : 'No' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No intercompany pairs.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @foreach ($sheet['subsidiaries'] as $sub)
            <h3>{{ $sub['name'] }} ({{ $sub['ownership_percent'] }}%)</h3>
            <p class="muted">TB lines: {{ count($sub['trial_balance']['rows'] ?? []) }} · IC pairs: {{ count($sub['ic_pairs'] ?? []) }}</p>
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Pair</th><th>Counterparty</th><th>Date</th><th class="money">Amount</th><th>Eliminated</th></tr></thead>
                    <tbody>
                        @forelse ($sub['ic_pairs'] as $row)
                            <tr>
                                <td>{{ \Illuminate\Support\Str::limit($row['pair_id'] ?? '', 12) }}</td>
                                <td>{{ $row['counterparty'] ?? $row['source_company'] ?? '' }}</td>
                                <td>{{ $row['date'] ?? '' }}</td>
                                <td class="money">{{ $row['amount'] ?? '0.00' }}</td>
                                <td>{{ ! empty($row['eliminated']) ? 'Yes' : 'No' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5">No IC pairs for this subsidiary.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endforeach
    </x-tally::shell.page>
</x-tally::layouts.app>
