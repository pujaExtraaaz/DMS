<div class="form-grid">
    <x-tally::form.field name="name" label="Name" required>
        <x-tally::form.input name="name" value="{{ old('name', $godown->name) }}" required maxlength="255" />
    </x-form.field>
    <x-tally::form.field name="code" label="Code">
        <x-tally::form.input name="code" value="{{ old('code', $godown->code) }}" maxlength="32" />
    </x-form.field>
    <x-tally::form.field name="address" label="Address" class="span-2">
        <x-tally::form.textarea name="address" :value="old('address', $godown->address)" />
    </x-form.field>
    <x-tally::form.active class="span-2" :checked="$godown->is_active ?? true" />
</div>
