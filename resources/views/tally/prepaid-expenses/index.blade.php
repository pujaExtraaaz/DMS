<x-tally::layouts.app title="Prepaid expenses">
    <x-tally::shell.page title="Prepaid expenses" section="Payables" :description="$company->name">
        <x-slot:actions>
            <form method="POST" action="{{ tally_route('books.tally.prepaid-expenses.recognize') }}">
                @csrf
                <button class="btn btn-primary" type="submit">Recognize due</button>
            </form>
        </x-slot:actions>
        <form method="POST" class="panel stack" action="{{ tally_route('books.tally.prepaid-expenses.store') }}">
            @csrf
            <div class="form-grid">
                <x-tally::form.field name="name" label="Name" required>
                    <x-tally::form.input name="name" value="{{ old('name') }}" required />
                </x-tally::form.field>
                <x-tally::form.field name="prepaid_ledger_id" label="Prepaid ledger" required>
                    <select class="input" name="prepaid_ledger_id" required>
                        <option value="">Select</option>
                        @foreach ($ledgers as $ledger)
                            <option value="{{ $ledger->id }}">{{ $ledger->name }}</option>
                        @endforeach
                    </select>
                </x-tally::form.field>
                <x-tally::form.field name="expense_ledger_id" label="Expense ledger" required>
                    <select class="input" name="expense_ledger_id" required>
                        <option value="">Select</option>
                        @foreach ($ledgers as $ledger)
                            <option value="{{ $ledger->id }}">{{ $ledger->name }}</option>
                        @endforeach
                    </select>
                </x-tally::form.field>
                <x-tally::form.field name="start_date" label="Start" required>
                    <x-tally::form.input type="date" name="start_date" value="{{ old('start_date', now()->toDateString()) }}" required />
                </x-tally::form.field>
                <x-tally::form.field name="end_date" label="End" required>
                    <x-tally::form.input type="date" name="end_date" value="{{ old('end_date', now()->addYear()->toDateString()) }}" required />
                </x-tally::form.field>
                <x-tally::form.field name="total_amount" label="Total amount" required>
                    <x-tally::form.input name="total_amount" value="{{ old('total_amount') }}" required />
                </x-tally::form.field>
            </div>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Save</button></div>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Name</th><th>Period</th><th class="money">Total</th><th class="money">Remaining</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($expenses as $row)
                        <tr>
                            <td>{{ $row->name }}</td>
                            <td>{{ $row->start_date->format('d M Y') }} – {{ $row->end_date->format('d M Y') }}</td>
                            <td class="money">{{ $row->total_amount }}</td>
                            <td class="money">{{ $row->remainingAmount() }}</td>
                            <td>{{ ucfirst($row->status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No prepaid expenses yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $expenses->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
