<x-tally::layouts.app :title="$group->name">
    <x-tally::shell.page :title="$group->name" section="Masters" :description="$company->name">
        <x-slot:actions>
            <a class="btn" href="{{ tally_route('books.tally.account-groups.edit', $group) }}">Edit</a>
            @if ($group->canBeDeleted())
                <form method="POST" action="{{ tally_route('books.tally.account-groups.destroy', $group) }}" onsubmit="return confirm('Delete this group?');">
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
                    <dt>Nature</dt><dd>{{ $group->nature->label() }}</dd>
                    <dt>Type</dt><dd>{{ $group->is_system ? 'System' : 'User' }}</dd>
                    <dt>Status</dt><dd><x-tally::ui.status :active="$group->is_active" /></dd>
                </dl>
            </section>
            <div class="stack">
                <section class="panel">
                    <h2>Subgroups</h2>
                    @forelse ($group->children as $child)
                        <p><a href="{{ tally_route('books.tally.account-groups.show', $child) }}">{{ $child->name }}</a></p>
                    @empty
                        <p class="muted">No subgroups.</p>
                    @endforelse
                </section>
                <section class="panel">
                    <h2>Ledgers</h2>
                    @forelse ($group->ledgers as $ledger)
                        <p><a href="{{ tally_route('books.tally.ledgers.show', $ledger) }}">{{ $ledger->name }}</a> <span class="muted">{{ $ledger->openingBalanceLabel() }}</span></p>
                    @empty
                        <p class="muted">No ledgers in this group.</p>
                    @endforelse
                </section>
            </div>
        </div>
    </x-shell.page>
</x-layouts.app>
