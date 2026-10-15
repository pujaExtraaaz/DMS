<x-tally::layouts.app title="GSTR-2B">
    <x-tally::shell.page title="GSTR-2B" section="Tax" :description="$company->name">
        <x-slot:actions>
            <form method="POST" action="{{ tally_route('books.tally.tax.gstr2b.match') }}">
                @csrf
                <input type="hidden" name="return_period" value="{{ $returnPeriod }}">
                <button class="btn" type="submit">Match books</button>
            </form>
        </x-slot:actions>
        <form method="GET" action="{{ tally_route('books.tally.tax.gstr2b') }}" class="filters">
            <x-tally::form.field name="return_period" label="Return period">
                <x-tally::form.input name="return_period" value="{{ $returnPeriod }}" placeholder="Yyyy-Mm" />
            </x-tally::form.field>
            <button class="btn" type="submit">Filter</button>
        </form>
        <form method="POST" action="{{ tally_route('books.tally.tax.gstr2b.import') }}" class="form-grid panel">
            @csrf
            <x-tally::form.field name="return_period" label="Import period" required>
                <x-tally::form.input name="return_period" value="{{ old('return_period', $returnPeriod) }}" required />
            </x-tally::form.field>
            <x-tally::form.field name="rows.0.supplier_gstin" label="Supplier GSTIN"><x-tally::form.input name="rows[0][supplier_gstin]" value="{{ old('rows.0.supplier_gstin') }}" /></x-tally::form.field>
            <x-tally::form.field name="rows.0.supplier_name" label="Supplier name"><x-tally::form.input name="rows[0][supplier_name]" value="{{ old('rows.0.supplier_name') }}" /></x-tally::form.field>
            <x-tally::form.field name="rows.0.invoice_number" label="Invoice no." required><x-tally::form.input name="rows[0][invoice_number]" value="{{ old('rows.0.invoice_number') }}" required /></x-tally::form.field>
            <x-tally::form.field name="rows.0.invoice_date" label="Invoice date"><x-tally::form.input type="date" name="rows[0][invoice_date]" value="{{ old('rows.0.invoice_date') }}" /></x-tally::form.field>
            <x-tally::form.field name="rows.0.taxable_value" label="Taxable"><x-tally::form.input name="rows[0][taxable_value]" value="{{ old('rows.0.taxable_value', '0') }}" /></x-tally::form.field>
            <x-tally::form.field name="rows.0.igst" label="IGST"><x-tally::form.input name="rows[0][igst]" value="{{ old('rows.0.igst', '0') }}" /></x-tally::form.field>
            <x-tally::form.field name="rows.0.cgst" label="CGST"><x-tally::form.input name="rows[0][cgst]" value="{{ old('rows.0.cgst', '0') }}" /></x-tally::form.field>
            <x-tally::form.field name="rows.0.sgst" label="SGST"><x-tally::form.input name="rows[0][sgst]" value="{{ old('rows.0.sgst', '0') }}" /></x-tally::form.field>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Import row</button></div>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>GSTIN</th><th>Supplier</th><th>Invoice</th><th>Date</th>
                        <th class="money">Taxable</th><th class="money">Tax</th><th>Status</th><th>Books</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>{{ $row->supplier_gstin }}</td>
                            <td>{{ $row->supplier_name }}</td>
                            <td>{{ $row->invoice_number }}</td>
                            <td>{{ $row->invoice_date?->format('d M Y') }}</td>
                            <td class="money">{{ $row->taxable_value }}</td>
                            <td class="money">{{ $row->totalTax() }}</td>
                            <td>{{ $row->match_status }}</td>
                            <td>{{ $row->matchedInvoice?->invoice_number ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8">No GSTR-2B rows for this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $rows->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
