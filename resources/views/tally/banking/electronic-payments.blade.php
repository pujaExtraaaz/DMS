<x-tally::layouts.app title="Electronic payments">
    <x-tally::shell.page title="Electronic payments" section="Banking" :description="$company->name">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ tally_route('books.tally.banking.electronic-payments.export-neft') }}">Export NEFT/RTGS file</a>
        </x-slot:actions>
        <form method="POST" action="{{ tally_route('books.tally.banking.electronic-payments.store') }}" class="form-grid panel">
            @csrf
            <x-tally::form.field name="channel" label="Channel" required>
                <select class="input" name="channel" required>
                    @foreach (['neft', 'rtgs', 'imps', 'upi', 'card', 'gateway'] as $channel)
                        <option value="{{ $channel }}" @selected(old('channel') === $channel)>{{ strtoupper($channel) }}</option>
                    @endforeach
                </select>
            </x-tally::form.field>
            <x-tally::form.field name="payment_date" label="Date" required><x-tally::form.input type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" required /></x-tally::form.field>
            <x-tally::form.field name="amount" label="Amount" required><x-tally::form.input name="amount" value="{{ old('amount') }}" required /></x-tally::form.field>
            <x-tally::form.field name="beneficiary" label="Beneficiary"><x-tally::form.input name="beneficiary" value="{{ old('beneficiary') }}" /></x-tally::form.field>
            <x-tally::form.field name="beneficiary_account" label="Beneficiary A/c"><x-tally::form.input name="beneficiary_account" value="{{ old('beneficiary_account') }}" /></x-tally::form.field>
            <x-tally::form.field name="beneficiary_ifsc" label="IFSC"><x-tally::form.input name="beneficiary_ifsc" value="{{ old('beneficiary_ifsc') }}" /></x-tally::form.field>
            <x-tally::form.field name="reference" label="Reference"><x-tally::form.input name="reference" value="{{ old('reference') }}" /></x-tally::form.field>
            <x-tally::form.field name="utr" label="UTR"><x-tally::form.input name="utr" value="{{ old('utr') }}" /></x-tally::form.field>
            <x-tally::form.field name="bank_account_id" label="Bank">
                <select class="input" name="bank_account_id">
                    <option value="">—</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}" @selected((string) old('bank_account_id') === (string) $account->id)>{{ $account->bank_name }} {{ $account->account_number }}</option>
                    @endforeach
                </select>
            </x-tally::form.field>
            <x-tally::form.field name="status" label="Status">
                <select class="input" name="status">
                    @foreach (['initiated', 'sent', 'cleared', 'failed', 'cancelled'] as $status)
                        <option value="{{ $status }}" @selected(old('status', 'initiated') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </x-tally::form.field>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Record payment</button></div>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Date</th><th>Channel</th><th>Beneficiary</th><th>A/c</th><th>IFSC</th><th>Bank</th><th class="money">Amount</th><th>UTR</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td>{{ $payment->payment_date?->format('d M Y') }}</td>
                            <td>{{ strtoupper($payment->channel) }}</td>
                            <td>{{ $payment->beneficiary }}</td>
                            <td>{{ $payment->beneficiary_account }}</td>
                            <td>{{ $payment->beneficiary_ifsc }}</td>
                            <td>{{ $payment->bankAccount?->bank_name }}</td>
                            <td class="money">{{ $payment->amount }}</td>
                            <td>{{ $payment->utr }}</td>
                            <td>{{ $payment->status }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9">No electronic payments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $payments->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
