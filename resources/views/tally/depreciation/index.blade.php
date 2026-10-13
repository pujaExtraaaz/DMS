<x-tally::layouts.app title="Depreciation">
    <x-tally::shell.page title="Depreciation" section="Fixed assets" :description="$company->name">
        <form method="POST" class="panel form-grid" action="{{ tally_route('books.tally.depreciation.run') }}" style="margin-bottom:1rem">
            @csrf
            <x-tally::form.field name="period" label="Period (YYYY-MM)" required>
                <x-tally::form.input name="period" value="{{ old('period', $defaultPeriod) }}" required />
            </x-tally::form.field>
            <x-tally::form.field name="entry_date" label="Entry date">
                <x-tally::form.input type="date" name="entry_date" value="{{ old('entry_date') }}" />
            </x-tally::form.field>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Run depreciation</button></div>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Period</th><th>Asset</th><th>Date</th><th class="money">Amount</th><th>Method</th></tr></thead>
                <tbody>
                    @forelse ($entries as $entry)
                        <tr>
                            <td>{{ $entry->period }}</td>
                            <td>
                                @if ($entry->fixedAsset)
                                    <a href="{{ tally_route('books.tally.fixed-assets.show', $entry->fixedAsset) }}">{{ $entry->fixedAsset->asset_code }}</a>
                                    — {{ $entry->fixedAsset->name }}
                                @endif
                            </td>
                            <td>{{ $entry->entry_date->format('d M Y') }}</td>
                            <td class="money">{{ $entry->amount }}</td>
                            <td>{{ $entry->method }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No depreciation entries yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $entries->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
