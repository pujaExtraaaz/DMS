<x-tally::layouts.app title="Loans">
    <x-tally::shell.page title="Loans" section="Liabilities" :description="$company->name">
        <form method="POST" action="{{ tally_route('books.tally.loans.store') }}" class="form-grid panel">
            @csrf
            <x-tally::form.field name="loan_number" label="Loan #" required><x-tally::form.input name="loan_number" required /></x-tally::form.field>
            <x-tally::form.field name="lender_name" label="Lender" required><x-tally::form.input name="lender_name" required /></x-tally::form.field>
            <x-tally::form.field name="disbursement_date" label="Disbursement" required><x-tally::form.input type="date" name="disbursement_date" :value="now()->toDateString()" required /></x-tally::form.field>
            <x-tally::form.field name="principal" label="Principal" required><x-tally::form.input name="principal" required /></x-tally::form.field>
            <x-tally::form.field name="interest_rate" label="Interest %"><x-tally::form.input name="interest_rate" value="10" /></x-tally::form.field>
            <x-tally::form.field name="tenure_months" label="Tenure" required><x-tally::form.input type="number" name="tenure_months" value="12" required /></x-tally::form.field>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Create loan</button></div>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Number</th><th>Lender</th><th class="money">Principal</th><th class="money">Emi</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($loans as $loan)
                        <tr>
                            <td><a href="{{ tally_route('books.tally.loans.show', $loan) }}">{{ $loan->loan_number }}</a></td>
                            <td>{{ $loan->lender_name }}</td>
                            <td class="money">{{ $loan->principal }}</td>
                            <td class="money">{{ $loan->emi_amount }}</td>
                            <td>{{ ucfirst($loan->status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No loans.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $loans->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
