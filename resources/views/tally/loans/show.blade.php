<x-tally::layouts.app title="Loan schedule">
    <x-tally::shell.page title="{{ $loan->loan_number }}" section="Liabilities" :description="$loan->lender_name">
        <x-slot:actions>
            @if (! $loan->disbursement_voucher_id)
                <form method="POST" action="{{ tally_route('books.tally.loans.disburse', $loan) }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">Post disbursement</button>
                </form>
            @else
                <span class="muted">Disbursed (voucher #{{ $loan->disbursement_voucher_id }})</span>
            @endif
        </x-slot:actions>
        <dl class="meta">
            <div><dt>Principal</dt><dd>{{ $loan->principal }}</dd></div>
            <div><dt>Outstanding</dt><dd>{{ $loan->outstanding_principal }}</dd></div>
            <div><dt>EMI</dt><dd>{{ $loan->emi_amount }}</dd></div>
            <div><dt>Rate</dt><dd>{{ $loan->interest_rate }}%</dd></div>
            <div><dt>Tenure</dt><dd>{{ $loan->tenure_months }} months</dd></div>
            <div><dt>Loan ledger</dt><dd>{{ $loan->loanLedger?->name ?? '—' }}</dd></div>
            <div><dt>Interest ledger</dt><dd>{{ $loan->interestLedger?->name ?? '—' }}</dd></div>
        </dl>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Due</th>
                        <th class="money">Principal</th>
                        <th class="money">Interest</th>
                        <th class="money">EMI</th>
                        <th class="money">Outstanding</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($loan->schedules as $row)
                        <tr>
                            <td>{{ $row->installment_no }}</td>
                            <td>{{ $row->due_date->format('d M Y') }}</td>
                            <td class="money">{{ $row->principal_component }}</td>
                            <td class="money">{{ $row->interest_component }}</td>
                            <td class="money">{{ $row->emi_amount }}</td>
                            <td class="money">{{ $row->outstanding_after }}</td>
                            <td>{{ $row->is_paid ? 'Paid' : 'Due' }}</td>
                            <td>
                                @if (! $row->is_paid && $loan->disbursement_voucher_id)
                                    <form method="POST" action="{{ tally_route('books.tally.loans.schedules.pay', ['loan' => $loan, 'schedule' => $row]) }}" class="inline-form">
                                        @csrf
                                        @if ($bankLedgers->isNotEmpty())
                                            <select class="input" name="bank_ledger_id" style="max-width:10rem">
                                                <option value="">Default bank</option>
                                                @foreach ($bankLedgers as $ledger)
                                                    <option value="{{ $ledger->id }}">{{ $ledger->name }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                        <button class="btn btn-sm" type="submit">Pay EMI</button>
                                    </form>
                                @elseif ($row->payment_voucher_id)
                                    <span class="muted">#{{ $row->payment_voucher_id }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-tally::shell.page>
</x-tally::layouts.app>
