@php
    $current = (string) ($current ?? '');
    $selected = collect($hsnSacs ?? [])->first(fn ($hsn) => (string) $hsn->id === $current);
@endphp
<div class="picker" data-picker data-hsn data-hsn-catalogue="{{ tally_route('books.tally.hsn-catalogue.index') }}" data-hsn-adopt="{{ tally_route('books.tally.hsn-catalogue.store') }}">
    <input class="input picker-query" data-picker-query inputmode="numeric" maxlength="8" autocomplete="off" placeholder="Type 4, 6 or 8 digits" value="{{ $selected->code ?? '' }}">
    <select class="picker-store" name="{{ $name }}" data-hsn-store tabindex="-1" aria-hidden="true">
        <option value="">None</option>
        @foreach ($hsnSacs ?? [] as $hsn)
            <option value="{{ $hsn->id }}" data-code="{{ $hsn->code }}" data-tax="{{ $hsn->tax_rate_id }}" @selected($current === (string) $hsn->id)>{{ $hsn->code }}</option>
        @endforeach
    </select>
    <div class="picker-list" hidden>
        @foreach ($hsnSacs ?? [] as $hsn)
            <button type="button" data-picker-choice data-value="{{ $hsn->id }}" data-code="{{ $hsn->code }}" data-tax="{{ $hsn->tax_rate_id }}">{{ $hsn->code }}{{ $hsn->description ? ' · '.$hsn->description : '' }}</button>
        @endforeach
    </div>
</div>
