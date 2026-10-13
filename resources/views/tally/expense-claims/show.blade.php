<x-tally::layouts.app :title="$claim->number">
    <x-tally::shell.page :title="$claim->number" section="Payables" :description="ucfirst($claim->status)">
        <x-slot:actions>
            @if (in_array($claim->status, ['draft', 'submitted'], true))
                <form method="POST" action="{{ tally_route('books.tally.expense-claims.approve', $claim) }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">Approve</button>
                </form>
            @endif
            @if ($claim->status === 'approved')
                <form method="POST" action="{{ tally_route('books.tally.expense-claims.pay', $claim) }}" class="flex items-center gap-2">
                    @csrf
                    <select name="bank_ledger_id" required class="input">
                        <option value="">Bank / cash</option>
                        @foreach ($bankLedgers as $ledger)
                            <option value="{{ $ledger->id }}">{{ $ledger->name }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-primary" type="submit">Pay</button>
                </form>
            @endif
            <a class="btn" href="{{ tally_route('books.tally.expense-claims.index') }}">Back</a>
        </x-slot:actions>
        <dl class="kv">
            <dt>Date</dt><dd>{{ $claim->claim_date->format('d M Y') }}</dd>
            <dt>Employee</dt><dd>{{ $claim->employeeLedger?->name }}</dd>
            <dt>Amount</dt><dd>{{ $claim->amount }}</dd>
            @if ($claim->approver)
                <dt>Approved by</dt><dd>{{ $claim->approver->name }}</dd>
            @endif
            @if ($claim->accrualVoucher)
                <dt>Accrual</dt><dd>{{ $claim->accrualVoucher->number }}</dd>
            @endif
            @if ($claim->paymentVoucher)
                <dt>Payment</dt><dd>{{ $claim->paymentVoucher->number }}</dd>
            @endif
        </dl>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Date</th><th>Category</th><th>Ledger</th><th>Description</th><th class="money">Amount</th></tr></thead>
                <tbody>
                    @foreach ($claim->lines as $line)
                        <tr>
                            <td>{{ $line->expense_date?->format('d M Y') }}</td>
                            <td>{{ $line->category }}</td>
                            <td>{{ $line->expenseLedger?->name }}</td>
                            <td>{{ $line->description }}</td>
                            <td class="money">{{ $line->amount }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-tally::shell.page>
</x-tally::layouts.app>
