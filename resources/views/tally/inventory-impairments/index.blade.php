<x-tally::layouts.app title="Inventory impairment">
    <x-tally::shell.page title="Inventory impairment" section="Inventory" :description="$company->name">
        <form method="POST" action="{{ tally_route('books.tally.inventory-impairments.store') }}" class="form-grid panel">
            @csrf
            <x-tally::form.field name="entry_date" label="Date" required>
                <x-tally::form.input type="date" name="entry_date" value="{{ old('entry_date', now()->toDateString()) }}" required />
            </x-tally::form.field>
            <x-tally::form.field name="amount" label="Amount" required>
                <x-tally::form.input name="amount" value="{{ old('amount') }}" required />
            </x-tally::form.field>
            <x-tally::form.field name="product_id" label="Product">
                <select class="input" name="product_id">
                    <option value="">All / general</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </select>
            </x-tally::form.field>
            <x-tally::form.field name="expense_ledger_id" label="Impairment expense" required>
                <select class="input" name="expense_ledger_id" required>
                    <option value="">—</option>
                    @foreach ($ledgers as $ledger)
                        <option value="{{ $ledger->id }}">{{ $ledger->name }}</option>
                    @endforeach
                </select>
            </x-tally::form.field>
            <x-tally::form.field name="provision_ledger_id" label="Provision / stock credit" required>
                <select class="input" name="provision_ledger_id" required>
                    <option value="">—</option>
                    @foreach ($ledgers as $ledger)
                        <option value="{{ $ledger->id }}">{{ $ledger->name }}</option>
                    @endforeach
                </select>
            </x-tally::form.field>
            <x-tally::form.field name="narration" label="Narration">
                <x-tally::form.input name="narration" value="{{ old('narration') }}" />
            </x-tally::form.field>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Post impairment</button></div>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Date</th><th>Product</th><th class="money">Amount</th><th>Voucher</th></tr></thead>
                <tbody>
                    @forelse ($entries as $entry)
                        <tr>
                            <td>{{ $entry->entry_date->format('d M Y') }}</td>
                            <td>{{ $entry->product?->name ?? '—' }}</td>
                            <td class="money">{{ $entry->amount }}</td>
                            <td>
                                @if ($entry->voucher)
                                    <a href="{{ tally_route('books.tally.vouchers.show', $entry->voucher) }}">{{ $entry->voucher->voucher_number }}</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No impairment entries yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $entries->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
