<x-tally::layouts.app title="Bank categorization">
    <x-tally::shell.page title="Bank categorization" section="Banking" :description="$company->name">
        <x-slot:actions>
            <form method="POST" action="{{ tally_route('books.tally.banking.rules.apply') }}">@csrf<button class="btn" type="submit">Apply rules</button></form>
        </x-slot:actions>
        <form method="POST" action="{{ tally_route('books.tally.banking.rules.store') }}" class="form-grid panel">
            @csrf
            <x-tally::form.field name="name" label="Name" required><x-tally::form.input name="name" value="{{ old('name') }}" required /></x-tally::form.field>
            <x-tally::form.field name="match_field" label="Match field" required>
                <select class="input" name="match_field" required>
                    @foreach (['narration', 'reference', 'amount'] as $field)
                        <option value="{{ $field }}" @selected(old('match_field', 'narration') === $field)>{{ ucfirst($field) }}</option>
                    @endforeach
                </select>
            </x-tally::form.field>
            <x-tally::form.field name="match_type" label="Match type" required>
                <select class="input" name="match_type" required>
                    @foreach (['contains', 'equals', 'starts_with', 'regex'] as $type)
                        <option value="{{ $type }}" @selected(old('match_type', 'contains') === $type)>{{ str_replace('_', ' ', ucfirst($type)) }}</option>
                    @endforeach
                </select>
            </x-tally::form.field>
            <x-tally::form.field name="match_value" label="Match value" required><x-tally::form.input name="match_value" value="{{ old('match_value') }}" required /></x-tally::form.field>
            <x-tally::form.field name="ledger_id" label="Ledger" required>
                <select class="input" name="ledger_id" required>
                    @foreach ($ledgers as $ledger)
                        <option value="{{ $ledger->id }}" @selected((string) old('ledger_id') === (string) $ledger->id)>{{ $ledger->name }}</option>
                    @endforeach
                </select>
            </x-tally::form.field>
            <x-tally::form.field name="priority" label="Priority"><x-tally::form.input name="priority" type="number" value="{{ old('priority', 100) }}" /></x-tally::form.field>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Save rule</button></div>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Priority</th><th>Name</th><th>Match</th><th>Ledger</th><th>Active</th><th></th></tr></thead>
                <tbody>
                    @forelse ($rules as $rule)
                        <tr>
                            <td>{{ $rule->priority }}</td>
                            <td>{{ $rule->name }}</td>
                            <td>{{ $rule->match_field }} {{ $rule->match_type }} “{{ $rule->match_value }}”</td>
                            <td>{{ $rule->ledger?->name }}</td>
                            <td>{{ $rule->is_active ? 'Yes' : 'No' }}</td>
                            <td>
                                <form method="POST" action="{{ tally_route('books.tally.banking.rules.destroy', $rule) }}" onsubmit="return confirm('Delete this rule?');">
                                    @csrf @method('DELETE')
                                    <button class="btn" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No categorization rules yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $rules->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
