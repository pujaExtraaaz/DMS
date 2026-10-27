<x-tally::layouts.app title="GSTR-9">
    <x-tally::shell.page title="GSTR-9" section="GST" :description="$company->name.' · '.$year->name">
        <p class="form-note">Annual summary of posted sales and purchase invoices for {{ $year->name }} ({{ $from }} to {{ $to }}). These are the books figures. Nothing here is filed on the GST portal.</p>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Part</th>
                        <th class="money">Taxable</th>
                        <th class="money">CGST</th>
                        <th class="money">SGST</th>
                        <th class="money">IGST</th>
                        <th class="money">Cess</th>
                        <th class="money">Tax</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Outward supplies</td>
                        <td class="money">{{ $report['output']['taxable'] }}</td>
                        <td class="money">{{ $report['output']['cgst'] }}</td>
                        <td class="money">{{ $report['output']['sgst'] }}</td>
                        <td class="money">{{ $report['output']['igst'] }}</td>
                        <td class="money">{{ $report['output']['cess'] }}</td>
                        <td class="money">{{ $report['output']['tax'] }}</td>
                    </tr>
                    <tr>
                        <td>Inward supplies</td>
                        <td class="money">{{ $report['input']['taxable'] }}</td>
                        <td class="money">{{ $report['input']['cgst'] }}</td>
                        <td class="money">{{ $report['input']['sgst'] }}</td>
                        <td class="money">{{ $report['input']['igst'] }}</td>
                        <td class="money">{{ $report['input']['cess'] }}</td>
                        <td class="money">{{ $report['input']['tax'] }}</td>
                    </tr>
                    <tr>
                        <td>Tax payable (outward minus inward)</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="money">{{ $report['net'] }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-shell.page>
</x-layouts.app>