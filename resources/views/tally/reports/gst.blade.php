<x-tally::layouts.app title="GST summary">
    <x-tally::shell.page title="GST summary" section="Reports" :description="$company->name.' · '.$year->name">
        <form class="filters" method="GET">
            <x-tally::form.field name="q" label="Search"><x-tally::form.input name="q" value="{{ $filters['q'] }}" placeholder="Invoice Or Party" /></x-form.field>
            <x-tally::form.field name="from" label="From"><x-tally::form.input name="from" type="date" value="{{ $filters['from'] }}" /></x-form.field>
            <x-tally::form.field name="to" label="To"><x-tally::form.input name="to" type="date" value="{{ $filters['to'] }}" /></x-form.field>
            <x-tally::form.field name="branch_id" label="Branch">
                <select id="branch_id" name="branch_id" class="input">
                    <option value="all" @selected($filters['branch_id'] === null)>All branches</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) $filters['branch_id'] === (string) $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </x-form.field>
            <button class="btn btn-primary" type="submit">Apply</button>
        </form>
        <p class="form-note">Posted sales and purchase invoices only. Drafts and cancelled documents are left out. Net is output tax minus input tax.</p>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th></th><th class="money">Taxable</th><th class="money">Cgst</th><th class="money">Sgst</th><th class="money">Igst</th><th class="money">Cess</th><th class="money">Tax</th></tr></thead>
                <tbody>
                    <tr><td>Output GST</td>@foreach (['taxable','cgst','sgst','igst','cess','tax'] as $key)<td class="money">{{ $report['output'][$key] }}</td>@endforeach</tr>
                    <tr><td>Input GST</td>@foreach (['taxable','cgst','sgst','igst','cess','tax'] as $key)<td class="money">{{ $report['input'][$key] }}</td>@endforeach</tr>
                    <tr><th>Net Payable</th><td colspan="5"></td><th class="money">{{ $report['net'] }}</th></tr>
                </tbody>
            </table>
        </div>
        <h2>Tax-wise</h2>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Side</th><th>Rate</th><th class="money">Taxable</th><th class="money">Cgst</th><th class="money">Sgst</th><th class="money">Igst</th><th class="money">Cess</th><th class="money">Tax</th></tr></thead>
                <tbody>
                    @foreach (['output' => 'Sales', 'input' => 'Purchase'] as $side => $label)
                        @foreach ($report['by_rate'][$side] as $row)
                            <tr><td>{{ $label }}</td><td>{{ $row['name'] }}</td>@foreach (['taxable','cgst','sgst','igst','cess','tax'] as $key)<td class="money">{{ $row[$key] }}</td>@endforeach</tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
        <h2>HSN / SAC</h2>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th class="money">Sales Taxable</th>
                        <th class="money">Sales Cgst</th>
                        <th class="money">Sales Sgst</th>
                        <th class="money">Sales Igst</th>
                        <th class="money">Sales Cess</th>
                        <th class="money">Sales Tax</th>
                        <th class="money">Purchase Taxable</th>
                        <th class="money">Purchase Cgst</th>
                        <th class="money">Purchase Sgst</th>
                        <th class="money">Purchase Igst</th>
                        <th class="money">Purchase Cess</th>
                        <th class="money">Purchase Tax</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report['by_hsn'] as $row)
                        <tr>
                            <td>{{ $row['code'] }}</td>
                            @foreach (['output_taxable','output_cgst','output_sgst','output_igst','output_cess','output_tax','input_taxable','input_cgst','input_sgst','input_igst','input_cess','input_tax'] as $key)
                                <td class="money">{{ $row[$key] }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="13">No posted tax lines in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <h2>Transactions</h2>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Date</th><th>Document</th><th>Party</th><th class="money">Taxable</th><th class="money">Cgst</th><th class="money">Sgst</th><th class="money">Igst</th><th class="money">Cess</th><th class="money">Tax</th></tr></thead>
                <tbody>
                    @forelse ($report['transactions'] as $row)
                        <tr>
                            <td>{{ $row['invoice']->invoice_date->format('d M Y') }}</td>
                            <td><a href="{{ tally_route($row['kind']->routeName('show'), $row['invoice']) }}">{{ $row['invoice']->invoice_number }}</a></td>
                            <td>{{ $row['party'] }}</td>
                            @foreach (['taxable','cgst','sgst','igst','cess','tax'] as $key)<td class="money">{{ $row[$key] }}</td>@endforeach
                        </tr>
                    @empty
                        <tr><td colspan="9">No posted invoices in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $report['transactions']->links() }}
    </x-shell.page>
</x-layouts.app>
