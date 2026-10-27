<x-tally::layouts.app :title="$title">
    <x-tally::shell.page :title="$title" section="Banking" :description="$company->name">
        <a class="esc-back" data-esc href="{{ tally_route('books.tally.banking.menu') }}">Quit</a>

        <nav class="filters" aria-label="Cheque views" style="margin-bottom: 1rem; gap: 0.5rem; display: flex; flex-wrap: wrap;">
            <a class="btn {{ $mode === 'printing' ? 'btn-primary' : '' }}" href="{{ tally_route('books.tally.banking.cheques.printing') }}">Cheque Printing</a>
            <a class="btn {{ $mode === 'register' ? 'btn-primary' : '' }}" href="{{ tally_route('books.tally.banking.cheques') }}">Cheque Register</a>
            <a class="btn {{ $mode === 'post-dated' ? 'btn-primary' : '' }}" href="{{ tally_route('books.tally.banking.cheques.post-dated') }}">Post-Dated Summary</a>
        </nav>

        @if ($mode === 'register')
            <p class="muted" style="margin-bottom: 0.75rem;">Create and maintain all cheques for this financial year.</p>
            <form class="panel" method="POST" action="{{ tally_route('books.tally.banking.cheques.store') }}">
                @csrf
                <x-tally::form.field name="number" label="Cheque no." required>
                    <x-tally::form.input name="number" value="{{ old('number', $nextNumber) }}" required maxlength="30" />
                </x-form.field>
                <x-tally::form.field name="instrument_date" label="Date" required>
                    <x-tally::form.input name="instrument_date" type="date" value="{{ old('instrument_date', now()->toDateString()) }}" required />
                </x-form.field>
                <x-tally::form.field name="amount" label="Amount" required>
                    <x-tally::form.input name="amount" value="{{ old('amount') }}" inputmode="decimal" required />
                </x-form.field>
                <x-tally::form.field name="favouring" label="Favouring" required>
                    <x-tally::form.input name="favouring" value="{{ old('favouring') }}" required maxlength="160" />
                </x-form.field>
                <x-tally::form.field name="bank_account_id" label="Bank">
                    @include('tally::masters._picker', [
                        'name' => 'bank_account_id',
                        'options' => $accounts->mapWithKeys(fn ($account) => [$account->id => $account->bank_name.' · '.$account->account_number])->all(),
                        'current' => old('bank_account_id'),
                        'placeholder' => 'Bank',
                        'optional' => true,
                    ])
                </x-form.field>
                <x-tally::form.field name="party_ledger_id" label="Party">
                    @include('tally::masters._picker', [
                        'name' => 'party_ledger_id',
                        'options' => $parties->mapWithKeys(fn ($ledger) => [$ledger->id => $ledger->name])->all(),
                        'current' => old('party_ledger_id'),
                        'placeholder' => 'Party',
                        'optional' => true,
                        'create' => tally_route('books.tally.ledgers.create'),
                    ])
                </x-form.field>
                <x-tally::form.field name="narration" label="Narration">
                    <x-tally::form.input name="narration" value="{{ old('narration') }}" maxlength="500" />
                </x-form.field>
                <button class="btn btn-primary" type="submit">Accept</button>
            </form>
        @elseif ($mode === 'printing')
            <p class="muted" style="margin-bottom: 0.75rem;">Open cheques ready to print. Use Register to create or edit cheques.</p>
        @else
            <p class="muted" style="margin-bottom: 0.75rem;">Cheques dated after today (as on {{ now()->format('d-m-Y') }}). Read-only summary.</p>
        @endif

        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Cheque No.</th>
                        <th>Bank</th>
                        <th>Favouring</th>
                        <th>Party</th>
                        <th class="money">Amount</th>
                        <th>Status</th>
                        @if ($mode !== 'post-dated')
                            <th></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        @if ($mode === 'register')
                            <tr>
                                <td colspan="8">
                                    <form method="POST" action="{{ tally_route('books.tally.banking.cheques.update', $row) }}" class="filters">
                                        @csrf
                                        @method('PUT')
                                        <input class="input" type="date" name="instrument_date" value="{{ $row->instrument_date->toDateString() }}" required>
                                        <input class="input" name="number" value="{{ $row->number }}" required maxlength="30">
                                        <input type="hidden" name="bank_account_id" value="{{ $row->bank_account_id }}">
                                        <span>{{ $row->bankAccount?->bank_name }}</span>
                                        <input class="input" name="favouring" value="{{ $row->favouring }}" required>
                                        <input class="input money" name="amount" value="{{ $row->amount }}" required>
                                        <input type="hidden" name="party_ledger_id" value="{{ $row->party_ledger_id }}">
                                        <select class="input" name="status">
                                            @foreach (['open' => 'Not printed', 'printed' => 'Printed', 'deposited' => 'Deposited', 'cancelled' => 'Cancelled'] as $value => $label)
                                                <option value="{{ $value }}" @selected($row->status === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <button class="btn" type="submit">Accept</button>
                                        @if ($row->status === 'open' || $row->status === 'printed')
                                            <a class="btn" href="{{ tally_route('books.tally.banking.cheques.print', $row) }}" target="_blank">Print</a>
                                        @endif
                                    </form>
                                </td>
                            </tr>
                        @else
                            <tr>
                                <td>{{ $row->instrument_date->format('d-m-Y') }}</td>
                                <td>{{ $row->number }}</td>
                                <td>{{ $row->bankAccount?->bank_name ?? '—' }}</td>
                                <td>{{ $row->favouring }}</td>
                                <td>{{ $row->party?->name ?? '—' }}</td>
                                <td class="money">{{ $row->amount }}</td>
                                <td>
                                    @php
                                        $statusLabel = [
                                            'open' => 'Not printed',
                                            'printed' => 'Printed',
                                            'deposited' => 'Deposited',
                                            'cancelled' => 'Cancelled',
                                        ][$row->status] ?? $row->status;
                                    @endphp
                                    {{ $statusLabel }}
                                </td>
                                @if ($mode === 'printing')
                                    <td>
                                        <a class="btn btn-primary" href="{{ tally_route('books.tally.banking.cheques.print', $row) }}" target="_blank">Print</a>
                                    </td>
                                @endif
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="{{ $mode === 'post-dated' ? 7 : 8 }}">
                                @if ($mode === 'printing')
                                    No open cheques to print.
                                @elseif ($mode === 'post-dated')
                                    No post-dated cheques.
                                @else
                                    No cheques in this list.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($mode === 'post-dated' && $rows->isNotEmpty())
                    <tfoot>
                        <tr>
                            <th colspan="5">Total ({{ $rows->count() }} cheques)</th>
                            <th class="money">{{ number_format($rows->sum(fn ($r) => (float) $r->amount), 2, '.', '') }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </x-tally::shell.page>
</x-tally::layouts.app>
