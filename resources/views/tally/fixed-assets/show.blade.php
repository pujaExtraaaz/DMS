<x-tally::layouts.app :title="$asset->asset_code">
    <x-tally::shell.page :title="$asset->name" section="Fixed assets" :description="$asset->asset_code.' · '.ucfirst($asset->status)">
        <x-slot:actions>
            <a class="btn" href="{{ tally_route('books.tally.fixed-assets.index') }}">Back</a>
        </x-slot:actions>
        <dl class="kv">
            <dt>Category</dt><dd>{{ $asset->category?->name ?: '—' }}</dd>
            <dt>Acquired</dt><dd>{{ $asset->acquisition_date->format('d M Y') }}</dd>
            <dt>Capitalized</dt><dd>{{ $asset->capitalization_date?->format('d M Y') ?: '—' }}</dd>
            <dt>CIP</dt><dd>{{ $asset->is_cip ? 'Yes' : 'No' }}</dd>
            <dt>Parent</dt><dd>{{ $asset->parent?->asset_code ?: '—' }}</dd>
            <dt>Gross</dt><dd>{{ $asset->gross_value }}</dd>
            <dt>Residual</dt><dd>{{ $asset->residual_value }}</dd>
            <dt>Accum. depreciation</dt><dd>{{ $asset->accumulated_depreciation }}</dd>
            <dt>Book value</dt><dd>{{ $asset->book_value }}</dd>
            <dt>Location</dt><dd>{{ $asset->location ?: '—' }}</dd>
            <dt>Custodian</dt><dd>{{ $asset->custodian ?: '—' }}</dd>
            @if ($asset->isDisposed())
                <dt>Disposed</dt><dd>{{ $asset->disposal_date?->format('d M Y') }} · proceeds {{ $asset->disposal_proceeds }} · G/L {{ $asset->disposal_gain_loss }}</dd>
            @endif
        </dl>
        @if ($asset->is_cip && ! $asset->isDisposed())
            <form method="POST" class="panel form-grid" action="{{ tally_route('books.tally.fixed-assets.capitalize', $asset) }}" style="margin-top:1rem">
                @csrf
                <x-tally::form.field name="capitalization_date" label="Capitalization date" required>
                    <x-tally::form.input type="date" name="capitalization_date" value="{{ now()->toDateString() }}" required />
                </x-tally::form.field>
                <div class="form-actions"><button class="btn btn-primary" type="submit">Capitalize CIP</button></div>
            </form>
        @endif
        @if (! $asset->isDisposed())
            <form method="POST" class="panel form-grid" action="{{ tally_route('books.tally.fixed-assets.dispose', $asset) }}" style="margin-top:1rem">
                @csrf
                <x-tally::form.field name="disposal_date" label="Disposal date" required>
                    <x-tally::form.input type="date" name="disposal_date" value="{{ now()->toDateString() }}" required />
                </x-tally::form.field>
                <x-tally::form.field name="disposal_proceeds" label="Proceeds">
                    <x-tally::form.input name="disposal_proceeds" value="0" />
                </x-tally::form.field>
                <div class="form-actions"><button class="btn" type="submit">Dispose</button></div>
            </form>
        @endif
        @if ($asset->children->isNotEmpty())
            <h3 style="margin-top:1.5rem">Components</h3>
            <ul>
                @foreach ($asset->children as $child)
                    <li><a href="{{ tally_route('books.tally.fixed-assets.show', $child) }}">{{ $child->asset_code }} · {{ $child->name }}</a></li>
                @endforeach
            </ul>
        @endif
        @if ($asset->depreciationEntries->isNotEmpty())
            <h3 style="margin-top:1.5rem">Depreciation</h3>
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Period</th><th>Date</th><th class="money">Amount</th><th>Method</th></tr></thead>
                    <tbody>
                        @foreach ($asset->depreciationEntries as $entry)
                            <tr>
                                <td>{{ $entry->period }}</td>
                                <td>{{ $entry->entry_date->format('d M Y') }}</td>
                                <td class="money">{{ $entry->amount }}</td>
                                <td>{{ $entry->method }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-tally::shell.page>
</x-tally::layouts.app>
