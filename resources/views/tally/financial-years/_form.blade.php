@php($record = $financialYear ?? null)
<div class="form-grid">
    <x-tally::form.field name="name" label="Name" required>
        <x-tally::form.input name="name" :value="old('name', $record?->name ?? '')" required maxlength="40" />
    </x-tally::form.field>
    <x-tally::form.field name="start_date" label="Start date" required>
        <x-tally::form.input name="start_date" type="date" :value="old('start_date', $record?->start_date?->format('Y-m-d') ?? '')" required />
    </x-tally::form.field>
    <x-tally::form.field name="end_date" label="End date" required>
        <x-tally::form.input name="end_date" type="date" :value="old('end_date', $record?->end_date?->format('Y-m-d') ?? '')" required />
    </x-tally::form.field>
    <x-tally::form.field name="opening_profit" label="Opening profit">
        <x-tally::form.input name="opening_profit" value="{{ old('opening_profit', $record?->opening_profit ?? '0.00') }}" inputmode="decimal" />
    </x-tally::form.field>
    <x-tally::form.active :checked="$record?->is_active ?? true" />
</div>
<p class="form-note">Opening profit is a credit carried into this year. A loss is a negative amount. The period cannot overlap another year of {{ $company->name }}.</p>
