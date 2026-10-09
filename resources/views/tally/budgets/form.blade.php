<x-tally::layouts.app :title="$budget->exists ? 'Edit budget' : 'New budget'">
    <x-tally::shell.page :title="$budget->exists ? 'Edit budget' : 'New budget'" section="Masters" :description="$company->name.' · '.$year->name">
        <form class="panel" method="POST" action="{{ $budget->exists ? tally_route('books.tally.budgets.update', $budget) : tally_route('books.tally.budgets.store') }}">
            @csrf
            @if ($budget->exists) @method('PUT') @endif
            <div class="form-grid">
                <x-tally::form.field name="name" label="Name" required><x-tally::form.input name="name" :value="old('name', $budget->name)" required /></x-form.field>
                <x-tally::form.field name="amount" label="Amount" required><x-tally::form.input name="amount" :value="old('amount', $budget->amount)" required /></x-form.field>
                <x-tally::form.field name="period_start" label="From" required><x-tally::form.input name="period_start" type="date" :value="old('period_start', optional($budget->period_start)->toDateString())" required /></x-form.field>
                <x-tally::form.field name="period_end" label="To" required><x-tally::form.input name="period_end" type="date" :value="old('period_end', optional($budget->period_end)->toDateString())" required /></x-form.field>
                <x-tally::form.field name="ledger_id" label="Ledger">
                    <select id="ledger_id" name="ledger_id" class="input"><option value="">Any</option>@foreach ($ledgers as $ledger)<option value="{{ $ledger->id }}" @selected((string) old('ledger_id', $budget->ledger_id) === (string) $ledger->id)>{{ $ledger->name }}</option>@endforeach</select>
                </x-form.field>
                <x-tally::form.field name="account_group_id" label="Account group">
                    <select id="account_group_id" name="account_group_id" class="input"><option value="">Any</option>@foreach ($groups as $group)<option value="{{ $group->id }}" @selected((string) old('account_group_id', $budget->account_group_id) === (string) $group->id)>{{ $group->name }}</option>@endforeach</select>
                </x-form.field>
                <x-tally::form.field name="cost_centre_id" label="Cost centre">
                    <select id="cost_centre_id" name="cost_centre_id" class="input"><option value="">Any</option>@foreach ($centres as $centre)<option value="{{ $centre->id }}" @selected((string) old('cost_centre_id', $budget->cost_centre_id) === (string) $centre->id)>{{ $centre->name }}</option>@endforeach</select>
                </x-form.field>
            </div>
            <p class="form-note">Choose at least one of ledger, group, or cost centre. Actuals come from posted vouchers in the period.</p>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Save</button>
                <a class="btn" href="{{ tally_route('books.tally.budgets.index') }}">Cancel</a>
            </div>
        </form>
    </x-shell.page>
</x-layouts.app>
