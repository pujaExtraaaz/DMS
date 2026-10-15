<x-tally::layouts.app title="New purchase requisition">
    <x-tally::shell.page title="New purchase requisition" section="Purchasing" :description="$company->name">
        <form method="POST" class="panel stack" action="{{ tally_route('books.tally.purchase-requisitions.store') }}">
            @csrf
            <div class="form-grid">
                <x-tally::form.field name="requisition_date" label="Date" required>
                    <x-tally::form.input type="date" name="requisition_date" value="{{ old('requisition_date', optional($requisition->requisition_date)->toDateString() ?? now()->toDateString()) }}" required />
                </x-tally::form.field>
                <x-tally::form.field name="priority" label="Priority">
                    <select class="input" name="priority">
                        @foreach (['low','normal','high','urgent'] as $priority)
                            <option value="{{ $priority }}" @selected(old('priority', $requisition->priority ?? 'normal') === $priority)>{{ ucfirst($priority) }}</option>
                        @endforeach
                    </select>
                </x-tally::form.field>
                <x-tally::form.field name="narration" label="Narration" class="span-full">
                    <x-tally::form.input name="narration" value="{{ old('narration', $requisition->narration) }}" />
                </x-tally::form.field>
            </div>
            @php
                $lines = old('lines', [['item_name' => '', 'quantity' => '1', 'unit' => '', 'required_by' => '', 'notes' => '']]);
            @endphp
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Item</th><th>Qty</th><th>Unit</th><th>Required By</th><th>Notes</th></tr></thead>
                    <tbody>
                        @foreach ($lines as $i => $line)
                            <tr>
                                <td><input class="input" name="lines[{{ $i }}][item_name]" value="{{ $line['item_name'] ?? '' }}" required></td>
                                <td><input class="input" name="lines[{{ $i }}][quantity]" value="{{ $line['quantity'] ?? '1' }}"></td>
                                <td><input class="input" name="lines[{{ $i }}][unit]" value="{{ $line['unit'] ?? '' }}"></td>
                                <td><input class="input" type="date" name="lines[{{ $i }}][required_by]" value="{{ $line['required_by'] ?? '' }}"></td>
                                <td><input class="input" name="lines[{{ $i }}][notes]" value="{{ $line['notes'] ?? '' }}"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Save</button>
                <a class="btn" href="{{ tally_route('books.tally.purchase-requisitions.index') }}">Cancel</a>
            </div>
        </form>
    </x-tally::shell.page>
</x-tally::layouts.app>
