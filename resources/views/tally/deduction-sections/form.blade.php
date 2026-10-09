<x-tally::layouts.app :title="$section->exists ? 'Edit section' : 'New section'">
    <x-tally::shell.page :title="$section->exists ? 'Edit section' : 'New TDS / TCS section'" section="Masters" :description="$company->name">
        <form class="panel" method="POST" action="{{ $section->exists ? tally_route('books.tally.deduction-sections.update', $section) : tally_route('books.tally.deduction-sections.store') }}">
            @csrf
            @if ($section->exists) @method('PUT') @endif
            <div class="form-grid">
                <x-tally::form.field name="kind" label="Kind" required>
                    <select id="kind" name="kind" class="input">
                        @foreach (\Tally\Tax\DeductionKind::cases() as $kind)
                            <option value="{{ $kind->value }}" @selected(old('kind', $section->kind?->value) === $kind->value)>{{ $kind->label() }}</option>
                        @endforeach
                    </select>
                </x-form.field>
                <x-tally::form.field name="section_code" label="Section code" required><x-tally::form.input name="section_code" :value="old('section_code', $section->section_code)" required /></x-form.field>
                <x-tally::form.field name="name" label="Name" required><x-tally::form.input name="name" :value="old('name', $section->name)" required /></x-form.field>
                <x-tally::form.field name="rate" label="Rate %" required><x-tally::form.input name="rate" :value="old('rate', $section->rate)" required /></x-form.field>
                <x-tally::form.field name="threshold_amount" label="Threshold" required><x-tally::form.input name="threshold_amount" :value="old('threshold_amount', $section->threshold_amount)" required /></x-form.field>
                <x-tally::form.field name="party_role" label="Applies to" required>
                    <select id="party_role" name="party_role" class="input">
                        @foreach (\Tally\Tax\PartyRole::cases() as $role)
                            <option value="{{ $role->value }}" @selected(old('party_role', $section->party_role?->value) === $role->value)>{{ $role->label() }}</option>
                        @endforeach
                    </select>
                </x-form.field>
                <x-tally::form.active :checked="$section->is_active ?? true" />
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Save</button>
                <a class="btn" href="{{ tally_route('books.tally.deduction-sections.index') }}">Cancel</a>
            </div>
        </form>
    </x-shell.page>
</x-layouts.app>
