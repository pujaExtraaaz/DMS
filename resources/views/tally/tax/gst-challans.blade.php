<x-tally::layouts.app title="GST challans">
    <x-tally::shell.page title="GST challans" section="Tax" :description="$company->name">
        <form method="POST" action="{{ tally_route('books.tally.tax.gst-challans.store') }}" class="form-grid panel">
            @csrf
            <x-tally::form.field name="return_period" label="Return period" required><x-tally::form.input name="return_period" value="{{ old('return_period', now()->format('Y-m')) }}" required /></x-tally::form.field>
            <x-tally::form.field name="challan_number" label="Challan no."><x-tally::form.input name="challan_number" value="{{ old('challan_number') }}" /></x-tally::form.field>
            <x-tally::form.field name="payment_date" label="Payment date" required><x-tally::form.input type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" required /></x-tally::form.field>
            <x-tally::form.field name="cgst" label="CGST"><x-tally::form.input name="cgst" value="{{ old('cgst', '0') }}" /></x-tally::form.field>
            <x-tally::form.field name="sgst" label="SGST"><x-tally::form.input name="sgst" value="{{ old('sgst', '0') }}" /></x-tally::form.field>
            <x-tally::form.field name="igst" label="IGST"><x-tally::form.input name="igst" value="{{ old('igst', '0') }}" /></x-tally::form.field>
            <x-tally::form.field name="cess" label="Cess"><x-tally::form.input name="cess" value="{{ old('cess', '0') }}" /></x-tally::form.field>
            <x-tally::form.field name="interest" label="Interest"><x-tally::form.input name="interest" value="{{ old('interest', '0') }}" /></x-tally::form.field>
            <x-tally::form.field name="penalty" label="Penalty"><x-tally::form.input name="penalty" value="{{ old('penalty', '0') }}" /></x-tally::form.field>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Save challan</button></div>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Period</th><th>Challan</th><th>Date</th><th class="money">Cgst</th><th class="money">Sgst</th><th class="money">Igst</th><th class="money">Total</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($challans as $challan)
                        <tr>
                            <td>{{ $challan->return_period }}</td>
                            <td>{{ $challan->challan_number }}</td>
                            <td>{{ $challan->payment_date?->format('d M Y') }}</td>
                            <td class="money">{{ $challan->cgst }}</td>
                            <td class="money">{{ $challan->sgst }}</td>
                            <td class="money">{{ $challan->igst }}</td>
                            <td class="money">{{ $challan->total_amount }}</td>
                            <td>{{ $challan->status }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8">No GST challans yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $challans->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
