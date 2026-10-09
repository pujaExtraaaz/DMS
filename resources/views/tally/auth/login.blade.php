<x-tally::layouts.guest title="Log in" scene="login">
    <h1>Log in</h1>
    <p>Super Admin access to the books.</p>
    @if (session('status'))
        <p class="flash" role="status">{{ session('status') }}</p>
    @endif
    <form method="POST" action="{{ tally_route('books.tally.login') }}">
        @csrf
        <x-tally::form.field name="email" label="Email" required>
            <x-tally::form.input name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" />
        </x-form.field>
        <x-tally::form.field name="password" label="Password" required>
            <x-tally::form.input name="password" type="password" required autocomplete="current-password" />
        </x-form.field>
        <label class="check">
            <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
            Remember me
        </label>
        <button class="btn btn-primary" type="submit">Log in</button>
        <p>superadmin@tally.com / password</p>
    </form>
</x-layouts.guest>
