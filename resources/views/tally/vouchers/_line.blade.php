<tr>
    <td>
        @php
            $selectedLedger = collect($ledgers)->first(fn ($ledger) => (string) $ledger->id === (string) ($entry['ledger_id'] ?? ''));
            $refLabel = $refLabel ?? (($mode ?? '') === 'journal' ? 'New Ref' : 'Agst Ref');
        @endphp
        @if (($mode ?? '') === 'journal')
            <span class="by-to" data-by-to>By</span>
        @endif
        <div class="picker" data-picker>
            <input class="input picker-query" data-picker-query autocomplete="off" placeholder="Ledger" value="{{ $selectedLedger->name ?? '' }}" @if (! empty($autofocus)) autofocus @endif>
            <select class="picker-store" name="entries[{{ $index }}][ledger_id]" tabindex="-1" aria-hidden="true">
                <option value="">Select ledger</option>
                @foreach ($ledgers as $ledger)
                    <option value="{{ $ledger->id }}" data-balance="{{ ($balances ?? [])[$ledger->id] ?? '' }}" @selected((string) ($entry['ledger_id'] ?? '') === (string) $ledger->id)>{{ $ledger->name }}</option>
                @endforeach
            </select>
            <div class="picker-list" hidden>
                @foreach ($ledgers as $ledger)
                    <button type="button" data-picker-choice data-value="{{ $ledger->id }}">{{ $ledger->name }}</button>
                @endforeach
                <a href="{{ tally_route('books.tally.ledgers.create') }}">Create</a>
            </div>
            <a class="picker-alter" data-picker-alter data-base="{{ url('/masters/ledgers/__ID__/edit') }}" href="{{ $selectedLedger ? tally_route('books.tally.ledgers.edit', $selectedLedger) : '#' }}" @unless ($selectedLedger) hidden @endunless>Alter</a>
        </div>
        <div class="cur-bal" data-line-balance></div>
        @if (! empty($account))
            <input type="hidden" name="entries[{{ $index }}][reference]" value="">
            <input type="hidden" name="entries[{{ $index }}][narration]" value="">
            <input type="hidden" name="entries[{{ $index }}][cost_centre_id]" value="">
        @else
            <label class="ref-line">{{ $refLabel }}
                <input class="input" name="entries[{{ $index }}][reference]" maxlength="50" value="{{ $entry['reference'] ?? '' }}">
            </label>
            <input type="hidden" name="entries[{{ $index }}][narration]" value="{{ $entry['narration'] ?? '' }}">
            @if (count($costCentres ?? []) > 0)
                @include('tally::vouchers._centre', ['index' => $index, 'entry' => $entry, 'costCentres' => $costCentres])
            @else
                <input type="hidden" name="entries[{{ $index }}][cost_centre_id]" value="">
            @endif
        @endif
    </td>
    @if (($mode ?? '') === 'journal')
        <td><input class="input money" data-amount="debit" name="entries[{{ $index }}][debit]" inputmode="decimal" value="{{ $entry['debit'] ?? '' }}"></td>
        <td><input class="input money" data-amount="credit" name="entries[{{ $index }}][credit]" inputmode="decimal" value="{{ $entry['credit'] ?? '' }}"></td>
    @elseif (! empty($account))
        <td>
            <input type="hidden" data-account-amount data-amount="{{ $mode }}" name="entries[{{ $index }}][{{ $mode }}]" value="{{ $entry['amount'] ?? ($entry[$mode] ?? '') }}">
            <input type="hidden" name="entries[{{ $index }}][{{ $mode === 'debit' ? 'credit' : 'debit' }}]" value="0">
        </td>
    @else
        <td>
            <input class="input money" data-amount="{{ $mode }}" name="entries[{{ $index }}][{{ $mode }}]" inputmode="decimal" value="{{ $entry['amount'] ?? ($entry[$mode] ?? '') }}">
            <input type="hidden" name="entries[{{ $index }}][{{ $mode === 'debit' ? 'credit' : 'debit' }}]" value="0">
        </td>
    @endif
    <td class="row-actions"><button type="button" data-remove-line>Remove</button></td>
</tr>
