<x-tally::layouts.app title="Tax accounts">
    <x-tally::shell.page title="Tax accounts" section="Masters" :description="$company->name">
        <form class="panel" method="POST" action="{{ tally_route('books.tally.tax-accounts.update') }}">
            @csrf
            @method('PUT')
            <div class="form-grid">
                @foreach ($components as $taxComponent)
                    <x-tally::form.field :name="$taxComponent->value.'_ledger_id'" :label="$taxComponent->label().' ledger'">
                        <select id="{{ $taxComponent->value }}_ledger_id" name="{{ $taxComponent->value }}_ledger_id" class="input">
                            <option value="">Not mapped</option>
                            @foreach ($ledgers as $ledger)
                                <option value="{{ $ledger->id }}" @selected((string) old($taxComponent->value.'_ledger_id', $accounts[$taxComponent->value]->ledger_id ?? '') === (string) $ledger->id)>{{ $ledger->name }}</option>
                            @endforeach
                        </select>
                    </x-form.field>
                @endforeach
            </div>
            <p class="form-note">These ledgers must sit under Duties & Taxes. Sales invoices credit them. Purchase invoices debit them. Leave a component unmapped until you need it.</p>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Save tax ledgers</button>
            </div>
        </form>
    </x-shell.page>
</x-layouts.app>
