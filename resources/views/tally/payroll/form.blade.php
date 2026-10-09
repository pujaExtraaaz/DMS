<x-tally::layouts.app title="Payroll">
    <x-tally::shell.page title="Payroll" section="Transactions" :description="$company->name.' · '.$year->name">
        <form method="POST" action="{{ tally_route('books.tally.payroll.store') }}" class="stack">
            @csrf
            <div class="form-grid">
                <x-tally::form.field name="period_start" label="Period from" required>
                    <x-tally::form.input name="period_start" type="date" :value="old('period_start', $year->start_date->toDateString())" required />
                </x-form.field>
                <x-tally::form.field name="period_end" label="Period to" required>
                    <x-tally::form.input name="period_end" type="date" :value="old('period_end', $year->start_date->toDateString())" required />
                </x-form.field>
                <x-tally::form.field name="salary_ledger_id" label="Salary expense ledger" required>
                    <select id="salary_ledger_id" name="salary_ledger_id" class="input" required>
                        @foreach ($ledgers as $ledger)
                            <option value="{{ $ledger->id }}">{{ $ledger->name }}</option>
                        @endforeach
                    </select>
                </x-form.field>
                <x-tally::form.field name="deduction_ledger_id" label="Deduction ledger">
                    <select id="deduction_ledger_id" name="deduction_ledger_id" class="input">
                        <option value="">None</option>
                        @foreach ($ledgers as $ledger)
                            <option value="{{ $ledger->id }}">{{ $ledger->name }}</option>
                        @endforeach
                    </select>
                </x-form.field>
            </div>
            <h2>Employees</h2>
            @foreach ($employees as $employee)
                <label class="check"><input type="checkbox" name="employee_ids[]" value="{{ $employee->id }}" checked> {{ $employee->name }} ({{ $employee->employee_code }}) {{ $employee->monthly_earnings }}</label>
            @endforeach
            @if ($errors->any())
                <p class="flash is-error" role="alert">{{ $errors->first() }}</p>
            @endif
            <p class="form-note">Accept saves a draft. Posting the journal is a separate step and uses the voucher engine.</p>
            <button class="btn btn-primary" type="submit">Accept</button>
        </form>
    </x-shell.page>
</x-layouts.app>
