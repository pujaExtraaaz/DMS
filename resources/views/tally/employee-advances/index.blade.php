<x-tally::layouts.app title="Employee advances">
    <x-tally::shell.page title="Employee advances" section="Payables" :description="$company->name">
        <form method="POST" class="panel stack" action="{{ tally_route('books.tally.employee-advances.store') }}">
            @csrf
            <div class="form-grid">
                <x-tally::form.field name="employee_ledger_id" label="Employee ledger" required>
                    <select class="input" name="employee_ledger_id" required>
                        <option value="">Select</option>
                        @foreach ($ledgers as $ledger)
                            <option value="{{ $ledger->id }}">{{ $ledger->name }}</option>
                        @endforeach
                    </select>
                </x-tally::form.field>
                <x-tally::form.field name="advance_date" label="Date" required>
                    <x-tally::form.input type="date" name="advance_date" value="{{ old('advance_date', now()->toDateString()) }}" required />
                </x-tally::form.field>
                <x-tally::form.field name="amount" label="Amount" required>
                    <x-tally::form.input name="amount" value="{{ old('amount') }}" required />
                </x-tally::form.field>
                <x-tally::form.field name="purpose" label="Purpose" class="span-full">
                    <x-tally::form.input name="purpose" value="{{ old('purpose') }}" />
                </x-tally::form.field>
            </div>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Save</button></div>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Date</th><th>Employee</th><th class="money">Amount</th><th class="money">Settled</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($advances as $row)
                        <tr>
                            <td>{{ $row->advance_date->format('d M Y') }}</td>
                            <td>{{ $row->employeeLedger?->name }}</td>
                            <td class="money">{{ $row->amount }}</td>
                            <td class="money">{{ $row->settled_amount }}</td>
                            <td>{{ ucfirst($row->status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No employee advances yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $advances->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
