<x-tally::layouts.app title="ITC register">
    <x-tally::shell.page title="ITC register" section="Tax" :description="$company->name">
        <form method="POST" action="{{ tally_route('books.tally.tax.itc.store') }}" class="form-grid panel">
            @csrf
            <x-tally::form.field name="return_period" label="Return period"><x-tally::form.input name="return_period" value="{{ old('return_period', now()->format('Y-m')) }}" /></x-tally::form.field>
            <x-tally::form.field name="eligibility" label="Eligibility" required>
                <select class="input" name="eligibility" required>
                    @foreach (['eligible', 'ineligible', 'blocked', 'reversed'] as $bucket)
                        <option value="{{ $bucket }}" @selected(old('eligibility', 'eligible') === $bucket)>{{ ucfirst($bucket) }}</option>
                    @endforeach
                </select>
            </x-tally::form.field>
            <x-tally::form.field name="invoice_id" label="Invoice ID"><x-tally::form.input name="invoice_id" value="{{ old('invoice_id') }}" /></x-tally::form.field>
            <x-tally::form.field name="igst" label="IGST"><x-tally::form.input name="igst" value="{{ old('igst', '0') }}" /></x-tally::form.field>
            <x-tally::form.field name="cgst" label="CGST"><x-tally::form.input name="cgst" value="{{ old('cgst', '0') }}" /></x-tally::form.field>
            <x-tally::form.field name="sgst" label="SGST"><x-tally::form.input name="sgst" value="{{ old('sgst', '0') }}" /></x-tally::form.field>
            <x-tally::form.field name="cess" label="Cess"><x-tally::form.input name="cess" value="{{ old('cess', '0') }}" /></x-tally::form.field>
            <x-tally::form.field name="reason" label="Reason"><x-tally::form.input name="reason" value="{{ old('reason') }}" /></x-tally::form.field>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Record ITC</button></div>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Period</th><th>Eligibility</th><th>Invoice</th><th class="money">IGST</th><th class="money">CGST</th><th class="money">SGST</th><th>Reason</th></tr></thead>
                <tbody>
                    @forelse ($entries as $entry)
                        <tr>
                            <td>{{ $entry->return_period }}</td>
                            <td>{{ $entry->eligibility }}</td>
                            <td>{{ $entry->invoice?->invoice_number ?? $entry->invoice_id }}</td>
                            <td class="money">{{ $entry->igst }}</td>
                            <td class="money">{{ $entry->cgst }}</td>
                            <td class="money">{{ $entry->sgst }}</td>
                            <td>{{ $entry->reason }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7">No ITC entries yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $entries->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
