<x-tally::layouts.app :title="$branch->name">
    <x-tally::shell.page :title="$branch->name" section="Settings" :description="$company->name">
        <x-slot:actions>
            <a class="btn" href="{{ tally_route('books.tally.companies.branches.edit', [$company, $branch]) }}">Edit</a>
            <form method="POST" action="{{ tally_route('books.tally.companies.branches.destroy', [$company, $branch]) }}" onsubmit="return confirm('Delete this branch?');">
                @csrf
                @method('DELETE')
                <button class="btn" type="submit">Delete</button>
            </form>
        </x-slot:actions>
        <section class="panel">
            <dl class="kv">
                <dt>Code</dt><dd>{{ $branch->code }}</dd>
                <dt>Address</dt><dd>{{ $branch->address ?: '—' }}</dd>
                <dt>City</dt><dd>{{ $branch->city ?: '—' }}</dd>
                <dt>State</dt><dd>{{ $branch->state ?: '—' }}</dd>
                <dt>Country</dt><dd>{{ $branch->country }}</dd>
                <dt>Pincode</dt><dd>{{ $branch->pincode ?: '—' }}</dd>
                <dt>Phone</dt><dd>{{ $branch->phone ?: '—' }}</dd>
                <dt>Email</dt><dd>{{ $branch->email ?: '—' }}</dd>
                <dt>Status</dt><dd><x-tally::ui.status :active="$branch->is_active" /></dd>
            </dl>
        </section>
    </x-shell.page>
</x-layouts.app>
