<x-tally::layouts.app title="Receivables ageing">
    <x-tally::shell.page title="Receivables ageing" section="Reports" :description="$company->name">
        <div class="filters">
            <form method="GET" class="filters" style="border:0;box-shadow:none;padding:0">
                <x-tally::form.field name="as_on" label="As on" size="sm"><x-tally::form.input type="date" name="as_on" value="{{ $asOn }}" size="sm" /></x-tally::form.field>
                <button class="btn btn-primary" type="submit">Refresh</button>
            </form>
            <form method="POST" action="{{ tally_route('books.tally.reports.collection-reminders.run') }}">@csrf<button class="btn" type="submit">Queue reminders</button></form>
        </div>
        <div class="audit">
            <div><dt>Current</dt><dd>{{ $totals['current'] }}</dd></div>
            <div><dt>1–30</dt><dd>{{ $totals['b1_30'] }}</dd></div>
            <div><dt>31–60</dt><dd>{{ $totals['b31_60'] }}</dd></div>
            <div><dt>61–90</dt><dd>{{ $totals['b61_90'] }}</dd></div>
            <div><dt>90+</dt><dd>{{ $totals['b90_plus'] }}</dd></div>
            <div><dt>Total</dt><dd>{{ $totals['total'] }}</dd></div>
        </div>
        <form method="POST" action="{{ tally_route('books.tally.reports.ageing.provision') }}" class="form-grid panel" style="margin-top:1rem">
            @csrf
            <input type="hidden" name="as_on" value="{{ $asOn }}">
            <x-tally::form.field name="b1_30" label="Provision % 1–30"><x-tally::form.input name="b1_30" value="5" /></x-tally::form.field>
            <x-tally::form.field name="b31_60" label="31–60"><x-tally::form.input name="b31_60" value="10" /></x-tally::form.field>
            <x-tally::form.field name="b61_90" label="61–90"><x-tally::form.input name="b61_90" value="25" /></x-tally::form.field>
            <x-tally::form.field name="b90_plus" label="90+"><x-tally::form.input name="b90_plus" value="50" /></x-tally::form.field>
            <div class="form-actions"><button class="btn" type="submit">Post provision</button></div>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Party</th><th>Bill</th><th>Due</th><th>Days</th><th class="money">Outstanding</th><th>Bucket</th><th></th></tr></thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>{{ $row['party'] }}</td>
                            <td>{{ $row['bill_number'] }}</td>
                            <td>{{ $row['due_date'] }}</td>
                            <td>{{ $row['days_overdue'] }}</td>
                            <td class="money">{{ $row['outstanding'] }}</td>
                            <td>{{ $row['bucket'] }}</td>
                            <td>
                                <form method="POST" action="{{ tally_route('books.tally.reports.ageing.write-off') }}">
                                    @csrf
                                    <input type="hidden" name="bill_id" value="{{ $row['bill_id'] ?? '' }}">
                                    @if (! empty($row['bill_id']))
                                        <button class="btn" type="submit">Write off</button>
                                    @endif
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">No open receivables.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-tally::shell.page>
</x-tally::layouts.app>
