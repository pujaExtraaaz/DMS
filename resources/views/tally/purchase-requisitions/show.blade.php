<x-tally::layouts.app :title="$requisition->number">
    <x-tally::shell.page :title="$requisition->number" section="Purchasing" :description="ucfirst($requisition->status)">
        <x-slot:actions>
            @if (! in_array($requisition->status, ['cancelled', 'rejected'], true) && $requisition->lines->isNotEmpty())
                <form method="POST" action="{{ tally_route('books.tally.purchase-requisitions.convert-rfq', $requisition) }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">Create RFQ</button>
                </form>
            @endif
            <a class="btn" href="{{ tally_route('books.tally.purchase-requisitions.index') }}">Back</a>
        </x-slot:actions>
        <dl class="kv">
            <dt>Date</dt><dd>{{ $requisition->requisition_date->format('d M Y') }}</dd>
            <dt>Priority</dt><dd>{{ ucfirst($requisition->priority) }}</dd>
            @if ($requisition->narration)
                <dt>Narration</dt><dd>{{ $requisition->narration }}</dd>
            @endif
        </dl>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Item</th><th class="money">Qty</th><th>Unit</th><th>Required by</th></tr></thead>
                <tbody>
                    @foreach ($requisition->lines as $line)
                        <tr>
                            <td>{{ $line->item_name }}</td>
                            <td class="money">{{ $line->quantity }}</td>
                            <td>{{ $line->unit }}</td>
                            <td>{{ $line->required_by?->format('d M Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-tally::shell.page>
</x-tally::layouts.app>
