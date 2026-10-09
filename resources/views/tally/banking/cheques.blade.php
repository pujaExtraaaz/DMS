<x-tally::layouts.app :title="$title">
    <x-tally::shell.page :title="$title" section="Banking" :description="$company->name">
        <a class="esc-back" data-esc href="{{ tally_route('books.tally.banking.menu') }}">Quit</a>
        <form class="panel" method="POST" action="{{ tally_route('books.tally.banking.cheques.store') }}">
            @csrf
            <x-tally::form.field name="number" label="Cheque no." required>
                <x-tally::form.input name="number" value="{{ old('number', $nextNumber) }}" required maxlength="30" />
            </x-form.field>
            <x-tally::form.field name="instrument_date" label="Date" required>
                <x-tally::form.input name="instrument_date" type="date" value="{{ old('instrument_date', now()->toDateString()) }}" required />
            </x-form.field>
            <x-tally::form.field name="amount" label="Amount" required>
                <x-tally::form.input name="amount" value="{{ old('amount') }}" inputmode="decimal" required />
            </x-form.field>
            <x-tally::form.field name="favouring" label="Favouring" required>
                <x-tally::form.input name="favouring" value="{{ old('favouring') }}" required maxlength="160" />
            </x-form.field>
            <x-tally::form.field name="bank_account_id" label="Bank">
                @include('tally::masters._picker', [
                    'name' => 'bank_account_id',
                    'options' => $accounts->mapWithKeys(fn ($account) => [$account->id => $account->bank_name.' · '.$account->account_number])->all(),
                    'current' => old('bank_account_id'),
                    'placeholder' => 'Bank',
                    'optional' => true,
                ])
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
            <x-tally::form.field name="narration" label="Narration">
                <x-tally::form.input name="narration" value="{{ old('narration') }}" maxlength="500" />
            </x-form.field>
            <button class="btn btn-primary" type="submit">Accept</button>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Cheque no.</th>
                        <th>Bank</th>
                        <th>Favouring</th>
                        <th class="money">Amount</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td colspan="7">
                                <form method="POST" action="{{ tally_route('books.tally.banking.cheques.update', $row) }}" class="filters">
                                    @csrf
                                    @method('PUT')
                                    <input class="input" type="date" name="instrument_date" value="{{ $row->instrument_date->toDateString() }}" required>
                                    <input class="input" name="number" value="{{ $row->number }}" required maxlength="30">
                                    <input type="hidden" name="bank_account_id" value="{{ $row->bank_account_id }}">
                                    <span>{{ $row->bankAccount?->bank_name }}</span>
                                    <input class="input" name="favouring" value="{{ $row->favouring }}" required>
                                    <input class="input money" name="amount" value="{{ $row->amount }}" required>
                                    <input type="hidden" name="party_ledger_id" value="{{ $row->party_ledger_id }}">
                                    <select class="input" name="status">
                                        @foreach (['open' => 'Not printed', 'printed' => 'Printed', 'deposited' => 'Deposited', 'cancelled' => 'Cancelled'] as $value => $label)
                                            <option value="{{ $value }}" @selected($row->status === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn" type="submit">Accept</button>
                                    @if ($mode === 'printing' || $row->status === 'open' || $row->status === 'printed')
                                        <a class="btn" href="{{ tally_route('books.tally.banking.cheques.print', $row) }}" target="_blank">Print</a>
                                    @endif
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">No cheques in this list.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-shell.page>
</x-layouts.app>
