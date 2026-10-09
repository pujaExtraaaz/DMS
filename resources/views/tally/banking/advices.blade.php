<x-tally::layouts.app title="Payment Advice">
    <x-tally::shell.page title="Payment Advice" section="Banking" :description="$company->name">
        <a class="esc-back" data-esc href="{{ tally_route('books.tally.banking.menu') }}">Quit</a>
        <form class="panel" method="POST" action="{{ tally_route('books.tally.banking.advices.store') }}">
            @csrf
            <x-tally::form.field name="advice_number" label="Advice no." required>
                <x-tally::form.input name="advice_number" value="{{ old('advice_number', $nextNumber) }}" required />
            </x-form.field>
            <x-tally::form.field name="advice_date" label="Date" required>
                <x-tally::form.input name="advice_date" type="date" value="{{ old('advice_date', now()->toDateString()) }}" required />
            </x-form.field>
            <x-tally::form.field name="amount" label="Amount" required>
                <x-tally::form.input name="amount" value="{{ old('amount') }}" required />
            </x-form.field>
            <x-tally::form.field name="party_ledger_id" label="Party">
                @include('tally::masters._picker', [
                    'name' => 'party_ledger_id',
                    'options' => $parties->mapWithKeys(fn ($ledger) => [$ledger->id => $ledger->name])->all(),
                    'current' => old('party_ledger_id'),
                    'placeholder' => 'Party',
                    'optional' => true,
                    'create' => tally_route('books.tally.ledgers.create'),
                ])
            </x-form.field>
            <x-tally::form.field name="bank_account_id" label="Bank">
                @include('tally::masters._picker', [
                    'name' => 'bank_account_id',
                    'options' => $accounts->mapWithKeys(fn ($account) => [$account->id => $account->bank_name])->all(),
                    'current' => old('bank_account_id'),
                    'placeholder' => 'Bank',
                    'optional' => true,
                ])
            </x-form.field>
            <x-tally::form.field name="narration" label="Narration">
                <x-tally::form.input name="narration" value="{{ old('narration') }}" />
            </x-form.field>
            <button class="btn btn-primary" type="submit">Accept</button>
        </form>
        <table class="data">
            <thead><tr><th>Date</th><th>Advice no.</th><th>Party</th><th>Bank</th><th class="money">Amount</th><th>Narration</th></tr></thead>
            <tbody>
                @forelse ($advices as $advice)
                    <tr>
                        <td>{{ $advice->advice_date->format('d M Y') }}</td>
                        <td>{{ $advice->advice_number }}</td>
                        <td>{{ $advice->party?->name }}</td>
                        <td>{{ $advice->bankAccount?->bank_name }}</td>
                        <td class="money">{{ $advice->amount }}</td>
                        <td>{{ $advice->narration }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">No payment advice in this year.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-shell.page>
</x-layouts.app>
