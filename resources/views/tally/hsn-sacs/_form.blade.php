<div class="form-grid">
    <x-tally::form.field name="code" label="Code" required>
        <x-tally::form.input name="code" value="{{ old('code', $record->code) }}" required maxlength="8" inputmode="numeric" placeholder="4, 6 or 8 digits" />
    </x-form.field>
    <x-tally::form.field name="kind" label="Kind" required>
        <select id="kind" name="kind" class="input" required>
            @foreach (\Tally\Tax\HsnKind::cases() as $kind)
                <option value="{{ $kind->value }}" @selected(old('kind', $record->kind?->value ?? $record->kind) === $kind->value)>{{ $kind->label() }}</option>
            @endforeach
        </select>
    </x-form.field>
    <x-tally::form.field name="description" label="Description" class="span-2">
        <x-tally::form.input name="description" value="{{ old('description', $record->description) }}" maxlength="255" />
    </x-form.field>
    <x-tally::form.field name="tax_rate_id" label="Tax rate" class="span-2">
        <select id="tax_rate_id" name="tax_rate_id" class="input">
            <option value="">None</option>
            @foreach ($rates as $rate)
                <option value="{{ $rate->id }}" @selected((string) old('tax_rate_id', $record->tax_rate_id) === (string) $rate->id)>{{ $rate->name }}</option>
            @endforeach
        </select>
    </x-form.field>
    <x-tally::form.active class="span-2" :checked="$record->is_active ?? true" />
</div>
