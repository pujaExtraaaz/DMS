<x-tally::layouts.app :title="$centre->exists ? 'Edit cost centre' : 'New cost centre'">
    <x-tally::shell.page :title="$centre->exists ? 'Edit cost centre' : 'New cost centre'" section="Masters" :description="$company->name">
        <form class="panel" method="POST" action="{{ $centre->exists ? tally_route('books.tally.cost-centres.update', $centre) : tally_route('books.tally.cost-centres.store') }}">
            @csrf
            @if ($centre->exists) @method('PUT') @endif
            <div class="form-grid">
                <x-tally::form.field name="name" label="Name" required><x-tally::form.input name="name" :value="old('name', $centre->name)" required /></x-form.field>
                <x-tally::form.field name="code" label="Code"><x-tally::form.input name="code" :value="old('code', $centre->code)" /></x-form.field>
                <x-tally::form.field name="cost_category_id" label="Category">
                    <select id="cost_category_id" name="cost_category_id" class="input">
                        <option value="">None</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('cost_category_id', $centre->cost_category_id) === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </x-form.field>
                <x-tally::form.active :checked="$centre->is_active ?? true" />
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Save</button>
                <a class="btn" href="{{ tally_route('books.tally.cost-centres.index') }}">Cancel</a>
            </div>
        </form>
    </x-shell.page>
</x-layouts.app>
