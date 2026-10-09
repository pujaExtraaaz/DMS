@props(['kind', 'partyLedgers', 'accountLedgers', 'invoice'])
@foreach ([
    ['name' => 'party_ledger_id', 'label' => $kind->partyLabel(), 'ledgers' => $partyLedgers, 'empty' => $kind->missingPartyMessage(), 'current' => old('party_ledger_id', $invoice->party_ledger_id)],
    ['name' => 'account_ledger_id', 'label' => $kind->accountLabel(), 'ledgers' => $accountLedgers, 'empty' => $kind->missingAccountMessage(), 'current' => old('account_ledger_id', $invoice->account_ledger_id)],
] as $field)
    @php
        $selected = collect($field['ledgers'])->first(fn ($ledger) => (string) $ledger->id === (string) $field['current']);
    @endphp
    <div class="tally-co-row">
        <span>{{ $field['label'] }}</span>
        <span>:</span>
        <div>
            <div class="picker" data-picker>
                <input id="{{ $field['name'] }}" class="input picker-query {{ $field['name'] === 'party_ledger_id' ? 'is-account-field' : '' }}" data-picker-query autocomplete="off" value="{{ $selected->name ?? '' }}">
                <select class="picker-store" name="{{ $field['name'] }}" required tabindex="-1" aria-hidden="true">
                    <option value="">Select {{ strtolower($field['label']) }}</option>
                    @foreach ($field['ledgers'] as $ledger)
                        <option value="{{ $ledger->id }}" data-state="{{ $ledger->state }}" @selected((string) $field['current'] === (string) $ledger->id)>{{ $ledger->name }}</option>
                    @endforeach
                </select>
                <div class="picker-list" hidden>
                    @foreach ($field['ledgers'] as $ledger)
                        <button type="button" data-picker-choice data-value="{{ $ledger->id }}">{{ $ledger->name }}</button>
                    @endforeach
                    <a href="{{ tally_route('books.tally.ledgers.create') }}">Create</a>
                </div>
                <a class="picker-alter" data-picker-alter data-base="{{ url('/masters/ledgers/__ID__/edit') }}" href="{{ $selected ? tally_route('books.tally.ledgers.edit', $selected) : '#' }}" @unless ($selected) hidden @endunless>Alter</a>
            </div>
            @if ($field['ledgers']->isEmpty())
                <p class="form-note">{{ $field['empty'] }}</p>
            @endif
        </div>
    </div>
@endforeach
