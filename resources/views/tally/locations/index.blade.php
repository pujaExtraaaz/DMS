<x-tally::layouts.app title="Locations">
    <x-tally::shell.page title="Locations" section="Masters" :description="$company->name">
        <x-slot:actions>
            <span class="muted">Branch / GST place masters</span>
        </x-slot:actions>

        <form method="POST" action="{{ tally_route('books.tally.locations.store') }}" class="filters">
            @csrf
            <x-tally::form.field name="name" label="Name" size="md"><x-tally::form.input name="name" required /></x-tally::form.field>
            <x-tally::form.field name="code" label="Code" size="sm"><x-tally::form.input name="code" size="sm" /></x-tally::form.field>
            <x-tally::form.field name="branch_id" label="Branch" size="md">
                <select class="input input--md" name="branch_id">
                    <option value="">Optional</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </x-tally::form.field>
            <x-tally::form.field name="city" label="City" size="sm"><x-tally::form.input name="city" size="sm" /></x-tally::form.field>
            <x-tally::form.field name="state" label="State" size="sm"><x-tally::form.input name="state" size="sm" /></x-tally::form.field>
            <button class="btn btn-primary" type="submit">Add</button>
        </form>

        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Branch</th>
                        <th>City</th>
                        <th>State</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($locations as $location)
                        <tr>
                            <td colspan="7">
                                <form method="POST" action="{{ tally_route('books.tally.locations.update', $location) }}" class="filters" style="border:0;box-shadow:none;padding:0.5rem 0">
                                    @csrf @method('PUT')
                                    <input class="input" name="name" value="{{ $location->name }}" required>
                                    <input class="input" name="code" value="{{ $location->code }}">
                                    <select class="input" name="branch_id">
                                        <option value="">—</option>
                                        @foreach ($branches as $branch)
                                            <option value="{{ $branch->id }}" @selected($location->branch_id === $branch->id)>{{ $branch->name }}</option>
                                        @endforeach
                                    </select>
                                    <input class="input" name="city" value="{{ $location->city }}">
                                    <input class="input" name="state" value="{{ $location->state }}">
                                    <label class="check"><input type="checkbox" name="is_active" value="1" @checked($location->is_active)> Active</label>
                                    <button class="btn" type="submit">Save</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">No locations yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $locations->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
