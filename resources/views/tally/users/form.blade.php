<x-tally::layouts.app :title="$user->exists ? 'Edit user' : 'New user'">
    <x-tally::shell.page :title="$user->exists ? 'Edit user' : 'New user'" section="Settings">
        <form class="panel" method="POST" action="{{ $user->exists ? tally_route('books.tally.users.update', $user) : tally_route('books.tally.users.store') }}">
            @csrf
            @if ($user->exists) @method('PUT') @endif
            <div class="form-grid">
                <x-tally::form.field name="name" label="Name" required><x-tally::form.input name="name" value="{{ old('name', $user->name) }}" required /></x-form.field>
                <x-tally::form.field name="email" label="Email" required><x-tally::form.input name="email" type="email" value="{{ old('email', $user->email) }}" required /></x-form.field>
                <x-tally::form.field name="password" label="{{ $user->exists ? 'New password' : 'Password' }}"><x-tally::form.input name="password" type="password" /></x-form.field>
                <x-tally::form.field name="password_confirmation" label="Confirm password"><x-tally::form.input name="password_confirmation" type="password" /></x-form.field>
                @unless ($user->exists && tally_super_admin($user))
                    <x-tally::form.field name="role_id" label="Role">
                        <select id="role_id" name="role_id" class="input">
                            <option value="">No role</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}" @selected((string) old('role_id', $user->role_id) === (string) $role->id)>{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </x-form.field>
                    <x-tally::form.field name="company_ids" label="Companies">
                        <select id="company_ids" name="company_ids[]" class="input" multiple>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}" @selected(in_array($company->id, old('company_ids', $user->exists ? $user->companies->pluck('id')->all() : []), true))>{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </x-form.field>
                    <label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active ?? true))> Active</label>
                @endunless
            </div>
            <p class="form-note">Leave companies blank to allow every company. The Super Admin stays active and is not limited by a role.</p>
            <button class="btn btn-primary" type="submit">Save</button>
        </form>
    </x-shell.page>
</x-layouts.app>
