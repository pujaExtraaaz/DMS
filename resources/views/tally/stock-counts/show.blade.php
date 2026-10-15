<x-tally::layouts.app :title="$count->number">
    <x-tally::shell.page :title="$count->number" section="Inventory" :description="ucfirst($count->status)">
        <x-slot:actions>
            @if ($count->isDraft())
                <a class="btn" href="{{ tally_route('books.tally.stock-counts.edit', $count) }}">Edit</a>
            @endif
            <a class="btn" href="{{ tally_route('books.tally.stock-counts.index') }}">Back</a>
        </x-slot:actions>
        <dl class="kv">
            <dt>Date</dt><dd>{{ $count->count_date->format('d M Y') }}</dd>
            <dt>Godown</dt><dd>{{ $count->godown?->name ?: 'All' }}</dd>
            @if ($count->narration)
                <dt>Narration</dt><dd>{{ $count->narration }}</dd>
            @endif
        </dl>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Product</th><th class="money">System</th><th class="money">Counted</th><th class="money">Variance</th><th class="money">Unit Cost</th></tr></thead>
                <tbody>
                    @foreach ($count->lines as $line)
                        <tr>
                            <td>{{ $line->product?->name }}</td>
                            <td class="money">{{ $line->system_qty }}</td>
                            <td class="money">{{ $line->counted_qty }}</td>
                            <td class="money">{{ $line->variance_qty }}</td>
                            <td class="money">{{ $line->unit_cost }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-tally::shell.page>
</x-tally::layouts.app>
