<x-tally::layouts.app title="New RFQ">
    <x-tally::shell.page title="New RFQ" section="Purchasing" :description="$company->name">
        <form method="POST" class="panel stack" action="{{ tally_route('books.tally.rfqs.store') }}">
            @csrf
            <div class="form-grid">
                <x-tally::form.field name="rfq_date" label="Date" required>
                    <x-tally::form.input type="date" name="rfq_date" value="{{ old('rfq_date', optional($rfq->rfq_date)->toDateString() ?? now()->toDateString()) }}" required />
                </x-tally::form.field>
                <x-tally::form.field name="due_date" label="Due date">
                    <x-tally::form.input type="date" name="due_date" value="{{ old('due_date', optional($rfq->due_date)->toDateString()) }}" />
                </x-tally::form.field>
                <x-tally::form.field name="narration" label="Narration" class="span-full">
                    <x-tally::form.input name="narration" value="{{ old('narration', $rfq->narration) }}" />
                </x-tally::form.field>
            </div>
            @php $lines = old('lines', [['item_name' => '', 'quantity' => '1', 'unit' => '']]); @endphp
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Item</th><th>Qty</th><th>Unit</th></tr></thead>
                    <tbody>
                        @foreach ($lines as $i => $line)
                            <tr>
                                <td><input class="input" name="lines[{{ $i }}][item_name]" value="{{ $line['item_name'] ?? '' }}" required></td>
                                <td><input class="input" name="lines[{{ $i }}][quantity]" value="{{ $line['quantity'] ?? '1' }}"></td>
                                <td><input class="input" name="lines[{{ $i }}][unit]" value="{{ $line['unit'] ?? '' }}"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Save</button>
                <a class="btn" href="{{ tally_route('books.tally.rfqs.index') }}">Cancel</a>
            </div>
        </form>
    </x-tally::shell.page>
</x-tally::layouts.app>
