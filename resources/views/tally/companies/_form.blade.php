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
<div class="tally-co-sheet">
    <div class="tally-co-title">{{ $record ? 'Company Alteration' : 'Company Creation' }}</div>
    <div class="tally-co-split">
        <div>
            <label class="tally-co-row">
                <span>Company Name</span>
                <span>:</span>
                <input class="input" name="name" value="{{ old('name', $record?->name ?? '') }}" required maxlength="160" autofocus>
            </label>
            <label class="tally-co-row">
                <span>Mailing Name</span>
                <span>:</span>
                <input class="input" name="mailing_name" value="{{ old('mailing_name', $record?->mailing_name ?? $record?->name ?? '') }}" maxlength="200">
            </label>
        </div>
        <div>
            <label class="tally-co-row">
                <span>Financial year beginning from</span>
                <span>:</span>
                <input class="input" name="financial_year_start" data-fy-start value="{{ $showDate($startRaw) }}" required autocomplete="off">
            </label>
            <label class="tally-co-row">
                <span>Books beginning from</span>
                <span>:</span>
                <input class="input" name="books_beginning_from" data-books-from value="{{ $showDate($booksRaw) }}" required autocomplete="off">
            </label>
        </div>
    </div>
    <label class="tally-co-row is-address">
        <span>Address</span>
        <span>:</span>
        <textarea class="input" name="address" rows="3" maxlength="500">{{ old('address', $record?->address ?? '') }}</textarea>
    </label>
    <label class="tally-co-row">
        <span>State</span>
        <span>:</span>
        <input class="input" name="state" value="{{ old('state', $record?->state ?? '') }}" maxlength="80" list="indian-states">
    </label>
    <label class="tally-co-row">
        <span>Country</span>
        <span>:</span>
        <input class="input" name="country" value="{{ old('country', $record?->country ?? 'India') }}" required maxlength="80">
    </label>
    <label class="tally-co-row">
        <span>Pincode</span>
        <span>:</span>
        <input class="input" name="pincode" value="{{ old('pincode', $record?->pincode ?? '') }}" maxlength="12">
    </label>
    <label class="tally-co-row">
        <span>Telephone</span>
        <span>:</span>
        <input class="input" name="phone" value="{{ old('phone', $record?->phone ?? '') }}" maxlength="30">
    </label>
    <label class="tally-co-row">
        <span>Mobile</span>
        <span>:</span>
        <input class="input" name="mobile" value="{{ old('mobile', $record?->mobile ?? '') }}" maxlength="30">
    </label>
    <label class="tally-co-row">
        <span>Fax</span>
        <span>:</span>
        <input class="input" name="fax" value="{{ old('fax', $record?->fax ?? '') }}" maxlength="30">
    </label>
    <label class="tally-co-row">
        <span>E-mail</span>
        <span>:</span>
        <input class="input" name="email" type="email" value="{{ old('email', $record?->email ?? '') }}" maxlength="160">
    </label>
    <label class="tally-co-row">
        <span>Website</span>
        <span>:</span>
        <input class="input" name="website" value="{{ old('website', $record?->website ?? '') }}" maxlength="255">
    </label>
    <div class="tally-co-rule"></div>
    <label class="tally-co-row">
        <span>Base Currency symbol</span>
        <span>:</span>
        <input class="input" name="currency_symbol" value="{{ old('currency_symbol', $record?->currency_symbol ?? '₹') }}" maxlength="8">
    </label>
    <label class="tally-co-row">
        <span>Formal name</span>
        <span>:</span>
        <input class="input" name="currency_formal_name" value="{{ old('currency_formal_name', $record?->currency_formal_name ?? 'INR') }}" maxlength="20">
    </label>
    <input type="hidden" name="financial_year_end" data-fy-end value="{{ $isoDate($endRaw) }}">
    <input type="hidden" name="is_active" value="{{ old('is_active', ($record?->is_active ?? true) ? '1' : '0') }}">
    <input type="hidden" name="allow_negative_stock" value="{{ old('allow_negative_stock', $record?->allow_negative_stock ? '1' : '0') }}">
    <input type="hidden" name="tax_pricing" value="{{ old('tax_pricing', $record?->tax_pricing?->value ?? 'exclusive') }}">
    <input type="hidden" name="tax_rounding" value="{{ old('tax_rounding', $record?->tax_rounding?->value ?? 'paisa') }}">
    <input type="hidden" name="sales_terms" value="{{ old('sales_terms', $record?->sales_terms ?? '') }}">
    <input type="hidden" name="purchase_terms" value="{{ old('purchase_terms', $record?->purchase_terms ?? '') }}">

    <details class="tally-co-more">
        <summary>Statutory</summary>
        <label class="tally-co-row">
            <span>GSTIN</span>
            <span>:</span>
            <span class="gstin-fetch">
                <input class="input" name="gstin" data-gstin value="{{ old('gstin', $record?->gstin ?? '') }}" maxlength="15">
                <button class="btn" type="button" data-gstin-fetch="{{ tally_route('books.tally.gstin.lookup') }}">Fetch</button>
            </span>
        </label>
        <p class="form-note" data-gstin-note></p>
        <label class="tally-co-row">
            <span>PAN</span>
            <span>:</span>
            <input class="input" name="pan" value="{{ old('pan', $record?->pan ?? '') }}" maxlength="10">
        </label>
        <label class="tally-co-row">
            <span>Legal name</span>
            <span>:</span>
            <input class="input" name="legal_name" value="{{ old('legal_name', $record?->legal_name ?? '') }}" maxlength="200">
        </label>
        <label class="tally-co-row">
            <span>City</span>
            <span>:</span>
            <input class="input" name="city" value="{{ old('city', $record?->city ?? '') }}" maxlength="80">
        </label>
        <label class="tally-co-row">
            <span>GST registration</span>
            <span>:</span>
            <select class="input" name="gst_registration_type">
                <option value="">Not set</option>
                @foreach (\Tally\Tax\GstRegistrationType::cases() as $registration)
                    <option value="{{ $registration->value }}" @selected(old('gst_registration_type', $record?->gst_registration_type?->value) === $registration->value)>{{ $registration->label() }}</option>
                @endforeach
            </select>
        </label>
    </details>
</div>
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

    let previous = start.value;
    start.addEventListener('change', function () {
        sync(previous);
        previous = start.value;
    });
})();
</script>
