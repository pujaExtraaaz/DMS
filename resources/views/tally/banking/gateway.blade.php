<x-tally::layouts.app title="Payment Gateway Reconciliation">
    <x-tally::shell.page title="Payment Gateway Reconciliation" section="Banking" :description="$company->name">
        <a class="esc-back" data-esc href="{{ tally_route('books.tally.banking.menu') }}">Quit</a>
        <form class="panel" method="POST" action="{{ tally_route('books.tally.banking.gateway.store') }}">
            @csrf
            <x-tally::form.field name="reference" label="Reference" required>
                <x-tally::form.input name="reference" value="{{ old('reference') }}" required />
            </x-form.field>
            <x-tally::form.field name="settlement_date" label="Date" required>
                <x-tally::form.input name="settlement_date" type="date" value="{{ old('settlement_date', now()->toDateString()) }}" required />
            </x-form.field>
            <x-tally::form.field name="gross_amount" label="Gross" required>
                <x-tally::form.input name="gross_amount" value="{{ old('gross_amount') }}" required />
            </x-form.field>
            <x-tally::form.field name="charges" label="Charges">
                <x-tally::form.input name="charges" value="{{ old('charges', '0.00') }}" />
            </x-form.field>
            <x-tally::form.field name="merchant_profile_id" label="Merchant">
                @include('tally::masters._picker', [
                    'name' => 'merchant_profile_id',
                    'options' => $merchants->mapWithKeys(fn ($merchant) => [$merchant->id => $merchant->name])->all(),
                    'current' => old('merchant_profile_id'),
                    'placeholder' => 'Merchant',
                    'optional' => true,
                    'create' => tally_route('books.tally.merchant-profiles.create'),
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
            <button class="btn btn-primary" type="submit">Accept</button>
        </form>
        <table class="data">
            <thead><tr><th>Date</th><th>Reference</th><th>Merchant</th><th>Bank</th><th class="money">Gross</th><th class="money">Charges</th><th class="money">Net</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td>{{ $row->settlement_date->format('d M Y') }}</td>
                        <td>{{ $row->reference }}</td>
                        <td>{{ $row->merchant?->name }}</td>
                        <td>{{ $row->bankAccount?->bank_name }}</td>
                        <td class="money">{{ $row->gross_amount }}</td>
                        <td class="money">{{ $row->charges }}</td>
                        <td class="money">{{ $row->net_amount }}</td>
                        <td>{{ $row->status === 'reconciled' ? 'Reconciled' : 'Pending' }}</td>
                        <td>
                            <form method="POST" action="{{ tally_route('books.tally.banking.gateway.reconcile', $row) }}">
                                @csrf
                                <button class="btn" type="submit">{{ $row->status === 'reconciled' ? 'Mark pending' : 'Reconcile' }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9">No gateway settlement in this year.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-shell.page>
</x-layouts.app>
