<x-tally::layouts.app :title="$batch->number">
    <x-tally::shell.page :title="$batch->number" section="Payables" :description="str_replace('_', ' ', ucfirst($batch->status))">
        <x-slot:actions>
            @if ($batch->status === 'draft')
                <form method="POST" action="{{ tally_route('books.tally.payment-batches.submit', $batch) }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">Submit</button>
                </form>
            @endif
            @if ($batch->status === 'pending_approval')
                <form method="POST" action="{{ tally_route('books.tally.payment-batches.approve', $batch) }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">Approve</button>
                </form>
            @endif
            @if ($batch->status === 'approved' && $batch->bank_ledger_id && $batch->lines->isNotEmpty())
                <form method="POST" action="{{ tally_route('books.tally.payment-batches.pay', $batch) }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">Post payments</button>
                </form>
            @endif
            @if ($batch->status === 'paid')
                <a class="btn" href="{{ tally_route('books.tally.payment-batches.export-neft', $batch) }}">Export NEFT/RTGS</a>
            @endif
        </x-slot:actions>
        <dl class="kv">
            <dt>Date</dt><dd>{{ $batch->batch_date->format('d M Y') }}</dd>
            <dt>Bank</dt><dd>{{ $batch->bankLedger?->name }}</dd>
            <dt>Total</dt><dd>{{ $batch->total_amount }}</dd>
            @if ($batch->approver)
                <dt>Approved by</dt><dd>{{ $batch->approver->name }}</dd>
            @endif
        </dl>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Supplier</th><th class="money">Amount</th><th class="money">TDS</th><th>Reference</th></tr></thead>
                <tbody>
                    @foreach ($batch->lines as $line)
                        <tr>
                            <td>{{ $line->supplier?->name }}</td>
                            <td class="money">{{ $line->amount }}</td>
                            <td class="money">{{ $line->tds_amount }}</td>
                            <td>{{ $line->reference }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-tally::shell.page>
</x-tally::layouts.app>
