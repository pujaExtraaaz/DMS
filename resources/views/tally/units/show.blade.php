<x-tally::layouts.app :title="$unit->name">
    <x-tally::shell.page :title="$unit->name" section="Masters" :description="$company->name">
        <x-slot:actions>
            <a class="btn" href="{{ tally_route('books.tally.units.edit', $unit) }}">Edit</a>
            @if ($unit->canBeDeleted())
                <form method="POST" action="{{ tally_route('books.tally.units.destroy', $unit) }}" onsubmit="return confirm('Delete this unit?');">
                    @csrf
                    @method('DELETE')
                    <button class="btn" type="submit">Delete</button>
                </form>
            @endif
        </x-slot:actions>
        <section class="panel">
            <dl class="kv">
                <dt>Symbol</dt><dd>{{ $unit->symbol }}</dd>
                <dt>Decimal places</dt><dd>{{ $unit->decimal_places }}</dd>
                <dt>Products</dt><dd>{{ $unit->primary_products_count + $unit->alternate_products_count }}</dd>
                <dt>Status</dt><dd><x-tally::ui.status :active="$unit->is_active" /></dd>
            </dl>
        </section>
    </x-shell.page>
</x-layouts.app>
