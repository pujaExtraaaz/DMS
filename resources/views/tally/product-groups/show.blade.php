<x-tally::layouts.app :title="$group->name">
    <x-tally::shell.page :title="$group->name" section="Masters" :description="$company->name">
        <x-slot:actions>
            <a class="btn" href="{{ tally_route('books.tally.product-groups.edit', $group) }}">Edit</a>
            @if ($group->canBeDeleted())
                <form method="POST" action="{{ tally_route('books.tally.product-groups.destroy', $group) }}" onsubmit="return confirm('Delete this product group?');">
                    @csrf
                    @method('DELETE')
                    <button class="btn" type="submit">Delete</button>
                </form>
            @endif
        </x-slot:actions>
        <div class="sheet-grid">
            <section class="panel">
                <h2>Group</h2>
                <dl class="kv">
                    <dt>Code</dt><dd>{{ $group->code ?: '—' }}</dd>
                    <dt>Parent</dt><dd>{{ $group->parent->name ?? 'Primary group' }}</dd>
                    <dt>Status</dt><dd><x-tally::ui.status :active="$group->is_active" /></dd>
                </dl>
            </section>
            <div class="stack">
                <section class="panel">
                    <h2>Subgroups</h2>
                    @forelse ($group->children as $child)
                        <p><a href="{{ tally_route('books.tally.product-groups.show', $child) }}">{{ $child->name }}</a></p>
                    @empty
                        <p class="muted">No subgroups.</p>
                    @endforelse
                </section>
                <section class="panel">
                    <h2>Products</h2>
                    @forelse ($group->products as $product)
                        <p><a href="{{ tally_route('books.tally.products.show', $product) }}">{{ $product->name }}</a></p>
                    @empty
                        <p class="muted">No products in this group.</p>
                    @endforelse
                </section>
            </div>
        </div>
    </x-shell.page>
</x-layouts.app>
