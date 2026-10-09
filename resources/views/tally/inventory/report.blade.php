<x-tally::layouts.app :title="$title">
    <x-tally::shell.page :title="$title" section="Reports" :description="$company->name">
        @isset($reconciliation)
            <p class="form-note">
                Stock value {{ $reconciliation['stock_value'] }}.
                Inventory ledgers {{ $reconciliation['ledger_value'] }}.
                Difference {{ $reconciliation['difference'] }}.
                @if ($reconciliation['agrees'])
                    Stock valuation agrees with the stock, raw material, and finished goods ledgers.
                @endif
            </p>
        @endisset
        @if ($report['rows'] === [])
            <x-tally::ui.empty-state title="Nothing to show" message="No stock matches this report." />
        @else
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            @foreach (array_keys($report['rows'][0]) as $heading)
                                @if (! str_ends_with($heading, '_id'))
                                    <th>{{ str_replace('_', ' ', ucfirst($heading)) }}</th>
                                @endif
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report['rows'] as $row)
                            <tr>
                                @foreach ($row as $key => $value)
                                    @if (! str_ends_with($key, '_id'))
                                        <td>{{ $value }}</td>
                                    @endif
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-shell.page>
</x-layouts.app>
