<x-tally::layouts.app title="FX revaluation">
    <x-tally::shell.page title="FX revaluation" section="Utilities" :description="$company->name.' · '.$year->name">
        <p class="form-note">Posts an unrealized gain/loss journal for foreign-currency ledger balances using historical (or master) rates as on the selected date.</p>
        <form method="POST" action="{{ tally_route('books.tally.fx-revaluation.store') }}" class="stack">
            @csrf
            <div class="form-grid">
                <x-tally::form.field name="as_on" label="As on" required>
                    <x-tally::form.input type="date" name="as_on" value="{{ old('as_on', now()->toDateString()) }}" required />
                </x-tally::form.field>
                <x-tally::form.field name="currency_id" label="Currency">
                    <select class="input" name="currency_id">
                        <option value="">All foreign currencies</option>
                        @foreach ($currencies as $currency)
                            <option value="{{ $currency->id }}">{{ $currency->code }}</option>
                        @endforeach
                    </select>
                </x-tally::form.field>
                <x-tally::form.field name="gain_loss_ledger_id" label="FX gain/loss ledger" required class="span-full">
                    <select class="input" name="gain_loss_ledger_id" required>
                        <option value="">Select ledger</option>
                        @foreach ($ledgers as $ledger)
                            <option value="{{ $ledger->id }}">{{ $ledger->name }}</option>
                        @endforeach
                    </select>
                </x-tally::form.field>
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Post revaluation</button>
                <a class="btn" href="{{ tally_route('books.tally.exchange-rates.index') }}">Exchange rates</a>
            </div>
        </form>
    </x-tally::shell.page>
</x-tally::layouts.app>
