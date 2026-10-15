<x-tally::layouts.app title="Employee loans">
    <x-tally::shell.page title="Employee loans" section="Payroll" :description="$company->name">
        <form method="POST" action="{{ tally_route('books.tally.employee-loans.store') }}" class="form-grid panel">
            @csrf
            <x-tally::form.field name="employee_id" label="Employee" required>
                <select class="input" name="employee_id" required>
                    @foreach ($employees as $e)<option value="{{ $e->id }}">{{ $e->name }}</option>@endforeach
                </select>
            </x-tally::form.field>
            <x-tally::form.field name="loan_date" label="Date" required><x-tally::form.input type="date" name="loan_date" :value="now()->toDateString()" required /></x-tally::form.field>
            <x-tally::form.field name="principal" label="Principal" required><x-tally::form.input name="principal" required /></x-tally::form.field>
            <x-tally::form.field name="interest_rate" label="Interest %"><x-tally::form.input name="interest_rate" value="0" /></x-tally::form.field>
            <x-tally::form.field name="tenure_months" label="Tenure (months)"><x-tally::form.input type="number" name="tenure_months" value="12" /></x-tally::form.field>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Add</button></div>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Employee</th><th>Date</th><th class="money">Principal</th><th class="money">Emi</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($loans as $loan)
                        <tr>
                            <td><a href="{{ tally_route('books.tally.employee-loans.show', $loan) }}">{{ $loan->employee?->name }}</a></td>
                            <td>{{ $loan->loan_date->format('d M Y') }}</td>
                            <td class="money">{{ $loan->principal }}</td>
                            <td class="money">{{ $loan->emi_amount }}</td>
                            <td>{{ ucfirst($loan->status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No employee loans.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $loans->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
