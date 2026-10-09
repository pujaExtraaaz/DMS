<x-tally::layouts.app :title="$title">
    <x-tally::shell.page :title="$title" section="Reports" :description="$company->name.' · '.$year->name">
        <x-slot:actions>
            <button class="btn" type="button" onclick="window.print()">Print</button>
        </x-slot:actions>
        <form class="filters" method="GET">
            <x-tally::form.field name="q" label="Search">
                <x-tally::form.input name="q" value="{{ $filters['q'] }}" placeholder="Product name or code" />
            </x-form.field>
            @unless ($kind === 'low')
                <x-tally::form.field name="from" label="From">
                    <x-tally::form.input name="from" type="date" value="{{ $filters['from'] }}" />
                </x-form.field>
            @endunless
            <x-tally::form.field name="to" label="{{ $kind === 'low' ? 'As on' : 'To' }}">
                <x-tally::form.input name="to" type="date" value="{{ $filters['to'] }}" />
            </x-form.field>
            <x-tally::form.field name="product_id" label="Product">
                <select id="product_id" name="product_id" class="input">
                    <option value="">All products</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" @selected((string) $filters['product_id'] === (string) $product->id)>{{ $product->name }}</option>
                    @endforeach
                </select>
            </x-form.field>
            @unless ($kind === 'low')
                <x-tally::form.field name="godown_id" label="Godown">
                    <select id="godown_id" name="godown_id" class="input">
                        <option value="">All godowns</option>
                        @foreach ($godowns as $godown)
                            <option value="{{ $godown->id }}" @selected((string) $filters['godown_id'] === (string) $godown->id)>{{ $godown->name }}</option>
                        @endforeach
                    </select>
                </x-form.field>
            @endunless
            <x-tally::form.field name="branch_id" label="Branch">
                <select id="branch_id" name="branch_id" class="input">
                    <option value="all" @selected($filters['branch_id'] === null)>All branches</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) $filters['branch_id'] === (string) $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </x-form.field>
            <button class="btn btn-primary" type="submit">Apply</button>
        </form>
        @if ($kind === 'summary')
            <p class="form-note">Opening quantity is the product opening plus posted movements before the start date. It is not stored in a godown. Cancelled stock is excluded.</p>
        @endif
        <div class="table-wrap">
            <table class="data">
                @if ($kind === 'summary')
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="qty">Opening</th>
                            <th class="qty">Stock in</th>
                            <th class="qty">Stock out</th>
                            <th class="qty">Closing</th>
                            <th class="money">Rate</th>
                            <th class="money">Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($report['rows'] as $row)
                            <tr>
                                <td><a href="{{ tally_route('books.tally.reports.stock-ledger', request()->except('page') + ['product_id' => $row['product_id']]) }}">{{ $row['product'] }}</a></td>
                                <td class="qty">{{ $row['opening_quantity'] }}</td>
                                <td class="qty">{{ $row['in_quantity'] }}</td>
                                <td class="qty">{{ $row['out_quantity'] }}</td>
                                <td class="qty">{{ $row['closing_quantity'] }}</td>
                                <td class="money">{{ $row['rate'] }}</td>
                                <td class="money">{{ $row['closing_value'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7">No stock in this period.</td></tr>
                        @endforelse
                    </tbody>
                    @if ($report['rows'] !== [])
                        <tfoot>
                            <tr>
                                <th>Total</th>
                                <th class="qty">{{ $report['opening_quantity'] }}</th>
                                <th class="qty">{{ $report['in_quantity'] }}</th>
                                <th class="qty">{{ $report['out_quantity'] }}</th>
                                <th class="qty">{{ $report['closing_quantity'] }}</th>
                                <th></th>
                                <th class="money">{{ $report['closing_value'] }}</th>
                            </tr>
                        </tfoot>
                    @endif
                @elseif ($kind === 'ledger')
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Number</th>
                            <th>Type</th>
                            <th>Godown</th>
                            <th class="qty">In</th>
                            <th class="qty">Out</th>
                            <th class="qty">Balance</th>
                            <th class="money">Rate</th>
                            <th class="money">Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($report['product'])
                            <tr>
                                <td colspan="6">Opening</td>
                                <td class="qty">{{ $report['opening_quantity'] }}</td>
                                <td colspan="2"></td>
                            </tr>
                            @foreach ($report['rows'] as $row)
                                <tr>
                                    <td>{{ $row['date'] }}</td>
                                    <td>{{ $row['number'] }}</td>
                                    <td>{{ $row['type'] }}</td>
                                    <td>{{ $row['godown'] }}</td>
                                    <td class="qty">{{ $row['in_quantity'] }}</td>
                                    <td class="qty">{{ $row['out_quantity'] }}</td>
                                    <td class="qty">{{ $row['balance'] }}</td>
                                    <td class="money">{{ $row['rate'] }}</td>
                                    <td class="money">{{ $row['value'] }}</td>
                                </tr>
                            @endforeach
                        @else
                            <tr><td colspan="9">Choose a product.</td></tr>
                        @endif
                    </tbody>
                    @if ($report['product'])
                        <tfoot>
                            <tr>
                                <th colspan="6">Closing</th>
                                <th class="qty">{{ $report['closing_quantity'] }}</th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    @endif
                @elseif ($kind === 'godowns')
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Godown</th>
                            <th class="qty">Opening</th>
                            <th class="qty">Stock in</th>
                            <th class="qty">Stock out</th>
                            <th class="qty">Closing</th>
                            <th class="money">Rate</th>
                            <th class="money">Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($report['rows'] as $row)
                            <tr>
                                <td>{{ $row['product'] }}</td>
                                <td>{{ $row['godown'] }}</td>
                                <td class="qty">{{ $row['opening_quantity'] }}</td>
                                <td class="qty">{{ $row['in_quantity'] }}</td>
                                <td class="qty">{{ $row['out_quantity'] }}</td>
                                <td class="qty">{{ $row['closing_quantity'] }}</td>
                                <td class="money">{{ $row['rate'] }}</td>
                                <td class="money">{{ $row['closing_value'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8">No godown stock in this period.</td></tr>
                        @endforelse
                    </tbody>
                @elseif ($kind === 'movements')
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Number</th>
                            <th>Product</th>
                            <th>Godown</th>
                            <th>Type</th>
                            <th class="qty">In</th>
                            <th class="qty">Out</th>
                            <th class="money">Rate</th>
                            <th class="money">Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($report['rows'] as $row)
                            <tr>
                                <td>{{ $row['date'] }}</td>
                                <td>{{ $row['number'] }}</td>
                                <td>{{ $row['product'] }}</td>
                                <td>{{ $row['godown'] }}</td>
                                <td>{{ $row['type'] }}</td>
                                <td class="qty">{{ $row['in_quantity'] }}</td>
                                <td class="qty">{{ $row['out_quantity'] }}</td>
                                <td class="money">{{ $row['rate'] }}</td>
                                <td class="money">{{ $row['value'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="9">No posted stock movements in this period.</td></tr>
                        @endforelse
                    </tbody>
                @else
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Code</th>
                            <th class="qty">Closing</th>
                            <th class="qty">Reorder level</th>
                            <th class="qty">Minimum</th>
                            <th class="qty">Shortfall</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($report['rows'] as $row)
                            <tr>
                                <td><a href="{{ tally_route('books.tally.reports.stock-ledger', request()->except('page') + ['product_id' => $row['product_id']]) }}">{{ $row['product'] }}</a></td>
                                <td>{{ $row['code'] ?: '—' }}</td>
                                <td class="qty">{{ $row['closing_quantity'] }}</td>
                                <td class="qty">{{ $row['reorder_level'] }}</td>
                                <td class="qty">{{ $row['minimum_stock'] }}</td>
                                <td class="qty">{{ $row['shortfall'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6">No product is at or below its reorder level.</td></tr>
                        @endforelse
                    </tbody>
                @endif
            </table>
        </div>
    </x-shell.page>
</x-layouts.app>
