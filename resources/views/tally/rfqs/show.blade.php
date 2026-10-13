<x-tally::layouts.app :title="$rfq->number">
    <x-tally::shell.page :title="$rfq->number" section="Purchasing" :description="ucfirst($rfq->status)">
        <x-slot:actions>
            @if ($rfq->vendorQuotations->contains(fn ($q) => $q->is_selected))
                <form method="POST" action="{{ tally_route('books.tally.rfqs.convert-po', $rfq) }}" class="inline">
                    @csrf
                    <button class="btn btn-primary" type="submit">Convert to PO</button>
                </form>
            @endif
            <a class="btn" href="{{ tally_route('books.tally.rfqs.index') }}">Back</a>
        </x-slot:actions>
        <dl class="kv">
            <dt>Date</dt><dd>{{ $rfq->rfq_date->format('d M Y') }}</dd>
            <dt>Due</dt><dd>{{ $rfq->due_date?->format('d M Y') }}</dd>
        </dl>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Item</th><th class="money">Qty</th><th>Unit</th></tr></thead>
                <tbody>
                    @foreach ($rfq->lines as $line)
                        <tr>
                            <td>{{ $line->item_name }}</td>
                            <td class="money">{{ $line->quantity }}</td>
                            <td>{{ $line->unit }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <h3>Vendor quotations</h3>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Supplier</th><th class="money">Total</th><th>Selected</th><th></th></tr></thead>
                <tbody>
                    @forelse ($rfq->vendorQuotations as $quote)
                        <tr>
                            <td>{{ $quote->supplier?->name }}</td>
                            <td class="money">{{ $quote->grand_total }}</td>
                            <td>{{ $quote->is_selected ? 'Yes' : 'No' }}</td>
                            <td>
                                @unless ($quote->is_selected)
                                    <form method="POST" action="{{ tally_route('books.tally.rfqs.select-vendor', [$rfq, $quote]) }}">
                                        @csrf
                                        <button class="btn" type="submit">Select</button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No vendor quotes yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <form method="POST" class="panel stack" action="{{ tally_route('books.tally.rfqs.vendor-quotes.store', $rfq) }}">
            @csrf
            <h3>Add vendor quote</h3>
            <div class="form-grid">
                <x-tally::form.field name="supplier_ledger_id" label="Supplier" required>
                    <select class="input" name="supplier_ledger_id" required>
                        <option value="">Select</option>
                        @foreach ($suppliers as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </x-tally::form.field>
                <x-tally::form.field name="quote_date" label="Quote date" required>
                    <x-tally::form.input type="date" name="quote_date" value="{{ now()->toDateString() }}" required />
                </x-tally::form.field>
            </div>
            @php $vLines = $rfq->lines->map(fn ($l) => ['item_name' => $l->item_name, 'quantity' => $l->quantity, 'rate' => '', 'tax_rate' => '0'])->all() ?: [['item_name'=>'','quantity'=>'1','rate'=>'','tax_rate'=>'0']]; @endphp
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Item</th><th>Qty</th><th>Rate</th><th>Tax %</th></tr></thead>
                    <tbody>
                        @foreach ($vLines as $i => $line)
                            <tr>
                                <td><input class="input" name="lines[{{ $i }}][item_name]" value="{{ $line['item_name'] }}" required></td>
                                <td><input class="input" name="lines[{{ $i }}][quantity]" value="{{ $line['quantity'] }}"></td>
                                <td><input class="input" name="lines[{{ $i }}][rate]" value="{{ $line['rate'] }}"></td>
                                <td><input class="input" name="lines[{{ $i }}][tax_rate]" value="{{ $line['tax_rate'] }}"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Save vendor quote</button></div>
        </form>
    </x-tally::shell.page>
</x-tally::layouts.app>
