<x-tally::layouts.app title="Custom fields">
    <x-tally::shell.page title="Custom fields" section="Settings" :description="$company->name">
        <form method="POST" action="{{ tally_route('books.tally.custom-fields.store') }}" class="form-grid panel">
            @csrf
            <x-tally::form.field name="entity_type" label="Entity" required><x-tally::form.input name="entity_type" placeholder="voucher" required /></x-tally::form.field>
            <x-tally::form.field name="field_key" label="Key" required><x-tally::form.input name="field_key" required /></x-tally::form.field>
            <x-tally::form.field name="label" label="Label" required><x-tally::form.input name="label" required /></x-tally::form.field>
            <x-tally::form.field name="field_type" label="Type">
                <select class="input" name="field_type">
                    @foreach (['text','number','date','boolean','select'] as $type)<option value="{{ $type }}">{{ $type }}</option>@endforeach
                </select>
            </x-tally::form.field>
            <label class="check"><input type="checkbox" name="is_required" value="1"> Required</label>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Add</button></div>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Entity</th><th>Key</th><th>Label</th><th>Type</th></tr></thead>
                <tbody>
                    @forelse ($fields as $field)
                        <tr>
                            <td>{{ $field->entity_type }}</td>
                            <td>{{ $field->field_key }}</td>
                            <td>{{ $field->label }}</td>
                            <td>{{ $field->field_type }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No custom fields.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $fields->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
