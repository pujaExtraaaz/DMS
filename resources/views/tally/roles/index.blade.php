<x-tally::layouts.app title="Roles">
    <x-tally::shell.page title="Roles and permissions" section="Settings">
        <x-slot:actions><a class="btn btn-primary" href="{{ tally_route('books.tally.roles.create') }}">New role</a></x-slot:actions>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Role</th><th>Permissions</th><th>Users</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($roles as $role)
                        <tr>
                            <td>{{ $role->name }}</td>
                            <td>{{ count($role->permissions ?? []) }}</td>
                            <td>{{ $role->users_count }}</td>
                            <td>{{ $role->is_active ? 'Active' : 'Inactive' }}</td>
                            <td>
                                <a href="{{ tally_route('books.tally.roles.edit', $role) }}">Edit</a>
                                <form method="POST" action="{{ tally_route('books.tally.roles.destroy', $role) }}" onsubmit="return confirm('Delete this role?');">@csrf @method('DELETE')
                                    <button class="btn" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No roles yet. The Super Admin already has full access.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $roles->links() }}
    </x-shell.page>
</x-layouts.app>
