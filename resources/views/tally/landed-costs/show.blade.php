<x-tally::layouts.app :title="$voucher->number">
    <x-tally::shell.page :title="$voucher->number" section="Purchasing" :description="ucfirst($voucher->status)">
        <x-slot:actions>
            @if ($voucher->status === 'draft')
                <form method="POST" action="{{ tally_route('books.tally.landed-costs.post', $voucher) }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">Post</button>
                </form>
            @endif
            <a class="btn" href="{{ tally_route('books.tally.landed-costs.index') }}">Back</a>
        </x-slot:actions>
        <dl class="kv">
            <dt>Date</dt><dd>{{ $voucher->voucher_date->format('d M Y') }}</dd>
            <dt>Freight</dt><dd>{{ $voucher->freight }}</dd>
            <dt>Duty</dt><dd>{{ $voucher->duty }}</dd>
            <dt>Other</dt><dd>{{ $voucher->other_charges }}</dd>
            <dt>Total</dt><dd>{{ $voucher->total_charges }}</dd>
            <dt>Basis</dt><dd>{{ ucfirst($voucher->allocation_basis) }}</dd>
            @if ($voucher->voucher)
                <dt>Journal</dt><dd>{{ $voucher->voucher->number }}</dd>
            @endif
        </dl>
        @if ($voucher->allocations->isNotEmpty())
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="money">Qty</th>
                            <th class="money">Base value</th>
                            <th class="money">Allocated</th>
                            <th class="money">Landed unit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($voucher->allocations as $row)
                            <tr>
                                <td>{{ $row->item_name ?: $row->product?->name }}</td>
                                <td class="money">{{ $row->quantity }}</td>
                                <td class="money">{{ $row->base_value }}</td>
                                <td class="money">{{ $row->allocated_charges }}</td>
                                <td class="money">{{ $row->landed_unit_cost }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-tally::shell.page>
</x-tally::layouts.app>
