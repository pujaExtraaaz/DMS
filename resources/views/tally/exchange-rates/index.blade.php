<x-tally::layouts.app title="Exchange rates">
    <x-tally::shell.page title="Exchange rates" section="Masters" :description="$company->name">
        <form method="POST" action="{{ tally_route('books.tally.exchange-rates.store') }}" class="filters">
            @csrf
            <x-tally::form.field name="currency_id" label="Currency">
                <select class="input" name="currency_id" required>
                    @foreach ($currencies as $currency)
                        <option value="{{ $currency->id }}">{{ $currency->code }} · {{ $currency->name }}</option>
                    @endforeach
                </select>
            </x-tally::form.field>
            <x-tally::form.field name="rate_date" label="Date"><x-tally::form.input type="date" name="rate_date" value="{{ now()->toDateString() }}" required /></x-tally::form.field>
            <x-tally::form.field name="rate" label="Rate (to base)"><x-tally::form.input name="rate" type="number" step="0.000001" min="0.000001" required /></x-tally::form.field>
            <button class="btn btn-primary" type="submit">Save rate</button>
        </form>

        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Date</th><th>Currency</th><th class="money">Rate</th></tr></thead>
                <tbody>
                    @forelse ($rates as $rate)
                        <tr>
                            <td>{{ $rate->rate_date?->format('d M Y') }}</td>
                            <td>{{ $rate->currency?->code }}</td>
                            <td class="money">{{ $rate->rate }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3">No historical rates yet. Vouchers can still use the currency master rate.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $rates->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
