<x-tally::layouts.app title="Accrued expenses">
    <x-tally::shell.page title="Accrued expenses" section="Payables" :description="$company->name">
        <form method="POST" class="panel stack" action="{{ tally_route('books.tally.accrued-expenses.store') }}">
            @csrf
            <div class="form-grid">
                <x-tally::form.field name="name" label="Name" required>
                    <x-tally::form.input name="name" value="{{ old('name') }}" required />
                </x-tally::form.field>
                <x-tally::form.field name="accrual_date" label="Date" required>
                    <x-tally::form.input type="date" name="accrual_date" value="{{ old('accrual_date', now()->toDateString()) }}" required />
                </x-tally::form.field>
                <x-tally::form.field name="expense_ledger_id" label="Expense ledger" required>
                    <select class="input" name="expense_ledger_id" required>
                        <option value="">Select</option>
                        @foreach ($ledgers as $ledger)
                            <option value="{{ $ledger->id }}">{{ $ledger->name }}</option>
                        @endforeach
                    </select>
                </x-tally::form.field>
                <x-tally::form.field name="accrual_ledger_id" label="Accrual ledger" required>
                    <select class="input" name="accrual_ledger_id" required>
                        <option value="">Select</option>
                        @foreach ($ledgers as $ledger)
                            <option value="{{ $ledger->id }}">{{ $ledger->name }}</option>
                        @endforeach
                    </select>
                </x-tally::form.field>
                <x-tally::form.field name="amount" label="Amount" required>
                    <x-tally::form.input name="amount" value="{{ old('amount') }}" required />
                </x-tally::form.field>
            </div>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Save</button></div>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Name</th><th>Date</th><th class="money">Amount</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($expenses as $row)
                        <tr>
                            <td>{{ $row->name }}</td>
                            <td>{{ $row->accrual_date->format('d M Y') }}</td>
                            <td class="money">{{ $row->amount }}</td>
                            <td>{{ ucfirst($row->status) }}</td>
                            <td>
                                @if ($row->status === 'open' && ! $row->accrual_voucher_id)
                                    <form method="POST" action="{{ tally_route('books.tally.accrued-expenses.post', $row) }}">
                                        @csrf
                                        <button class="btn" type="submit">Post</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No accrued expenses yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $expenses->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
