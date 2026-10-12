<x-tally::layouts.app :title="$item->exists ? 'Edit recurring' : 'New recurring'">
    <x-tally::shell.page :title="$item->exists ? 'Edit recurring' : 'New recurring'" section="Utilities" :description="$company->name">
        <form method="POST" action="{{ $item->exists ? tally_route('books.tally.recurring.update', $item) : tally_route('books.tally.recurring.store') }}" class="stack">
            @csrf
            @if ($item->exists) @method('PUT') @endif

            <div class="form-grid">
                <x-tally::form.field name="name" label="Name" required>
                    <x-tally::form.input name="name" value="{{ old('name', $item->name) }}" required />
                </x-tally::form.field>
                <x-tally::form.field name="branch_id" label="Branch" required>
                    <select class="input" name="branch_id" required>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) old('branch_id', $item->branch_id) === (string) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </x-tally::form.field>
                <x-tally::form.field name="voucher_type" label="Type">
                    <select class="input" name="voucher_type">
                        @foreach (['journal','payment','receipt','contra','transfer'] as $type)
                            <option value="{{ $type }}" @selected(old('voucher_type', $item->voucher_type?->value) === $type)>{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </x-tally::form.field>

                <x-tally::form.field name="frequency" label="Frequency">
                    <select class="input" name="frequency">
                        @foreach (['weekly','monthly','quarterly','yearly'] as $freq)
                            <option value="{{ $freq }}" @selected(old('frequency', $item->frequency) === $freq)>{{ ucfirst($freq) }}</option>
                        @endforeach
                    </select>
                </x-tally::form.field>
                <x-tally::form.field name="day_of_month" label="Day of month">
                    <x-tally::form.input type="number" name="day_of_month" min="1" max="28" value="{{ old('day_of_month', $item->day_of_month) }}" />
                </x-tally::form.field>
                <x-tally::form.field name="next_run_on" label="Next run">
                    <x-tally::form.input type="date" name="next_run_on" value="{{ old('next_run_on', optional($item->next_run_on)->toDateString()) }}" />
                </x-tally::form.field>

                <x-tally::form.field name="start_date" label="Start">
                    <x-tally::form.input type="date" name="start_date" value="{{ old('start_date', optional($item->start_date)->toDateString()) }}" />
                </x-tally::form.field>
                <x-tally::form.field name="end_date" label="End">
                    <x-tally::form.input type="date" name="end_date" value="{{ old('end_date', optional($item->end_date)->toDateString()) }}" />
                </x-tally::form.field>
                <x-tally::form.field name="narration" label="Narration">
                    <x-tally::form.input name="narration" value="{{ old('narration', $item->narration) }}" />
                </x-tally::form.field>

                <label class="field check-field">
                    <span class="field-label-spacer"></span>
                    <span class="check"><input type="checkbox" name="auto_post" value="1" @checked(old('auto_post', $item->auto_post))> Auto-post</span>
                </label>
                <label class="field check-field">
                    <span class="field-label-spacer"></span>
                    <span class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true))> Active</span>
                </label>
            </div>

            <h3 class="text-sm font-semibold text-slate-700 mt-1">Lines</h3>
            @php $entries = old('entries', $item->lines?->map(fn ($l) => ['ledger_id' => $l->ledger_id, 'debit' => $l->debit, 'credit' => $l->credit])->all() ?? [['ledger_id'=>'','debit'=>'','credit'=>''],['ledger_id'=>'','debit'=>'','credit'=>'']]); @endphp
            <div class="table-wrap">
                <table class="data tally-voucher">
                    <thead><tr><th>Ledger</th><th class="money">Debit</th><th class="money">Credit</th></tr></thead>
                    <tbody>
                        @foreach ($entries as $i => $entry)
                            <tr>
                                <td>
                                    <select class="input" name="entries[{{ $i }}][ledger_id]">
                                        <option value="">—</option>
                                        @foreach ($ledgers as $ledger)
                                            <option value="{{ $ledger->id }}" @selected((string) ($entry['ledger_id'] ?? '') === (string) $ledger->id)>{{ $ledger->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input class="input" name="entries[{{ $i }}][debit]" value="{{ $entry['debit'] ?? '' }}" inputmode="decimal"></td>
                                <td><input class="input" name="entries[{{ $i }}][credit]" value="{{ $entry['credit'] ?? '' }}" inputmode="decimal"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Save</button>
                <a class="btn" href="{{ tally_route('books.tally.recurring.index') }}">Cancel</a>
            </div>
        </form>
    </x-tally::shell.page>
</x-tally::layouts.app>
