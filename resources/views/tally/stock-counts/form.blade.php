<x-tally::layouts.app :title="$count->exists ? 'Edit stock count' : 'New stock count'">
    <x-tally::shell.page :title="$count->exists ? 'Edit stock count' : 'New stock count'" section="Inventory" :description="$company->name">
        <form method="POST" class="panel stack" action="{{ $count->exists ? tally_route('books.tally.stock-counts.update', $count) : tally_route('books.tally.stock-counts.store') }}">
            @csrf
            @if ($count->exists) @method('PUT') @endif
            <div class="form-grid">
                <x-tally::form.field name="count_date" label="Count date" required>
                    <x-tally::form.input type="date" name="count_date" value="{{ old('count_date', optional($count->count_date)->toDateString() ?? now()->toDateString()) }}" required />
                </x-tally::form.field>
                <x-tally::form.field name="godown_id" label="Godown">
                    <select class="input" name="godown_id">
                        <option value="">All godowns</option>
                        @foreach ($godowns as $godown)
                            <option value="{{ $godown->id }}" @selected((string) old('godown_id', $count->godown_id) === (string) $godown->id)>{{ $godown->name }}</option>
                        @endforeach
                    </select>
                </x-tally::form.field>
                <x-tally::form.field name="narration" label="Narration" class="span-full">
                    <x-tally::form.input name="narration" value="{{ old('narration', $count->narration) }}" />
                </x-tally::form.field>
            </div>
            @php
                $lines = old('lines', $count->lines?->map(fn ($l) => [
                    'product_id' => $l->product_id,
                    'counted_qty' => $l->counted_qty,
                    'unit_cost' => $l->unit_cost,
                ])->all() ?? [['product_id' => '', 'counted_qty' => '0', 'unit_cost' => '']]);
            @endphp
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Product</th><th>Counted Qty</th><th>Unit Cost</th></tr></thead>
                    <tbody>
                        @foreach ($lines as $i => $line)
                            <tr>
                                <td>
                                    <select class="input" name="lines[{{ $i }}][product_id]" required>
                                        <option value="">Select</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}" @selected((string) ($line['product_id'] ?? '') === (string) $product->id)>{{ $product->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input class="input" name="lines[{{ $i }}][counted_qty]" value="{{ $line['counted_qty'] ?? '0' }}" required></td>
                                <td><input class="input" name="lines[{{ $i }}][unit_cost]" value="{{ $line['unit_cost'] ?? '' }}" placeholder="Auto"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Save</button>
                <a class="btn" href="{{ tally_route('books.tally.stock-counts.index') }}">Cancel</a>
            </div>
        </form>
    </x-tally::shell.page>
</x-tally::layouts.app>
