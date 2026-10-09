@php
    $selectedCentre = collect($costCentres ?? [])->first(fn ($centre) => (string) $centre->id === (string) ($entry['cost_centre_id'] ?? ''));
@endphp
<div class="picker" data-picker>
    <input class="input picker-query" data-picker-query autocomplete="off" placeholder="Cost centre" value="{{ $selectedCentre->name ?? '' }}">
    <select class="picker-store" name="entries[{{ $index }}][cost_centre_id]" tabindex="-1" aria-hidden="true">
        <option value="">Cost centre</option>
        @foreach (($costCentres ?? []) as $centre)
            <option value="{{ $centre->id }}" @selected((string) ($entry['cost_centre_id'] ?? '') === (string) $centre->id)>{{ $centre->name }}</option>
        @endforeach
    </select>
    <div class="picker-list" hidden>
        @foreach (($costCentres ?? []) as $centre)
            <button type="button" data-picker-choice data-value="{{ $centre->id }}">{{ $centre->name }}</button>
        @endforeach
        <a href="{{ tally_route('books.tally.cost-centres.create') }}">Create</a>
    </div>
</div>
