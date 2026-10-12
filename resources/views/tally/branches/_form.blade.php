@php($record = $branch ?? null)
<div class="form-grid">
    <x-tally::form.field name="name" label="Name" required>
        <x-tally::form.input name="name" :value="old('name', $record?->name ?? '')" required maxlength="160" />
    </x-tally::form.field>
    <x-tally::form.field name="code" label="Code" required>
        <x-tally::form.input name="code" :value="old('code', $record?->code ?? '')" required maxlength="20" autocapitalize="characters" />
    </x-tally::form.field>
    <x-tally::form.field name="phone" label="Phone">
        <x-tally::form.input name="phone" :value="old('phone', $record?->phone ?? '')" maxlength="30" />
    </x-tally::form.field>
    <x-tally::form.field name="city" label="City">
        <x-tally::form.input name="city" :value="old('city', $record?->city ?? '')" maxlength="80" />
    </x-tally::form.field>
    <x-tally::form.field name="state" label="State">
        <x-tally::form.input name="state" :value="old('state', $record?->state ?? '')" maxlength="80" list="indian-states" />
    </x-tally::form.field>
    <x-tally::form.field name="country" label="Country" required>
        <x-tally::form.input name="country" :value="old('country', $record?->country ?? 'India')" required maxlength="80" />
    </x-tally::form.field>
    <x-tally::form.field name="pincode" label="Pincode">
        <x-tally::form.input name="pincode" :value="old('pincode', $record?->pincode ?? '')" maxlength="12" />
    </x-tally::form.field>
    <x-tally::form.field name="email" label="Email">
        <x-tally::form.input name="email" type="email" :value="old('email', $record?->email ?? '')" maxlength="160" />
    </x-tally::form.field>
    <x-tally::form.active :checked="$record?->is_active ?? true" />
    <x-tally::form.field class="span-full" name="address" label="Address">
        <x-tally::form.textarea name="address" rows="2" maxlength="500" :value="old('address', $record?->address ?? '')" />
    </x-tally::form.field>
</div>
<datalist id="indian-states">
    @foreach (config('states') as $state)
        <option value="{{ $state }}"></option>
    @endforeach
</datalist>
<p class="form-note">Branch code must be unique within {{ $company->name }}.</p>
