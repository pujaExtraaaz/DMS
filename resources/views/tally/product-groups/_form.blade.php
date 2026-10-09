<div class="form-grid">
    <x-tally::form.field name="name" label="Name" required>
        <x-tally::form.input name="name" value="{{ old('name', $group->name) }}" required maxlength="255" />
    </x-form.field>
    <x-tally::form.field name="code" label="Code">
        <x-tally::form.input name="code" value="{{ old('code', $group->code) }}" maxlength="32" />
    </x-form.field>
    <x-tally::form.field name="parent_id" label="Parent group" class="span-2">
        <select id="parent_id" name="parent_id" class="input">
            <option value="">Primary group</option>
            @foreach ($parents as $row)
                <option value="{{ $row['group']->id }}" @selected((string) old('parent_id', $group->parent_id) === (string) $row['group']->id)>
                    {{ str_repeat('· ', $row['depth']) }}{{ $row['group']->name }}
                </option>
            @endforeach
        </select>
    </x-form.field>
    <x-tally::form.active class="span-2" :checked="$group->is_active ?? true" />
</div>
<p class="form-note">The code is filled in for a new group. Leave it as shown, or type another code. A group cannot be placed under itself or under one of its subgroups.</p>
