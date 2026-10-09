<x-tally::layouts.app title="Keyboard Shortcuts">
    <x-tally::shell.page title="Keyboard Shortcuts" section="Settings" description="These shortcuts apply across the application. New screens register their actions in the same list.">
        <div class="filters">
            <x-tally::form.field name="shortcut-search" label="Search shortcuts">
                <input id="shortcut-search" class="input" type="search" placeholder="Name, key, or scope" autocomplete="off">
            </x-form.field>
        </div>

        @error('shortcuts')
            <p class="flash is-error" role="alert">{{ $message }}</p>
        @enderror

        <form id="shortcut-save" method="POST" action="{{ tally_route('books.tally.settings.shortcuts.update') }}">
            @csrf
            @method('PUT')
        </form>

        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Action</th>
                        <th>Scope</th>
                        <th>Current keys</th>
                        <th>Default</th>
                        <th>Enabled</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($shortcuts as $index => $shortcut)
                        <tr data-shortcut-row="{{ strtolower($shortcut['label'].' '.($shortcut['description'] ?? '').' '.$shortcut['keys'].' '.$shortcut['scope'].' '.$shortcut['group']) }}">
                            <td>
                                <strong>{{ $shortcut['label'] }}</strong>
                                @if (! empty($shortcut['description']))
                                    <div class="muted">{{ $shortcut['description'] }}</div>
                                @endif
                                <div class="muted">{{ $shortcut['group'] }}</div>
                            </td>
                            <td>{{ $shortcut['scope'] }}</td>
                            <td>
                                <input type="hidden" name="shortcuts[{{ $index }}][id]" value="{{ $shortcut['id'] }}" form="shortcut-save">
                                <input class="input key-input" name="shortcuts[{{ $index }}][keys]" value="{{ $shortcut['keys'] }}" data-key-capture form="shortcut-save" autocomplete="off" spellcheck="false" required>
                            </td>
                            <td><kbd>{{ $shortcut['default_keys'] }}</kbd></td>
                            <td>
                                <input type="hidden" name="shortcuts[{{ $index }}][enabled]" value="0" form="shortcut-save">
                                <input type="checkbox" name="shortcuts[{{ $index }}][enabled]" value="1" form="shortcut-save" @checked($shortcut['enabled']) aria-label="Enable {{ $shortcut['label'] }}">
                            </td>
                            <td>
                                <form method="POST" action="{{ tally_route('books.tally.settings.shortcuts.restore', $shortcut['id']) }}">
                                    @csrf
                                    <button class="btn" type="submit">Restore</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit" form="shortcut-save">Save shortcuts</button>
        </div>
        <form method="POST" action="{{ tally_route('books.tally.settings.shortcuts.restore-all') }}">
            @csrf
            <button class="btn" type="submit">Restore all defaults</button>
        </form>
    </x-shell.page>
</x-layouts.app>
