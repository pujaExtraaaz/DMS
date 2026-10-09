<x-tally::layouts.app :title="$godown->name">
    <x-tally::shell.page :title="$godown->name" section="Masters" :description="$company->name">
        <x-slot:actions>
            <a class="btn" href="{{ tally_route('books.tally.godowns.edit', $godown) }}">Edit</a>
            <form method="POST" action="{{ tally_route('books.tally.godowns.destroy', $godown) }}" onsubmit="return confirm('Delete this godown?');">
                @csrf
                @method('DELETE')
                <button class="btn" type="submit">Delete</button>
            </form>
        </x-slot:actions>
        <section class="panel">
            <dl class="kv">
                <dt>Code</dt><dd>{{ $godown->code ?: '—' }}</dd>
                <dt>Address</dt><dd>{{ $godown->address ?: '—' }}</dd>
                <dt>Status</dt><dd><x-tally::ui.status :active="$godown->is_active" /></dd>
            </dl>
        </section>
    </x-shell.page>
</x-layouts.app>
