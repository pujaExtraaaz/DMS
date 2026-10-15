<x-tally::layouts.app title="MSME payables">
    <x-tally::shell.page title="MSME payables" section="Reports" :description="$company->name">
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Party</th><th>Msme Type</th><th>Registration</th><th>Bill</th><th>Due</th><th class="money">Amount</th></tr></thead>
                <tbody>
                    @forelse ($bills as $bill)
                        @php $party = $parties->firstWhere('ledger_id', $bill->ledger_id); @endphp
                        <tr>
                            <td>{{ $bill->ledger?->name }}</td>
                            <td>{{ $party?->msme_type }}</td>
                            <td>{{ $party?->msme_registration_number }}</td>
                            <td>{{ $bill->bill_number }}</td>
                            <td>{{ $bill->due_date?->format('d M Y') }}</td>
                            <td class="money">{{ $bill->original_amount }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No MSME payables found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-tally::shell.page>
</x-tally::layouts.app>
