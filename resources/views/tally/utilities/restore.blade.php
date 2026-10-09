<x-tally::layouts.app title="Restore backup">
    <x-tally::shell.page title="Restore backup" section="Utilities" :description="$backup->filename">
        <p class="flash is-error" role="alert">
            Restoring replaces company, accounting, inventory, tax, and banking data with this backup.
            The Super Admin account is kept. A new safety backup is saved first. This cannot be undone from this screen except by restoring that safety backup.
        </p>

        @if (! $inspection['valid'])
            <p class="flash is-error" role="alert">This backup failed validation and will not be restored.</p>
            <ul>
                @foreach ($inspection['errors'] as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
            <a class="btn" href="{{ tally_route('books.tally.utilities.backup') }}">Back to backups</a>
        @else
            <p>Backup time: {{ $inspection['created_at'] }}</p>
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>Table</th>
                            <th>Rows now</th>
                            <th>Rows in backup</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($inspection['current_counts'] as $table => $count)
                            <tr>
                                <td>{{ $table }}</td>
                                <td>{{ $count }}</td>
                                <td>{{ $inspection['backup_counts'][$table] ?? 0 }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <form class="panel" method="POST" action="{{ tally_route('books.tally.utilities.backup.restore.store', $backup) }}">
                @csrf
                <x-tally::form.field name="password" label="Super Admin password" required>
                    <input id="password" class="input" type="password" name="password" required autocomplete="current-password">
                </x-form.field>
                <x-tally::form.field name="confirmation" label="Type {{ $confirmation }} to continue" required>
                    <input id="confirmation" class="input" type="text" name="confirmation" required autocomplete="off">
                </x-form.field>
                <button class="btn btn-primary" type="submit">Restore this backup</button>
                <a class="btn" href="{{ tally_route('books.tally.utilities.backup') }}">Cancel</a>
            </form>
        @endif
    </x-shell.page>
</x-layouts.app>
