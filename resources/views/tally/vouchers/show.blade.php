<x-tally::layouts.app :title="$voucher->voucher_number">
    <x-tally::shell.page :title="$voucher->voucher_number" section="Transactions" :description="$voucher->voucher_type->label().' · '.$voucher->voucher_date->format('d M Y')">
        <x-slot:actions>
            @unless ($voucher->invoice || in_array($voucher->voucher_type, [\Tally\Accounting\VoucherType::Sales, \Tally\Accounting\VoucherType::Purchase], true))
                @if ($voucher->isDraft())
                    <a class="btn" href="{{ tally_route('books.tally.vouchers.edit', $voucher) }}">Edit</a>
                    <form method="POST" action="{{ tally_route('books.tally.vouchers.post', $voucher) }}">
                        @csrf
                        <button class="btn btn-primary" type="submit">Post voucher</button>
                    </form>
                    <form method="POST" action="{{ tally_route('books.tally.vouchers.destroy', $voucher) }}" onsubmit="return confirm('Delete this draft?');">
                        @csrf
                        @method('DELETE')
                        <button class="btn" type="submit">Delete draft</button>
                    </form>
                @elseif ($voucher->isPosted())
                    <a class="btn" href="{{ tally_route('books.tally.vouchers.entry', $voucher) }}">Alter</a>
                    <form method="POST" action="{{ tally_route('books.tally.vouchers.cancel', $voucher) }}" onsubmit="return confirm('Cancel this voucher? It will stay in the books as cancelled.');">
                        @csrf
                        <button class="btn" type="submit">Cancel voucher</button>
                    </form>
                @endif
            @endunless
            <a class="btn" href="{{ tally_route('books.tally.vouchers.print', $voucher) }}" target="_blank">Print</a>
            <a class="btn" href="{{ tally_route('books.tally.vouchers.pdf', $voucher) }}">Download PDF</a>
            @if ($previousVoucher)
                <a class="btn" rel="prev" href="{{ tally_route('books.tally.vouchers.show', $previousVoucher) }}">Previous</a>
            @endif
            @if ($nextVoucher)
                <a class="btn" rel="next" href="{{ tally_route('books.tally.vouchers.show', $nextVoucher) }}">Next</a>
            @endif
        </x-slot:actions>

        <div class="form-grid">
            <p><span class="muted">Status</span><br><span @class(['tag', 'is-bad' => $voucher->isCancelled()])>{{ $voucher->status->label() }}</span></p>
            <p><span class="muted">Reference</span><br>{{ $voucher->reference_number ?: '—' }}</p>
            <p class="span-2"><span class="muted">Narration</span><br>{{ $voucher->narration ?: '—' }}</p>
        </div>
        @if ($voucher->invoice)
            <p class="form-note"><a href="{{ tally_route($voucher->invoice->kind->routeName('show'), $voucher->invoice) }}">Open {{ strtolower($voucher->invoice->kind->documentLabel()) }}</a></p>
        @endif

        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Ledger</th>
                        <th class="money">Debit</th>
                        <th class="money">Credit</th>
                        <th>Narration</th>
                        <th>Reference</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($voucher->entries as $entry)
                        <tr>
                            <td>{{ $entry->ledger->name }}</td>
                            <td class="money">{{ $entry->debit }}</td>
                            <td class="money">{{ $entry->credit }}</td>
                            <td>{{ $entry->narration ?: '—' }}</td>
                            <td>{{ $entry->reference ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th>Total</th>
                        <th class="money">{{ $voucher->total_debit }}</th>
                        <th class="money">{{ $voucher->total_credit }}</th>
                        <th colspan="2"></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <dl class="audit">
            <div><dt>Created by</dt><dd>{{ $voucher->creator->name ?? '—' }}</dd></div>
            <div><dt>Created</dt><dd>{{ $voucher->created_at?->format('d M Y H:i') }}</dd></div>
            <div><dt>Posted</dt><dd>{{ $voucher->posted_at?->format('d M Y H:i') ?? '—' }}</dd></div>
            <div><dt>Cancelled</dt><dd>{{ $voucher->cancelled_at?->format('d M Y H:i') ?? '—' }}</dd></div>
        </dl>
    </x-shell.page>
</x-layouts.app>
