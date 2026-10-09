<x-tally::layouts.app :title="$rate->name">
    <x-tally::shell.page :title="$rate->name" section="Masters" :description="$company->name">
        <x-slot:actions>
            <a class="btn" href="{{ tally_route('books.tally.tax-rates.edit', $rate) }}">Edit</a>
            @if ($rate->canBeDeleted())
                <form method="POST" action="{{ tally_route('books.tally.tax-rates.destroy', $rate) }}" onsubmit="return confirm('Delete this tax rate?');">
                    @csrf
                    @method('DELETE')
                    <button class="btn" type="submit">Delete</button>
                </form>
            @endif
        </x-slot:actions>
        <section class="panel">
            <dl class="kv">
                <dt>Category</dt><dd>{{ $rate->category->name ?? '—' }}</dd>
                <dt>Code</dt><dd>{{ $rate->code ?: '—' }}</dd>
                <dt>CGST</dt><dd>{{ $rate->trimmed('cgst_rate') }}%</dd>
                <dt>SGST</dt><dd>{{ $rate->trimmed('sgst_rate') }}%</dd>
                <dt>IGST</dt><dd>{{ $rate->trimmed('igst_rate') }}%</dd>
                <dt>Cess</dt><dd>{{ $rate->trimmed('cess_rate') }}%</dd>
                <dt>Status</dt><dd><x-tally::ui.status :active="$rate->is_active" /></dd>
            </dl>
        </section>
    </x-shell.page>
</x-layouts.app>
