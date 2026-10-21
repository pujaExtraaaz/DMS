@php
    $selected = (string) old($name, $company->{$name} ?? '');
@endphp
<x-tally::form.field :name="$name" :label="$label">
    <select class="input" name="{{ $name }}">
        <option value="">{{ $ledgers->isEmpty() ? ($empty ?? '—') : '—' }}</option>
        @foreach ($ledgers as $ledger)
            <option value="{{ $ledger->id }}" @selected($selected === (string) $ledger->id)>{{ $ledger->name }}{{ $ledger->accountGroup ? ' · '.$ledger->accountGroup->name : '' }}</option>
        @endforeach
    </select>
</x-tally::form.field>
