<x-tally::layouts.app title="Business units">
    <x-tally::shell.page title="Business units" section="Masters" :description="$company->name">
        <form method="POST" action="{{ tally_route('books.tally.business-units.store') }}" class="filters">
            @csrf
            <x-tally::form.field name="name" label="Name" size="md"><x-tally::form.input name="name" required /></x-tally::form.field>
            <x-tally::form.field name="code" label="Code" size="sm"><x-tally::form.input name="code" size="sm" /></x-tally::form.field>
            <button class="btn btn-primary" type="submit">Add</button>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Name</th><th>Code</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @foreach ($units as $unit)
                        <tr>
                            <td colspan="4">
                                <form method="POST" action="{{ tally_route('books.tally.business-units.update', $unit) }}" class="filters">
                                    @csrf @method('PUT')
                                    <input class="input" name="name" value="{{ $unit->name }}" required>
                                    <input class="input" name="code" value="{{ $unit->code }}">
                                    <label><input type="checkbox" name="is_active" value="1" @checked($unit->is_active)> Active</label>
                                    <button class="btn" type="submit">Save</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $units->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
