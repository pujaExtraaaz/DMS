<x-tally::layouts.app title="Vendor retentions">
    <x-tally::shell.page title="Vendor retentions" section="Payables" :description="$company->name">
        <form method="POST" class="panel stack" action="{{ tally_route('books.tally.vendor-retentions.store') }}">
            @csrf
            <div class="form-grid">
                <x-tally::form.field name="supplier_ledger_id" label="Supplier" required>
                    <select class="input" name="supplier_ledger_id" required>
                        <option value="">Select</option>
                        @foreach ($suppliers as $ledger)
                            <option value="{{ $ledger->id }}" @selected(old('supplier_ledger_id') == $ledger->id)>{{ $ledger->name }}</option>
                        @endforeach
                    </select>
                </x-tally::form.field>
                <x-tally::form.field name="retention_ledger_id" label="Retention ledger" required>
                    <select class="input" name="retention_ledger_id" required>
                        <option value="">Select</option>
                        @foreach ($ledgers as $ledger)
                            <option value="{{ $ledger->id }}" @selected(old('retention_ledger_id', $company->retention_ledger_id) == $ledger->id)>{{ $ledger->name }}</option>
                        @endforeach
                    </select>
                </x-tally::form.field>
                <x-tally::form.field name="invoice_id" label="Invoice ID">
                    <x-tally::form.input name="invoice_id" value="{{ old('invoice_id') }}" />
                </x-tally::form.field>
                <x-tally::form.field name="retention_percent" label="Retention %">
                    <x-tally::form.input name="retention_percent" value="{{ old('retention_percent') }}" />
                </x-tally::form.field>
                <x-tally::form.field name="retention_amount" label="Amount" required>
                    <x-tally::form.input name="retention_amount" value="{{ old('retention_amount') }}" required />
                </x-tally::form.field>
                <x-tally::form.field name="retention_date" label="Hold date" required>
                    <x-tally::form.input type="date" name="retention_date" value="{{ old('retention_date', now()->toDateString()) }}" required />
                </x-tally::form.field>
                <x-tally::form.field name="release_due_date" label="Release due">
                    <x-tally::form.input type="date" name="release_due_date" value="{{ old('release_due_date') }}" />
                </x-tally::form.field>
                <x-tally::form.field name="kind" label="Kind">
                    <select class="input" name="kind">
                        <option value="retention">Retention</option>
                        <option value="security_deposit">Security deposit</option>
                    </select>
                </x-tally::form.field>
            </div>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Hold retention</button></div>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Supplier</th>
                        <th>Ledger</th>
                        <th class="money">Held</th>
                        <th class="money">Released</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($retentions as $row)
                        <tr>
                            <td>{{ $row->retention_date->format('d M Y') }}</td>
                            <td>{{ $row->supplier?->name }}</td>
                            <td>{{ $row->retentionLedger?->name ?? '—' }}</td>
                            <td class="money">{{ $row->retention_amount }}</td>
                            <td class="money">{{ $row->released_amount }}</td>
                            <td>{{ ucfirst($row->status) }}</td>
                            <td>
                                @if (! in_array($row->status, ['released', 'cancelled'], true))
                                    <form method="POST" action="{{ tally_route('books.tally.vendor-retentions.release', $row) }}" class="inline-form">
                                        @csrf
                                        <input class="input" type="text" name="amount" value="{{ number_format((float) $row->retention_amount - (float) $row->released_amount, 2, '.', '') }}" style="max-width:6rem" required>
                                        <select class="input" name="bank_ledger_id" style="max-width:10rem">
                                            <option value="">Journal → supplier</option>
                                            @foreach ($bankLedgers as $ledger)
                                                <option value="{{ $ledger->id }}">{{ $ledger->name }}</option>
                                            @endforeach
                                        </select>
                                        <button class="btn btn-sm" type="submit">Release</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">No vendor retentions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $retentions->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
