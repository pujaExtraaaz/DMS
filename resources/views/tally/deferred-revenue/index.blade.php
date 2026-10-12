<x-tally::layouts.app title="Deferred revenue">
    <x-tally::shell.page title="Deferred revenue" section="Utilities" :description="$company->name">
        <x-slot:actions>
            <form method="POST" action="{{ tally_route('books.tally.deferred-revenue.recognize') }}">@csrf<button class="btn" type="submit">Recognize due</button></form>
        </x-slot:actions>
        <form method="POST" action="{{ tally_route('books.tally.deferred-revenue.store') }}" class="form-grid panel">
            @csrf
            <x-tally::form.field name="customer_ledger_id" label="Customer" required>
                <select class="input" name="customer_ledger_id" required>
                    @foreach ($ledgers as $ledger)
                        <option value="{{ $ledger->id }}">{{ $ledger->name }}</option>
                    @endforeach
                </select>
            </x-tally::form.field>
            <x-tally::form.field name="deferred_ledger_id" label="Deferred ledger" required>
                <select class="input" name="deferred_ledger_id" required>
                    @foreach ($ledgers as $ledger)
                        <option value="{{ $ledger->id }}">{{ $ledger->name }}</option>
                    @endforeach
                </select>
            </x-tally::form.field>
            <x-tally::form.field name="revenue_ledger_id" label="Revenue ledger" required>
                <select class="input" name="revenue_ledger_id" required>
                    @foreach ($ledgers as $ledger)
                        <option value="{{ $ledger->id }}">{{ $ledger->name }}</option>
                    @endforeach
                </select>
            </x-tally::form.field>
            <x-tally::form.field name="total_amount" label="Amount" required><x-tally::form.input name="total_amount" required /></x-tally::form.field>
            <x-tally::form.field name="start_date" label="Start" required><x-tally::form.input type="date" name="start_date" value="{{ now()->toDateString() }}" required /></x-tally::form.field>
            <x-tally::form.field name="end_date" label="End" required><x-tally::form.input type="date" name="end_date" value="{{ now()->addYear()->toDateString() }}" required /></x-tally::form.field>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Create schedule</button></div>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Customer</th><th class="money">Total</th><th class="money">Recognized</th><th>Next</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($schedules as $schedule)
                        <tr>
                            <td>{{ $schedule->customer_ledger_id }}</td>
                            <td class="money">{{ $schedule->total_amount }}</td>
                            <td class="money">{{ $schedule->recognized_amount }}</td>
                            <td>{{ optional($schedule->next_recognize_on)->format('d M Y') }}</td>
                            <td>{{ $schedule->status }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No schedules.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $schedules->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
