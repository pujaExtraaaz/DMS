@php
    use App\Accounting\AccountNature;
@endphp
<div class="form-grid">
    <x-tally::form.field name="name" label="Name" required>
        <x-tally::form.input name="name" value="{{ old('name', $group->name) }}" required :disabled="$group->is_system" />
    </x-form.field>
    <x-tally::form.field name="code" label="Code">
        <x-tally::form.input name="code" value="{{ old('code', $group->code) }}" maxlength="32" :disabled="$group->is_system" />
    </x-form.field>
    <x-tally::form.field name="parent_id" label="Parent group">
        <select id="parent_id" name="parent_id" class="input" @disabled($group->is_system)>
            <option value="">Primary group</option>
            @foreach ($parents as $row)
                <option value="{{ $row['group']->id }}" @selected((string) old('parent_id', $group->parent_id) === (string) $row['group']->id)>
                    {{ str_repeat('· ', $row['depth']) }}{{ $row['group']->name }}
                </option>
            @endforeach
        </select>
    </x-form.field>
    <x-tally::form.field name="nature" label="Nature" :required="! old('parent_id', $group->parent_id)">
        <select id="nature" name="nature" class="input" @disabled($group->is_system)>
            @foreach (AccountNature::cases() as $nature)
                <option value="{{ $nature->value }}" @selected(old('nature', $group->nature?->value ?? 'asset') === $nature->value)>{{ $nature->label() }}</option>
            @endforeach
        </select>
    </x-form.field>
    <x-tally::form.active class="span-2" :checked="$group->is_active ?? true" />
</div>
<p class="form-note muted">A subgroup keeps the nature of its parent. System groups stay in the chart and cannot be deleted.</p>
