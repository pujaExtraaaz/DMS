<x-tally::layouts.app :title="$record->code">
    <x-tally::shell.page :title="$record->label()" section="Masters" :description="$company->name">
        <x-slot:actions>
            <a class="btn" href="{{ tally_route('books.tally.hsn-sacs.edit', $record) }}">Edit</a>
            @if ($record->canBeDeleted())
                <form method="POST" action="{{ tally_route('books.tally.hsn-sacs.destroy', $record) }}" onsubmit="return confirm('Delete this HSN/SAC?');">
                    @csrf
                    @method('DELETE')
                    <button class="btn" type="submit">Delete</button>
                </form>
            @endif
        </x-slot:actions>
        <section class="panel">
            <dl class="kv">
                <dt>Kind</dt><dd>{{ $record->kind->label() }}</dd>
                <dt>Description</dt><dd>{{ $record->description ?: '—' }}</dd>
                <dt>Tax rate</dt><dd>{{ $record->taxRate->name ?? '—' }}</dd>
                <dt>Status</dt><dd><x-tally::ui.status :active="$record->is_active" /></dd>
            </dl>
        </section>
    </x-shell.page>
</x-layouts.app>
