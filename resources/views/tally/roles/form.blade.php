<x-tally::layouts.app :title="$role->exists ? 'Edit role' : 'New role'">
    <x-tally::shell.page :title="$role->exists ? 'Edit role' : 'New role'" section="Settings">
        <form class="panel" method="POST" action="{{ $role->exists ? tally_route('books.tally.roles.update', $role) : tally_route('books.tally.roles.store') }}">
            @csrf
            @if ($role->exists) @method('PUT') @endif
            <x-tally::form.field name="name" label="Name" required><x-tally::form.input name="name" value="{{ old('name', $role->name) }}" required /></x-form.field>
            @foreach ($groups as $label => $permissions)
                <h2>{{ $label }}</h2>
                <div class="form-grid">
                    @foreach ($permissions as $permission)
                        <label class="check">
                            <input type="checkbox" name="permissions[]" value="{{ $permission }}" @checked(in_array($permission, old('permissions', $role->permissions ?? []), true))>
                            {{ $permission }}
                        </label>
                    @endforeach
                </div>
            @endforeach
            <button class="btn btn-primary" type="submit">Save</button>
        </form>
    </x-shell.page>
</x-layouts.app>
