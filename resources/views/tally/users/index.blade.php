<x-tally::layouts.app title="Users">
    <x-tally::shell.page title="Users" section="Settings">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ tally_route('books.tally.users.create') }}">New user</a>
        </x-slot:actions>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Last login</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @foreach ($users as $account)
                        <tr>
                            <td><a href="{{ tally_route('books.tally.users.show', $account) }}">{{ $account->name }}</a>@if (tally_super_admin($account)) <span class="tag">Super Admin</span>@endif</td>
                            <td>{{ $account->email }}</td>
                            <td>{{ $account->role?->name ?: (tally_super_admin($account) ? 'Super Admin' : '—') }}</td>
                            <td>{{ $account->last_login_at?->format('d M Y H:i') ?: '—' }}</td>
                            <td>{{ $account->is_active ? 'Active' : 'Inactive' }}</td>
                            <td>
                                <a href="{{ tally_route('books.tally.users.edit', $account) }}">Edit</a>
                                @unless (tally_super_admin($account))
                                    <form method="POST" action="{{ tally_route('books.tally.users.destroy', $account) }}" onsubmit="return confirm('Delete this user?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit">Delete</button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $users->links() }}
    </x-shell.page>
</x-layouts.app>
