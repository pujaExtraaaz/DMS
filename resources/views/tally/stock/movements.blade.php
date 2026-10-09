<x-tally::layouts.app title="Stock movements">
    <x-tally::shell.page title="Stock movements" section="Transactions" :description="$company->name.' · '.$year->name">
        <form class="filters" method="GET" action="{{ tally_route('books.tally.stock-movements.index') }}">
            <x-tally::form.field name="product_id" label="Product">
                <select id="product_id" name="product_id" class="input">
                    <option value="">All</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" @selected((string) ($filters['product_id'] ?? '') === (string) $product->id)>{{ $product->name }}</option>
                    @endforeach
                </select>
            </x-form.field>
            <x-tally::form.field name="godown_id" label="Godown">
                <select id="godown_id" name="godown_id" class="input">
                    <option value="">All</option>
                    @foreach ($godowns as $godown)
                        <option value="{{ $godown->id }}" @selected((string) ($filters['godown_id'] ?? '') === (string) $godown->id)>{{ $godown->name }}</option>
                    @endforeach
                </select>
            </x-form.field>
            <x-tally::form.field name="movement_type" label="Type">
                <select id="movement_type" name="movement_type" class="input">
                    <option value="">All</option>
                    @foreach ($types as $movementType)
                        <option value="{{ $movementType->value }}" @selected(($filters['movement_type'] ?? '') === $movementType->value)>{{ $movementType->label() }}</option>
                    @endforeach
                </select>
            </x-form.field>
            <x-tally::form.field name="from" label="From">
                <x-tally::form.input name="from" type="date" value="{{ $filters['from'] ?? '' }}" />
            </x-form.field>
            <x-tally::form.field name="to" label="To">
                <x-tally::form.input name="to" type="date" value="{{ $filters['to'] ?? '' }}" />
            </x-form.field>
            <button class="btn btn-primary" type="submit">Apply</button>
            <a class="btn" href="{{ tally_route('books.tally.stock-movements.index') }}">Reset</a>
        </form>
        @if ($balance !== null)
            <p class="form-note">On hand: {{ rtrim(rtrim($balance, '0'), '.') ?: '0' }}. A godown total is movements only. Without a godown, on hand also includes the product opening quantity.</p>
        @endif
        @if ($movements->isEmpty())
            <x-tally::ui.empty-state title="No stock movements" message="Post stock, or a sales or purchase invoice with a product and godown." />
        @else
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Product</th>
                            <th>Godown</th>
                            <th class="money">Quantity</th>
                            <th class="money">Rate</th>
                            <th class="money">Value</th>
                            <th>Reference</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($movements as $movement)
                            <tr>
                                <td>{{ $movement->movement_date->format('d M Y') }}</td>
                                <td>{{ $movement->movement_type->label() }}{{ $movement->is_reversal ? ' reversal' : '' }}</td>
                                <td>{{ $movement->product->name ?? '—' }}</td>
                                <td>{{ $movement->godown->name ?? '—' }}</td>
                                <td class="money">{{ $movement->quantity }}</td>
                                <td class="money">{{ $movement->rate }}</td>
                                <td class="money">{{ $movement->value }}</td>
                                <td>{{ $movement->referenceLabel() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $movements->links() }}
        @endif
    </x-shell.page>
</x-layouts.app>
