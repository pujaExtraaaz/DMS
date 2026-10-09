<x-tally::layouts.app :title="$integration->exists ? 'Edit integration' : 'New integration'">
    <x-tally::shell.page :title="$integration->exists ? 'Edit integration' : 'New integration'" section="Settings" :description="$company->name">
        <form class="panel" method="POST" action="{{ $integration->exists ? tally_route('books.tally.integrations.update', $integration) : tally_route('books.tally.integrations.store') }}">
            @csrf
            @if ($integration->exists) @method('PUT') @endif
            <div class="form-grid">
                <x-tally::form.field name="name" label="Name" required><x-tally::form.input name="name" value="{{ old('name', $integration->name) }}" required /></x-form.field>
                <x-tally::form.field name="type" label="Type" required>
                    <select id="type" name="type" class="input">
                        @foreach (['webhook' => 'Webhook', 'api' => 'API', 'file' => 'File'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('type', $integration->type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </x-form.field>
                <x-tally::form.field name="direction" label="Direction" required>
                    <select id="direction" name="direction" class="input">
                        @foreach (['outbound' => 'Outbound', 'inbound' => 'Inbound', 'both' => 'Both'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('direction', $integration->direction) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </x-form.field>
                <x-tally::form.field name="external_system" label="External system"><x-tally::form.input name="external_system" value="{{ old('external_system', $integration->external_system) }}" /></x-form.field>
                <x-tally::form.field name="url" label="Webhook URL" required class="span-2"><x-tally::form.input name="url" value="{{ old('url', $integration->endpoint->url ?? '') }}" required /></x-form.field>
                <x-tally::form.field name="api_key" label="API key"><x-tally::form.input name="api_key" type="password" placeholder="{{ $integration->hasCredentials() ? 'Saved. Leave blank to keep it.' : '' }}" autocomplete="new-password" /></x-form.field>
            </div>
            <h2>Events</h2>
            <div class="form-grid">
                @foreach ($events as $event)
                    <label class="check"><input type="checkbox" name="events[]" value="{{ $event }}" @checked(in_array($event, old('events', $integration->endpoint->events ?? ['integration.sync']), true))> {{ $event }}</label>
                @endforeach
            </div>
            <p class="form-note">The API key is encrypted and is not written to the audit trail. The webhook secret is generated and stays on the existing webhook endpoint.</p>
            <button class="btn btn-primary" type="submit">Save</button>
        </form>
    </x-shell.page>
</x-layouts.app>
