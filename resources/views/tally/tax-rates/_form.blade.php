<div class="form-grid">
    <x-tally::form.field name="name" label="Name" required>
        <x-tally::form.input name="name" value="{{ old('name', $rate->name) }}" required maxlength="255" />
    </x-form.field>
    <x-tally::form.field name="code" label="Code">
        <x-tally::form.input name="code" value="{{ old('code', $rate->code) }}" maxlength="32" />
    </x-form.field>
    <x-tally::form.field name="tax_category_id" label="Category" required class="span-2">
        <select id="tax_category_id" name="tax_category_id" class="input" required>
            <option value="">Select category</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) old('tax_category_id', $rate->tax_category_id) === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
    </x-form.field>
    <x-tally::form.field name="cgst_rate" label="CGST %" required>
        <x-tally::form.input name="cgst_rate" type="number" min="0" max="100" step="0.0001" value="{{ old('cgst_rate', $rate->exists ? $rate->trimmed('cgst_rate') : '0') }}" required />
    </x-form.field>
    <x-tally::form.field name="sgst_rate" label="SGST %" required>
        <x-tally::form.input name="sgst_rate" type="number" min="0" max="100" step="0.0001" value="{{ old('sgst_rate', $rate->exists ? $rate->trimmed('sgst_rate') : '0') }}" required />
    </x-form.field>
    <x-tally::form.field name="igst_rate" label="IGST %" required>
        <x-tally::form.input name="igst_rate" type="number" min="0" max="100" step="0.0001" value="{{ old('igst_rate', $rate->exists ? $rate->trimmed('igst_rate') : '0') }}" required />
    </x-form.field>
    <x-tally::form.field name="cess_rate" label="Cess %" required>
        <x-tally::form.input name="cess_rate" type="number" min="0" max="100" step="0.0001" value="{{ old('cess_rate', $rate->exists ? $rate->trimmed('cess_rate') : '0') }}" required />
    </x-form.field>
    <x-tally::form.active class="span-2" :checked="$rate->is_active ?? true" />
</div>
<p class="form-note">SGST must equal CGST, and IGST must equal CGST + SGST. Intra-state invoices use CGST and SGST. Inter-state invoices use IGST. Cess applies to both.</p>
