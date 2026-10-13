<x-tally::layouts.app title="KPI dashboard">
    <x-tally::shell.page title="KPI dashboard" section="Analytics" :description="$company->name">
        <form method="POST" action="{{ tally_route('books.tally.kpi-dashboard.store') }}" class="form-grid panel">
            @csrf
            <x-tally::form.field name="as_of" label="As of" required><x-tally::form.input type="date" name="as_of" :value="$asOf" required /></x-tally::form.field>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Compute snapshot</button></div>
        </form>
        @if ($snapshot)
            <dl class="meta">
                <div><dt>DSO</dt><dd>{{ $snapshot->dso ?? '—' }}</dd></div>
                <div><dt>DPO</dt><dd>{{ $snapshot->dpo ?? '—' }}</dd></div>
                <div><dt>Working capital</dt><dd>{{ $snapshot->working_capital ?? '—' }}</dd></div>
                <div><dt>As of</dt><dd>{{ $snapshot->as_of_date->format('d M Y') }}</dd></div>
            </dl>
        @endif
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Date</th><th>DSO</th><th>DPO</th><th class="money">WC</th></tr></thead>
                <tbody>
                    @forelse ($history as $row)
                        <tr>
                            <td>{{ $row->as_of_date->format('d M Y') }}</td>
                            <td>{{ $row->dso }}</td>
                            <td>{{ $row->dpo }}</td>
                            <td class="money">{{ $row->working_capital }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No snapshots yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-tally::shell.page>
</x-tally::layouts.app>
