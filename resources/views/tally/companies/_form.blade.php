@php
    $record = $company ?? null;
    $today = now();
    $fyStartYear = $today->month >= 4 ? $today->year : $today->year - 1;
    $startRaw = old('financial_year_start', $record?->financial_year_start?->toDateString() ?? sprintf('%d-04-01', $fyStartYear));
    $endRaw = old('financial_year_end', $record?->financial_year_end?->toDateString() ?? sprintf('%d-03-31', $fyStartYear + 1));
    $booksRaw = old('books_beginning_from', $record?->books_beginning_from?->toDateString() ?? $startRaw);
    $showDate = function ($value): string {
        try {
            return \Illuminate\Support\Carbon::parse($value)->format('j-M-y');
        } catch (\Throwable) {
            return (string) $value;
        }
    };
    $isoDate = function ($value): string {
        try {
            return \Illuminate\Support\Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return (string) $value;
        }
    };
@endphp
<div class="form-grid">
    <x-tally::form.field name="name" label="Company name" required>
        <input class="input" id="name" name="name" value="{{ old('name', $record?->name ?? '') }}" required maxlength="160" autofocus>
    </x-tally::form.field>
    <x-tally::form.field name="mailing_name" label="Mailing name">
        <input class="input" id="mailing_name" name="mailing_name" value="{{ old('mailing_name', $record?->mailing_name ?? $record?->name ?? '') }}" maxlength="200">
    </x-tally::form.field>
    <x-tally::form.field name="legal_name" label="Legal name">
        <input class="input" id="legal_name" name="legal_name" value="{{ old('legal_name', $record?->legal_name ?? '') }}" maxlength="200">
    </x-tally::form.field>

    <x-tally::form.field name="financial_year_start" label="FY beginning from" required>
        <input class="input" id="financial_year_start" name="financial_year_start" data-fy-start value="{{ $showDate($startRaw) }}" required autocomplete="off">
    </x-tally::form.field>
    <x-tally::form.field name="books_beginning_from" label="Books beginning from" required>
        <input class="input" id="books_beginning_from" name="books_beginning_from" data-books-from value="{{ $showDate($booksRaw) }}" required autocomplete="off">
    </x-tally::form.field>
    <x-tally::form.field name="country" label="Country" required>
        <input class="input" id="country" name="country" value="{{ old('country', $record?->country ?? 'India') }}" required maxlength="80">
    </x-tally::form.field>

    <x-tally::form.field name="address" label="Address" class="span-full">
        <textarea class="input" id="address" name="address" rows="2" maxlength="500">{{ old('address', $record?->address ?? '') }}</textarea>
    </x-tally::form.field>

    <x-tally::form.field name="city" label="City">
        <input class="input" id="city" name="city" value="{{ old('city', $record?->city ?? '') }}" maxlength="80">
    </x-tally::form.field>
    <x-tally::form.field name="state" label="State">
        <input class="input" id="state" name="state" value="{{ old('state', $record?->state ?? '') }}" maxlength="80" list="indian-states">
    </x-tally::form.field>
    <x-tally::form.field name="pincode" label="Pincode">
        <input class="input" id="pincode" name="pincode" value="{{ old('pincode', $record?->pincode ?? '') }}" maxlength="12">
    </x-tally::form.field>

    <x-tally::form.field name="phone" label="Telephone">
        <input class="input" id="phone" name="phone" value="{{ old('phone', $record?->phone ?? '') }}" maxlength="30">
    </x-tally::form.field>
    <x-tally::form.field name="mobile" label="Mobile">
        <input class="input" id="mobile" name="mobile" value="{{ old('mobile', $record?->mobile ?? '') }}" maxlength="30">
    </x-tally::form.field>
    <x-tally::form.field name="email" label="E-mail">
        <input class="input" id="email" name="email" type="email" value="{{ old('email', $record?->email ?? '') }}" maxlength="160">
    </x-tally::form.field>

    <x-tally::form.field name="fax" label="Fax">
        <input class="input" id="fax" name="fax" value="{{ old('fax', $record?->fax ?? '') }}" maxlength="30">
    </x-tally::form.field>
    <x-tally::form.field name="website" label="Website">
        <input class="input" id="website" name="website" value="{{ old('website', $record?->website ?? '') }}" maxlength="255">
    </x-tally::form.field>
    <x-tally::form.field name="currency_symbol" label="Currency symbol">
        <input class="input" id="currency_symbol" name="currency_symbol" value="{{ old('currency_symbol', $record?->currency_symbol ?? '₹') }}" maxlength="8">
    </x-tally::form.field>

    <x-tally::form.field name="currency_formal_name" label="Currency formal name">
        <input class="input" id="currency_formal_name" name="currency_formal_name" value="{{ old('currency_formal_name', $record?->currency_formal_name ?? 'INR') }}" maxlength="20">
    </x-tally::form.field>
    <x-tally::form.field name="gstin" label="GSTIN">
        <div class="gstin-fetch">
            <input class="input" id="gstin" name="gstin" data-gstin value="{{ old('gstin', $record?->gstin ?? '') }}" maxlength="15">
            <button class="btn" type="button" data-gstin-fetch="{{ tally_route('books.tally.gstin.lookup') }}">Fetch</button>
        </div>
        <p class="form-note" data-gstin-note></p>
    </x-tally::form.field>
    <x-tally::form.field name="pan" label="PAN">
        <input class="input" id="pan" name="pan" value="{{ old('pan', $record?->pan ?? '') }}" maxlength="10">
    </x-tally::form.field>

    <x-tally::form.field name="gst_registration_type" label="GST registration">
        <select class="input" id="gst_registration_type" name="gst_registration_type">
            <option value="">Not set</option>
            @foreach (\Tally\Tax\GstRegistrationType::cases() as $registration)
                <option value="{{ $registration->value }}" @selected(old('gst_registration_type', $record?->gst_registration_type?->value) === $registration->value)>{{ $registration->label() }}</option>
            @endforeach
        </select>
    </x-tally::form.field>
</div>

<input type="hidden" name="financial_year_end" data-fy-end value="{{ $isoDate($endRaw) }}">
<input type="hidden" name="is_active" value="{{ old('is_active', ($record?->is_active ?? true) ? '1' : '0') }}">
<input type="hidden" name="allow_negative_stock" value="{{ old('allow_negative_stock', $record?->allow_negative_stock ? '1' : '0') }}">
<input type="hidden" name="tax_pricing" value="{{ old('tax_pricing', $record?->tax_pricing?->value ?? 'exclusive') }}">
<input type="hidden" name="tax_rounding" value="{{ old('tax_rounding', $record?->tax_rounding?->value ?? 'paisa') }}">
<input type="hidden" name="sales_terms" value="{{ old('sales_terms', $record?->sales_terms ?? '') }}">
<input type="hidden" name="purchase_terms" value="{{ old('purchase_terms', $record?->purchase_terms ?? '') }}">

<datalist id="indian-states">
    @foreach (config('states') as $state)
        <option value="{{ $state }}"></option>
    @endforeach
</datalist>
<script>
(function () {
    const start = document.querySelector('[data-fy-start]');
    const books = document.querySelector('[data-books-from]');
    const end = document.querySelector('[data-fy-end]');
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    function read(value) {
        const match = String(value || '').trim().match(/^(\d{1,2})-([A-Za-z]{3})-(\d{2,4})$/);
        if (!match) {
            const iso = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})$/);
            return iso ? new Date(Number(iso[1]), Number(iso[2]) - 1, Number(iso[3])) : null;
        }
        let year = Number(match[3]);
        if (year < 100) {
            year += 2000;
        }
        const month = months.findIndex((name) => name.toLowerCase() === match[2].toLowerCase());
        return month < 0 ? null : new Date(year, month, Number(match[1]));
    }

    function show(date) {
        return date.getDate() + '-' + months[date.getMonth()] + '-' + String(date.getFullYear()).slice(-2);
    }

    function sync(previous) {
        const parsed = read(start.value);
        if (!parsed || Number.isNaN(parsed.getTime())) {
            return;
        }
        if (!books.value || books.value === previous) {
            books.value = show(parsed);
        }
        const closing = new Date(parsed.getFullYear() + 1, parsed.getMonth(), parsed.getDate() - 1);
        end.value = closing.getFullYear() + '-' + String(closing.getMonth() + 1).padStart(2, '0') + '-' + String(closing.getDate()).padStart(2, '0');
    }

    if (!start || !books || !end) {
        return;
    }
    let previous = start.value;
    start.addEventListener('change', function () {
        sync(previous);
        previous = start.value;
    });
})();
</script>
