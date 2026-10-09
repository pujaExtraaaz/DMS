<div class="form-grid">
    <x-tally::form.field name="name" label="Name" required>
        <x-tally::form.input name="name" value="{{ old('name', $unit->name) }}" required maxlength="255" />
    </x-form.field>
    <x-tally::form.field name="symbol" label="Symbol" required>
        <x-tally::form.input name="symbol" value="{{ old('symbol', $unit->symbol) }}" required maxlength="16" />
    </x-form.field>
    <x-tally::form.field name="decimal_places" label="Decimal places" required>
        <select id="decimal_places" name="decimal_places" class="input" required>
            @foreach (range(0, 4) as $places)
                <option value="{{ $places }}" @selected((string) old('decimal_places', $unit->decimal_places ?? 0) === (string) $places)>{{ $places }}</option>
            @endforeach
        </select>
    </x-form.field>
    <x-tally::form.active :checked="$unit->is_active ?? true" />
</div>
<p class="form-note">Decimal places limit quantities for products that use this unit. A unit used by a product cannot be deleted.</p>
