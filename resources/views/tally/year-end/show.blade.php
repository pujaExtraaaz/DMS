<x-tally::layouts.app title="Year-end close">
    <x-tally::shell.page title="Year-end close" section="Utilities" :description="$company->name.' · '.$year->name">
        @if ($year->is_closed)
            <p class="flash">This financial year is already closed{{ $year->closed_at ? ' on '.$year->closed_at->format('d M Y') : '' }}.</p>
        @else
            <p>Closing will reverse P&amp;L into retained earnings, migrate balance-sheet openings to the next year, and lock all periods in {{ $year->name }}.</p>
            <form method="POST" action="{{ tally_route('books.tally.year-end.close') }}" class="stack">
                @csrf
                <x-tally::form.field name="retained_earnings_ledger_id" label="Retained earnings / capital ledger">
                    <select class="input" name="retained_earnings_ledger_id">
                        <option value="">Auto-detect</option>
                        @foreach ($ledgers as $ledger)
                            <option value="{{ $ledger->id }}" @selected((string) old('retained_earnings_ledger_id', $company->retained_earnings_ledger_id) === (string) $ledger->id)>{{ $ledger->name }}</option>
                        @endforeach
                    </select>
                </x-tally::form.field>
                <label class="field"><input type="checkbox" name="confirm" value="1" required> I confirm year-end close for {{ $year->name }}</label>
                <button class="btn btn-primary" type="submit">Close financial year</button>
            </form>
        @endif
    </x-tally::shell.page>
</x-tally::layouts.app>
