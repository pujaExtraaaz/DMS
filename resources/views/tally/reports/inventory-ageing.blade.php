<x-tally::layouts.app title="Inventory ageing">
    <x-tally::shell.page title="Inventory ageing" section="Reports" :description="$company->name">
        <div class="filters">
            <form method="GET" class="filters" style="border:0;box-shadow:none;padding:0">
                <x-tally::form.field name="as_on" label="As on" size="sm">
                    <x-tally::form.input type="date" name="as_on" value="{{ $asOn }}" size="sm" />
                </x-tally::form.field>
                <button class="btn btn-primary" type="submit">Refresh</button>
            </form>
        </div>
        @if (! empty($valuationNote))
            <p class="muted" style="margin:0.75rem 0">{{ $valuationNote }}</p>
        @endif
        <div class="audit">
            <div><dt>0–30</dt><dd>{{ number_format($totals['b0_30'], 4) }}</dd></div>
            <div><dt>31–60</dt><dd>{{ number_format($totals['b31_60'], 4) }}</dd></div>
            <div><dt>61–90</dt><dd>{{ number_format($totals['b61_90'], 4) }}</dd></div>
            <div><dt>90+</dt><dd>{{ number_format($totals['b90_plus'], 4) }}</dd></div>
            <div><dt>Total</dt><dd>{{ number_format($totals['total'], 4) }}</dd></div>
        </div>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Product</th><th>Code</th><th class="money">Qty</th><th>Last movement</th><th>Days</th><th>Bucket</th></tr></thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>{{ $row['product'] }}</td>
                            <td>{{ $row['code'] ?: '—' }}</td>
                            <td class="money">{{ $row['quantity'] }}</td>
                            <td>{{ $row['last_movement'] }}</td>
                            <td>{{ $row['days'] }}</td>
                            <td>
                                @switch($row['bucket'])
                                    @case('b0_30') 0–30 @break
                                    @case('b31_60') 31–60 @break
                                    @case('b61_90') 61–90 @break
                                    @default 90+
                                @endswitch
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No on-hand stock to age.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-tally::shell.page>
</x-tally::layouts.app>
