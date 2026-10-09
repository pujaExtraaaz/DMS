<x-tally::layouts.app :title="$account->name">
    <x-tally::shell.page :title="$account->name" section="Settings" :description="$account->email">
        <x-slot:actions>
            <a class="btn" href="{{ tally_route('books.tally.users.edit', $account) }}">Edit</a>
        </x-slot:actions>
        <dl class="kv">
            <dt>Role</dt><dd>{{ $account->role?->name ?: (tally_super_admin($account) ? 'Super Admin' : '—') }}</dd>
            <dt>Status</dt><dd>{{ $account->is_active ? 'Active' : 'Inactive' }}</dd>
            <dt>Last login</dt><dd>{{ $account->last_login_at?->format('d M Y H:i') ?: '—' }}</dd>
            <dt>Companies</dt><dd>{{ tally_super_admin($account) || $account->companies->isEmpty() ? 'All companies' : $account->companies->pluck('name')->join(', ') }}</dd>
            <dt>Branches</dt><dd>{{ $account->branches->isEmpty() ? 'All branches' : $account->branches->pluck('name')->join(', ') }}</dd>
        </dl>
        <h2>Reset password</h2>
        <form class="panel" method="POST" action="{{ tally_route('books.tally.users.password', $account) }}">
            @csrf
            @method('PUT')
            <div class="form-grid">
                <x-tally::form.field name="password" label="New password"><x-tally::form.input name="password" type="password" required /></x-form.field>
                <x-tally::form.field name="password_confirmation" label="Confirm"><x-tally::form.input name="password_confirmation" type="password" required /></x-form.field>
            </div>
            <button class="btn" type="submit">Update password</button>
        </form>
    </x-shell.page>
</x-layouts.app>
