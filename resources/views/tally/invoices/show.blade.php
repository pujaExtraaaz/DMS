<x-tally::layouts.app :title="$invoice->invoice_number">
    <x-tally::shell.page :title="$invoice->invoice_number" section="Transactions" :description="$kind->documentLabel().' · '.$invoice->invoice_date->format('d M Y')">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ tally_route($kind->routeName('print'), $invoice) }}" target="_blank">Print</a>
            <a class="btn" href="{{ tally_route($kind->routeName('pdf'), $invoice) }}">Download PDF</a>
            <a class="btn" href="{{ tally_route($kind->routeName('einvoice'), $invoice) }}">E-invoice JSON</a>
            <a class="btn" href="{{ tally_route($kind->routeName('eway'), $invoice) }}">E-way bill JSON</a>
            @if ($invoice->isDraft())
                <a class="btn" href="{{ tally_route($kind->routeName('edit'), $invoice) }}">Edit</a>
                <form method="POST" action="{{ tally_route($kind->routeName('post'), $invoice) }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">Post</button>
                </form>
            @elseif ($invoice->isPosted())
                <form method="POST" action="{{ tally_route($kind->routeName('cancel'), $invoice) }}" onsubmit="return confirm('Cancel this invoice? The accounting entry stays in the books as cancelled.');">
                    @csrf
                    <button class="btn" type="submit">Cancel</button>
                </form>
            @endif
            <a class="btn" href="{{ tally_route($kind->routeName('index')) }}">Back</a>
        </x-slot:actions>

        <p class="form-note">E-invoice JSON and e-way bill JSON are the upload files for this invoice. This screen does not issue an IRN or an e-way bill number.</p>
        @if ($errors->any())
            <p class="flash is-error" role="alert">{{ $errors->first() }}</p>
        @endif

        <div class="form-grid">
            <p><span class="muted">Status</span><br><span @class(['tag', 'is-bad' => $invoice->isCancelled()])>{{ $invoice->status->label() }}</span></p>
            <p><span class="muted">{{ $kind->partyLabel() }}</span><br>{{ $invoice->party->name ?? '—' }}</p>
            <p><span class="muted">{{ $kind->accountLabel() }}</span><br>{{ $invoice->account->name ?? '—' }}</p>
            <p><span class="muted">{{ $kind->referenceLabel() }}</span><br>{{ $invoice->reference_number ?: '—' }}</p>
            <p class="span-2"><span class="muted">Narration</span><br>{{ $invoice->narration ?: '—' }}</p>
            @if ($invoice->supply_type)
                <p><span class="muted">Supply</span><br>{{ $invoice->supply_type->label() }}</p>
                <p><span class="muted">Place of supply</span><br>{{ $invoice->place_of_supply ?: '—' }}</p>
            @endif
        </div>

        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="money">Quantity</th>
                        <th class="money">Rate</th>
                        <th class="money">Discount</th>
                        <th class="money">Tax</th>
                        <th class="money">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoice->lines as $line)
                        <tr>
                            <td>
                                {{ $line->item_name }}
                                @if ($line->product)<span class="muted"> · {{ $line->product->code }}</span>@endif
                                @if ($line->godown)<span class="muted"> · {{ $line->godown->name }}</span>@endif
                            </td>
                            <td class="money">{{ rtrim(rtrim($line->quantity, '0'), '.') }}</td>
                            <td class="money">{{ $line->rate }}</td>
                            <td class="money">{{ $line->discount }}</td>
                            <td class="money">{{ $line->tax_amount }}</td>
                            <td class="money">{{ $line->line_total }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <x-tally::invoice.totals :invoice="$invoice" />
        <p class="form-note">A tax rate posts CGST and SGST, or IGST, to the mapped tax ledgers. Tax entered without a rate stays inside the sales or purchase amount. Stock moves only for lines that have a product and a godown.</p>

        @if ($invoice->voucher)
            <p class="kicker">Accounting</p>
            <p class="form-note">
                Voucher {{ $invoice->voucher->voucher_number }} · {{ $invoice->voucher->status->label() }}
                · {{ $kind->postingNote() }}
            </p>
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>Ledger</th>
                            <th class="money">Debit</th>
                            <th class="money">Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoice->voucher->entries as $entry)
                            <tr>
                                <td>{{ $entry->ledger->name }}</td>
                                <td class="money">{{ $entry->debit }}</td>
                                <td class="money">{{ $entry->credit }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @elseif ($invoice->isDraft())
            <p class="form-note">
                @if ($kind === \Tally\Invoicing\InvoiceKind::Sales)
                    Posting debits the customer and credits the sales ledger for the grand total.
                @else
                    Posting debits the purchase ledger and credits the supplier for the grand total.
                @endif
                No accounting voucher is written until you post.
            </p>
        @endif

        <dl class="audit">
            <div><dt>Created by</dt><dd>{{ $invoice->creator->name ?? '—' }}</dd></div>
            <div><dt>Created</dt><dd>{{ $invoice->created_at?->format('d M Y H:i') }}</dd></div>
            <div><dt>Posted</dt><dd>{{ $invoice->posted_at?->format('d M Y H:i') ?? '—' }}</dd></div>
            <div><dt>Cancelled</dt><dd>{{ $invoice->cancelled_at?->format('d M Y H:i') ?? '—' }}</dd></div>
        </dl>
    </x-shell.page>
</x-layouts.app>
