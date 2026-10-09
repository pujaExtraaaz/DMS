<form class="filters" method="GET" action="{{ $action }}">
    <x-tally::form.field name="q" label="Search">
        <x-tally::form.input name="q" value="{{ $filters['q'] }}" placeholder="Number, narration, reference" />
    </x-form.field>
    <x-tally::form.field name="from" label="From">
        <x-tally::form.input name="from" type="date" value="{{ $filters['from'] }}" />
    </x-form.field>
    <x-tally::form.field name="to" label="To">
        <x-tally::form.input name="to" type="date" value="{{ $filters['to'] }}" />
    </x-form.field>
    <x-tally::form.field name="ledger_id" label="Bank ledger">
        <select id="ledger_id" name="ledger_id" class="input">
            <option value="">All bank ledgers</option>
            @foreach ($ledgers as $ledger)
                <option value="{{ $ledger->id }}" @selected((string) $filters['ledger_id'] === (string) $ledger->id)>{{ $ledger->name }}</option>
            @endforeach
        </select>
    </x-form.field>
    <x-tally::form.field name="branch_id" label="Branch">
        <select id="branch_id" name="branch_id" class="input">
            <option value="all" @selected($filters['branch_id'] === null)>All branches</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected((string) $filters['branch_id'] === (string) $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>
    </x-form.field>
    <button class="btn btn-primary" type="submit">Apply</button>
</form>
