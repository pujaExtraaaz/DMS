<div class="form-grid">
    <x-tally::form.field name="name" label="Name" required>
        <x-tally::form.input name="name" value="{{ old('name', $category->name) }}" required maxlength="255" />
    </x-form.field>
    <x-tally::form.field name="code" label="Code">
        <x-tally::form.input name="code" value="{{ old('code', $category->code) }}" maxlength="32" />
    </x-form.field>
    <x-tally::form.active class="span-2" :checked="$category->is_active ?? true" />
</div>
