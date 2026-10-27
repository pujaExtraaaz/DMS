<x-tally::layouts.app title="IMS">
    <x-tally::shell.page title="IMS" section="GST" :description="$company->name">
        @if ($errors->any())
            <p class="flash is-error" role="alert">{{ $errors->first() }}</p>
        @endif
        <p class="form-note">Accept or reject posted purchase invoices in these books. This list is not the GST portal inbox, and it does not file a return.</p>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Invoice</th>
                        <th>Supplier</th>
                        <th class="money">Total</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $invoice)
                        <tr>
                            <td>{{ $invoice->invoice_date->format('d M Y') }}</td>
                            <td><a href="{{ tally_route('books.tally.invoices.purchase.show', $invoice) }}">{{ $invoice->invoice_number }}</a></td>
                            <td>{{ $invoice->party->name ?? '—' }}</td>
                            <td class="money">{{ $invoice->grand_total }}</td>
                            <td>{{ ucfirst($invoice->ims_status ?: 'pending') }}</td>
                            <td>
                                <form method="POST" action="{{ tally_route('books.tally.invoices.purchase.ims', $invoice) }}">
                                    @csrf
                                    <select class="input" name="ims_status">
                                        @foreach (['pending' => 'Pending', 'accepted' => 'Accepted', 'rejected' => 'Rejected'] as $value => $label)
                                            <option value="{{ $value }}" @selected(($invoice->ims_status ?: 'pending') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn" type="submit">Save</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No posted purchase invoices.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $invoices->links() }}
    </x-shell.page>
</x-layouts.app>