<x-tally::layouts.app title="Payables ageing">
    <x-tally::shell.page title="Payables ageing" section="Reports" :description="$company->name">
        <div class="filters">
            <form method="GET" class="filters" style="border:0;box-shadow:none;padding:0">
                <x-tally::form.field name="as_on" label="As on" size="sm"><x-tally::form.input type="date" name="as_on" value="{{ $asOn }}" size="sm" /></x-tally::form.field>
                <button class="btn btn-primary" type="submit">Refresh</button>
            </form>
        </div>
        <div class="audit">
            <div><dt>Current</dt><dd>{{ $totals['current'] }}</dd></div>
            <div><dt>1–30</dt><dd>{{ $totals['b1_30'] }}</dd></div>
            <div><dt>31–60</dt><dd>{{ $totals['b31_60'] }}</dd></div>
            <div><dt>61–90</dt><dd>{{ $totals['b61_90'] }}</dd></div>
            <div><dt>90+</dt><dd>{{ $totals['b90_plus'] }}</dd></div>
            <div><dt>Total</dt><dd>{{ $totals['total'] }}</dd></div>
        </div>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Party</th><th>Bill</th><th>Due</th><th>Days</th><th class="money">Outstanding</th><th>Bucket</th></tr></thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>{{ $row['party'] }}</td>
                            <td>{{ $row['bill_number'] }}</td>
                            <td>{{ $row['due_date'] }}</td>
                            <td>{{ $row['days_overdue'] }}</td>
                            <td class="money">{{ $row['outstanding'] }}</td>
                            <td>{{ $row['bucket'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No open payables.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-tally::shell.page>
</x-tally::layouts.app>
