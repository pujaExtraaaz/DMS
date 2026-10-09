<x-tally::layouts.guest title="Set up Super Admin">
    <h1>Set up Super Admin</h1>
    <p>Create the owner account. This application has one Super Admin.</p>
    <form method="POST" action="{{ tally_route('books.tally.setup') }}">
        @csrf
        <x-tally::form.field name="name" label="Name" required>
            <x-tally::form.input name="name" value="{{ old('name') }}" required autofocus autocomplete="name" />
        </x-form.field>
        <x-tally::form.field name="email" label="Email" required>
            <x-tally::form.input name="email" type="email" value="{{ old('email') }}" required autocomplete="username" />
        </x-form.field>
        <x-tally::form.field name="password" label="Password" required>
            <x-tally::form.input name="password" type="password" required autocomplete="new-password" />
        </x-form.field>
        <x-tally::form.field name="password_confirmation" label="Confirm password" required>
            <x-tally::form.input name="password_confirmation" type="password" required autocomplete="new-password" />
        </x-form.field>
        <button class="btn btn-primary" type="submit">Create account</button>
    </form>
</x-layouts.guest>
