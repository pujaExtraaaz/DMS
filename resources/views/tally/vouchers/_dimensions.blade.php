@if (count($businessUnits ?? []) > 0)
    <label class="ref-line">Business unit
        <select class="input" name="entries[{{ $index }}][business_unit_id]">
            <option value="">—</option>
            @foreach ($businessUnits as $unit)
                <option value="{{ $unit->id }}" @selected((string) ($entry['business_unit_id'] ?? '') === (string) $unit->id)>{{ $unit->name }}</option>
            @endforeach
        </select>
    </label>
@else
    <input type="hidden" name="entries[{{ $index }}][business_unit_id]" value="">
@endif
@if (count($departments ?? []) > 0)
    <label class="ref-line">Department
        <select class="input" name="entries[{{ $index }}][department_id]">
            <option value="">—</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}" @selected((string) ($entry['department_id'] ?? '') === (string) $department->id)>{{ $department->name }}</option>
            @endforeach
        </select>
    </label>
@else
    <input type="hidden" name="entries[{{ $index }}][department_id]" value="">
@endif
