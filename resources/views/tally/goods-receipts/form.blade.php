<x-tally::layouts.app title="New goods receipt">
    <x-tally::shell.page title="New goods receipt" section="Purchasing" :description="$company->name">
        <form method="POST" class="panel stack" action="{{ tally_route('books.tally.goods-receipts.store') }}">
            @csrf
            <div class="form-grid">
                <x-tally::form.field name="receipt_date" label="Date" required>
                    <x-tally::form.input type="date" name="receipt_date" value="{{ old('receipt_date', now()->toDateString()) }}" required />
                </x-tally::form.field>
                <x-tally::form.field name="purchase_order_id" label="Purchase order">
                    <select class="input" name="purchase_order_id">
                        <option value="">None</option>
                        @foreach ($purchaseOrders as $po)
                            <option value="{{ $po->id }}" @selected((string) old('purchase_order_id') === (string) $po->id)>{{ $po->number }}</option>
                        @endforeach
                    </select>
                </x-tally::form.field>
                <x-tally::form.field name="supplier_ledger_id" label="Supplier">
                    <select class="input" name="supplier_ledger_id">
                        <option value="">Select</option>
                        @foreach ($suppliers as $id => $name)
                            <option value="{{ $id }}" @selected((string) old('supplier_ledger_id') === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </x-tally::form.field>
                <x-tally::form.field name="narration" label="Narration" class="span-full">
                    <x-tally::form.input name="narration" value="{{ old('narration') }}" />
                </x-tally::form.field>
            </div>
            @php $lines = old('lines', [['item_name' => '', 'ordered_qty' => '0', 'received_qty' => '1', 'rate' => '0']]); @endphp
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Item</th><th>Ordered</th><th>Received</th><th>Rate</th></tr></thead>
                    <tbody>
                        @foreach ($lines as $i => $line)
                            <tr>
                                <td><input class="input" name="lines[{{ $i }}][item_name]" value="{{ $line['item_name'] ?? '' }}" required></td>
                                <td><input class="input" name="lines[{{ $i }}][ordered_qty]" value="{{ $line['ordered_qty'] ?? '0' }}"></td>
                                <td><input class="input" name="lines[{{ $i }}][received_qty]" value="{{ $line['received_qty'] ?? '1' }}"></td>
                                <td><input class="input" name="lines[{{ $i }}][rate]" value="{{ $line['rate'] ?? '0' }}"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Save</button>
                <a class="btn" href="{{ tally_route('books.tally.goods-receipts.index') }}">Cancel</a>
            </div>
        </form>
    </x-tally::shell.page>
</x-tally::layouts.app>
