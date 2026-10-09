<x-tally::layouts.app :title="$category->name">
    <x-tally::shell.page :title="$category->name" section="Masters" :description="$company->name">
        <x-slot:actions>
            <a class="btn" href="{{ tally_route('books.tally.tax-categories.edit', $category) }}">Edit</a>
            <form method="POST" action="{{ tally_route('books.tally.tax-categories.destroy', $category) }}" onsubmit="return confirm('Delete this tax category?');">
                @csrf
                @method('DELETE')
                <button class="btn" type="submit">Delete</button>
            </form>
        </x-slot:actions>
        <section class="panel">
            <dl class="kv">
                <dt>Code</dt><dd>{{ $category->code ?: '—' }}</dd>
                <dt>Rates</dt><dd>{{ $category->rates_count }}</dd>
                <dt>Status</dt><dd><x-tally::ui.status :active="$category->is_active" /></dd>
            </dl>
        </section>
    </x-shell.page>
</x-layouts.app>
