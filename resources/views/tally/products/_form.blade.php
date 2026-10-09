<div class="form-grid">
    <x-tally::form.field name="name" label="Name" required>
        <x-tally::form.input name="name" value="{{ old('name', $product->name) }}" required maxlength="255" />
    </x-form.field>
    <x-tally::form.field name="code" label="SKU" required>
        <x-tally::form.input name="code" value="{{ old('code', $product->code) }}" required maxlength="32" />
    </x-form.field>
    <x-tally::form.field name="barcode" label="Barcode">
        <x-tally::form.input name="barcode" value="{{ old('barcode', $product->barcode) }}" maxlength="64" />
    </x-form.field>
    <x-tally::form.field name="extra_barcodes" label="More barcodes" class="span-2">
        <textarea id="extra_barcodes" name="extra_barcodes" class="input" rows="3" placeholder="One barcode per line">{{ old('extra_barcodes', $product->exists ? $product->barcodes->pluck('barcode')->implode("\n") : '') }}</textarea>
    </x-form.field>
    <x-tally::form.field name="hsn_sac_id" label="HSN / SAC">
        @include('tally::masters._hsn', [
            'name' => 'hsn_sac_id',
            'hsnSacs' => $hsnSacs ?? [],
            'current' => old('hsn_sac_id', $product->hsn_sac_id),
        ])
    </x-form.field>
    <x-tally::form.field name="tax_rate_id" label="Tax rate">
        <select id="tax_rate_id" name="tax_rate_id" class="input">
            <option value="">None</option>
            @foreach ($taxRates ?? [] as $taxRate)
                <option value="{{ $taxRate->id }}" @selected((string) old('tax_rate_id', $product->tax_rate_id) === (string) $taxRate->id)>{{ $taxRate->name }}</option>
            @endforeach
        </select>
    </x-form.field>
    <x-tally::form.field name="product_group_id" label="Product group" required>
        <select id="product_group_id" name="product_group_id" class="input" required>
            <option value="">Select group</option>
            @foreach ($groups as $row)
                <option value="{{ $row['group']->id }}" @selected((string) old('product_group_id', $product->product_group_id) === (string) $row['group']->id)>
                    {{ str_repeat('· ', $row['depth']) }}{{ $row['group']->name }}
                </option>
            @endforeach
        </select>
    </x-form.field>
    <x-tally::form.field name="primary_unit_id" label="Primary unit" required>
        <select id="primary_unit_id" name="primary_unit_id" class="input" required>
            <option value="">Select unit</option>
            @foreach ($units as $unit)
                <option value="{{ $unit->id }}" @selected((string) old('primary_unit_id', $product->primary_unit_id) === (string) $unit->id)>{{ $unit->label() }}</option>
            @endforeach
        </select>
    </x-form.field>
    <x-tally::form.field name="alternate_unit_id" label="Alternate unit">
        <select id="alternate_unit_id" name="alternate_unit_id" class="input">
            <option value="">None</option>
            @foreach ($units as $unit)
                <option value="{{ $unit->id }}" @selected((string) old('alternate_unit_id', $product->alternate_unit_id) === (string) $unit->id)>{{ $unit->label() }}</option>
            @endforeach
        </select>
    </x-form.field>
    <x-tally::form.field name="conversion_factor" label="Conversion factor" class="span-2">
        <x-tally::form.input name="conversion_factor" type="number" min="0" step="0.000001" value="{{ old('conversion_factor', $product->conversion_factor) }}" />
    </x-form.field>
    <x-tally::form.field name="purchase_rate" label="Purchase rate" required>
        <x-tally::form.input name="purchase_rate" type="number" min="0" step="0.01" value="{{ old('purchase_rate', $product->purchase_rate ?? '0.00') }}" required />
    </x-form.field>
    <x-tally::form.field name="sales_rate" label="Sales rate" required>
        <x-tally::form.input name="sales_rate" type="number" min="0" step="0.01" value="{{ old('sales_rate', $product->sales_rate ?? '0.00') }}" required />
    </x-form.field>
    <x-tally::form.field name="opening_quantity" label="Opening quantity" required>
        <x-tally::form.input name="opening_quantity" type="number" min="0" step="0.0001" value="{{ old('opening_quantity', $product->exists ? $product->trimmedQuantity('opening_quantity') : '0') }}" required />
    </x-form.field>
    <x-tally::form.field name="opening_rate" label="Opening rate" required>
        <x-tally::form.input name="opening_rate" type="number" min="0" step="0.01" value="{{ old('opening_rate', $product->opening_rate ?? '0.00') }}" required />
    </x-form.field>
    <x-tally::form.field name="opening_value" label="Opening value">
        <x-tally::form.input name="opening_value" type="number" min="0" step="0.01" value="{{ old('opening_value', $product->exists ? $product->opening_value : '') }}" />
    </x-form.field>
    <x-tally::form.field name="minimum_stock" label="Minimum stock" required>
        <x-tally::form.input name="minimum_stock" type="number" min="0" step="0.0001" value="{{ old('minimum_stock', $product->exists ? $product->trimmedQuantity('minimum_stock') : '0') }}" required />
    </x-form.field>
    <x-tally::form.field name="reorder_level" label="Reorder level" required>
        <x-tally::form.input name="reorder_level" type="number" min="0" step="0.0001" value="{{ old('reorder_level', $product->exists ? $product->trimmedQuantity('reorder_level') : '0') }}" required />
    </x-form.field>
    <x-tally::form.field name="maximum_stock" label="Maximum stock">
        <x-tally::form.input name="maximum_stock" type="number" min="0" step="0.0001" value="{{ old('maximum_stock', $product->exists && $product->maximum_stock !== null ? $product->trimmedQuantity('maximum_stock') : '') }}" />
    </x-form.field>
    <x-tally::form.field name="track_batch" label="Tracking">
        <label><input type="hidden" name="track_batch" value="0"><input type="checkbox" name="track_batch" value="1" @checked(old('track_batch', $product->track_batch))> Track batches</label>
        <label><input type="hidden" name="track_serial" value="0"><input type="checkbox" name="track_serial" value="1" @checked(old('track_serial', $product->track_serial))> Track serial numbers</label>
    </x-form.field>
    <x-tally::form.active class="span-2" :checked="$product->is_active ?? true" />
</div>
<p class="form-note">Conversion factor is how many primary units make one alternate unit. Opening value must equal opening quantity × opening rate. Opening quantity is not received into a godown.</p>
