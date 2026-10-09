<x-tally::layouts.app title="Bank reconciliation">
    <x-tally::shell.page title="Bank reconciliation" section="Transactions" :description="$company->name.' · '.$year->name">
        <x-slot:actions>
            <button class="btn" type="button" onclick="window.print()">Print</button>
        </x-slot:actions>
        <p class="form-note">Reconciling marks a posted receipt, payment, contra, or journal. It does not post another voucher. Difference is the book amount minus the bank amount.</p>
        @if ($errors->any())
            <p class="flash is-error" role="alert">{{ $errors->first() }}</p>
        @endif
        @include('tally::banking._filters', ['action' => tally_route('books.tally.banking.reconciliation')])
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Book date</th>
                        <th>Voucher</th>
                        <th>Reference</th>
                        <th>Transaction date</th>
                        <th class="money">Book amount</th>
                        <th class="money">Bank amount</th>
                        <th class="money">Difference</th>
                        <th>Status</th>
                        <th>Reconciliation date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php
                            $entry = $row['entry'];
                            $formId = 'reconcile-'.$entry->id;
                        @endphp
                        <tr>
                            <td>{{ $row['date'] }}</td>
                            <td><a href="{{ $row['url'] }}">{{ $row['number'] }}</a><br><span class="muted">{{ $row['ledger'] }}</span></td>
                            @if ($row['status'] === \Tally\Banking\ReconciliationStatus::Reconciled)
                                <td>{{ $row['reference'] ?: '—' }}</td>
                                <td>{{ \Illuminate\Support\Carbon::parse($row['transaction_date'])->format('d M Y') }}</td>
                                <td class="money">{{ $row['book_amount'] }} {{ $row['book_side'] }}</td>
                                <td class="money">{{ $row['bank_amount'] }}</td>
                                <td class="money">{{ $row['difference'] }}</td>
                                <td>Reconciled</td>
                                <td>{{ $row['reconciled_on'] ? \Illuminate\Support\Carbon::parse($row['reconciled_on'])->format('d M Y') : '—' }}</td>
                                <td>
                                    <form method="POST" action="{{ tally_route('books.tally.banking.unreconcile', $entry) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn" type="submit">Unreconcile</button>
                                    </form>
                                </td>
                            @else
                                <td><input class="input" form="{{ $formId }}" name="reference" value="{{ $row['reference'] }}" maxlength="50"></td>
                                <td><input class="input" form="{{ $formId }}" type="date" name="transaction_date" value="{{ $row['transaction_date'] }}"></td>
                                <td class="money">{{ $row['book_amount'] }} {{ $row['book_side'] }}</td>
                                <td><input class="input" form="{{ $formId }}" name="bank_amount" value="{{ $row['bank_amount'] ?? $row['book_amount'] }}"></td>
                                <td class="money">{{ $row['difference'] ?? '—' }}</td>
                                <td>Unreconciled</td>
                                <td><input class="input" form="{{ $formId }}" type="date" name="reconciliation_date" value="{{ $row['reconciled_on'] ?? $row['transaction_date'] }}"></td>
                                <td><button class="btn btn-primary" type="submit" form="{{ $formId }}">Reconcile</button></td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="10">No posted bank transactions in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @foreach ($rows as $row)
            @if ($row['status'] !== \Tally\Banking\ReconciliationStatus::Reconciled)
                <form id="reconcile-{{ $row['entry']->id }}" method="POST" action="{{ tally_route('books.tally.banking.reconcile', $row['entry']) }}">
                    @csrf
                </form>
            @endif
        @endforeach
    </x-shell.page>
</x-layouts.app>
