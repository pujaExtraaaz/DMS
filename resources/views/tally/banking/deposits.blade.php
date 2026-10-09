<x-tally::layouts.app title="Deposit Slip">
    <x-tally::shell.page title="Deposit Slip" section="Banking" :description="$company->name">
        <a class="esc-back" data-esc href="{{ tally_route('books.tally.banking.menu') }}">Quit</a>
        <form class="panel" method="POST" action="{{ tally_route('books.tally.banking.deposits.store') }}">
            @csrf
            <div class="form-grid">
                <x-tally::form.field name="slip_number" label="Slip no." required>
                    <x-tally::form.input name="slip_number" value="{{ old('slip_number', $nextNumber) }}" required />
                </x-form.field>
                <x-tally::form.field name="slip_date" label="Date" required>
                    <x-tally::form.input name="slip_date" type="date" value="{{ old('slip_date', now()->toDateString()) }}" required />
                </x-form.field>
                <x-tally::form.field name="bank_account_id" label="Bank" required>
                    @include('tally::masters._picker', [
                        'name' => 'bank_account_id',
                        'options' => $accounts->mapWithKeys(fn ($account) => [$account->id => $account->bank_name.' · '.$account->account_number])->all(),
                        'current' => old('bank_account_id'),
                        'placeholder' => 'Bank',
                    ])
                </x-form.field>
                <x-tally::form.field name="narration" label="Narration" class="span-2">
                    <x-tally::form.input name="narration" value="{{ old('narration') }}" />
                </x-form.field>
            </div>
            <table class="data">
                <thead><tr><th></th><th>Cheque no.</th><th>Date</th><th>Favouring</th><th class="money">Amount</th></tr></thead>
                <tbody>
                    @forelse ($cheques as $cheque)
                        <tr>
                            <td><input type="checkbox" name="instrument_ids[]" value="{{ $cheque->id }}"></td>
                            <td>{{ $cheque->number }}</td>
                            <td>{{ $cheque->instrument_date->format('d M Y') }}</td>
                            <td>{{ $cheque->favouring }}</td>
                            <td class="money">{{ $cheque->amount }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No open cheque is waiting to be deposited.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <button class="btn btn-primary" type="submit">Accept</button>
        </form>
        <h2>Saved slips</h2>
        <table class="data">
            <thead><tr><th>Date</th><th>Slip no.</th><th>Bank</th><th class="money">Amount</th><th>Cheques</th></tr></thead>
            <tbody>
                @forelse ($slips as $slip)
                    <tr>
                        <td>{{ $slip->slip_date->format('d M Y') }}</td>
                        <td>{{ $slip->slip_number }}</td>
                        <td>{{ $slip->bankAccount?->bank_name }}</td>
                        <td class="money">{{ $slip->amount }}</td>
                        <td>{{ $slip->lines->map(fn ($line) => $line->instrument?->number)->filter()->join(', ') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">No deposit slip in this year.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-shell.page>
</x-layouts.app>
