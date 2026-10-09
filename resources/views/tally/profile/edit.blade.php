<x-tally::layouts.app title="Account details">
    <x-tally::shell.page title="Account details" section="Account" description="Name, email, and password can be changed.">
        <div class="stack">
            <section class="panel">
                <h2>Account</h2>
                <form class="panel" method="POST" action="{{ tally_route('books.tally.profile.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="form-grid">
                        <x-tally::form.field name="name" label="Name" required>
                            <x-tally::form.input name="name" value="{{ old('name', $user->name) }}" required autocomplete="name" />
                        </x-form.field>
                        <x-tally::form.field name="email" label="Email" required>
                            <x-tally::form.input name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="username" />
                        </x-form.field>
                    </div>
                    <div class="form-actions">
                        <button class="btn btn-primary" type="submit">Save profile</button>
                    </div>
                </form>
            </section>
            <section class="panel">
                <h2>Password</h2>
                <form class="panel" method="POST" action="{{ tally_route('books.tally.profile.password') }}">
                    @csrf
                    @method('PUT')
                    <div class="form-grid">
                        <x-tally::form.field name="current_password" label="Current password" required>
                            <x-tally::form.input name="current_password" type="password" required autocomplete="current-password" />
                        </x-form.field>
                        <x-tally::form.field name="password" label="New password" required>
                            <x-tally::form.input name="password" type="password" required autocomplete="new-password" />
                        </x-form.field>
                        <x-tally::form.field name="password_confirmation" label="Confirm new password" required>
                            <x-tally::form.input name="password_confirmation" type="password" required autocomplete="new-password" />
                        </x-form.field>
                    </div>
                    <div class="form-actions">
                        <button class="btn btn-primary" type="submit">Change password</button>
                    </div>
                </form>
            </section>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn" type="submit">Log out</button>
            </form>
        </div>
    </x-shell.page>
</x-layouts.app>
