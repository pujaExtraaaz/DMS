<x-tally::layouts.app :title="$category->exists ? 'Edit cost category' : 'New cost category'">
    <x-tally::shell.page :title="$category->exists ? 'Edit cost category' : 'New cost category'" section="Masters" :description="$company->name">
        <form class="panel" method="POST" action="{{ $category->exists ? tally_route('books.tally.cost-categories.update', $category) : tally_route('books.tally.cost-categories.store') }}">
            @csrf
            @if ($category->exists) @method('PUT') @endif
            <div class="form-grid">
                <x-tally::form.field name="name" label="Name" required><x-tally::form.input name="name" :value="old('name', $category->name)" required /></x-form.field>
                <x-tally::form.field name="code" label="Code"><x-tally::form.input name="code" :value="old('code', $category->code)" /></x-form.field>
                <x-tally::form.active :checked="$category->is_active ?? true" />
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Save</button>
                <a class="btn" href="{{ tally_route('books.tally.cost-categories.index') }}">Cancel</a>
            </div>
        </form>
    </x-shell.page>
</x-layouts.app>
