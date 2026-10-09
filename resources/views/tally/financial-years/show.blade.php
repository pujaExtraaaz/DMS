<x-tally::layouts.app :title="$financialYear->name">
    <x-tally::shell.page :title="$financialYear->name" section="Settings" :description="$company->name">
        <x-slot:actions>
            <a class="btn" href="{{ tally_route('books.tally.companies.financial-years.edit', [$company, $financialYear]) }}">Edit</a>
            <form method="POST" action="{{ tally_route('books.tally.companies.financial-years.destroy', [$company, $financialYear]) }}" onsubmit="return confirm('Delete this financial year?');">
                @csrf
                @method('DELETE')
                <button class="btn" type="submit">Delete</button>
            </form>
        </x-slot:actions>
        <section class="panel">
            <dl class="kv">
                <dt>Start</dt><dd>{{ $financialYear->start_date->format('d M Y') }}</dd>
                <dt>End</dt><dd>{{ $financialYear->end_date->format('d M Y') }}</dd>
                <dt>Status</dt><dd><x-tally::ui.status :active="$financialYear->is_active" /></dd>
            </dl>
        </section>
    </x-shell.page>
</x-layouts.app>
