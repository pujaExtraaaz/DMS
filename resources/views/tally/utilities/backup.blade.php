<x-tally::layouts.app title="Backup">
    <x-tally::shell.page title="Backup" section="Utilities" description="Save application data without replacing the Super Admin account.">
        <x-slot:actions>
            <form method="POST" action="{{ tally_route('books.tally.utilities.backup.store') }}">
                @csrf
                <button class="btn btn-primary" type="submit">Create backup</button>
            </form>
        </x-slot:actions>

        <p>A backup stores company, accounting, inventory, tax, and banking rows. The Super Admin login, sessions, and this backup history are not included in the file.</p>

        @if ($backups->isEmpty())
            <x-tally::ui.empty-state title="No backups" message="Create a backup before you need to restore." />
        @else
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>File</th>
                            <th>Status</th>
                            <th>Started</th>
                            <th>Completed</th>
                            <th>Size</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($backups as $backup)
                            <tr>
                                <td>{{ $backup->filename }}</td>
                                <td>{{ $backup->status->label() }}</td>
                                <td>{{ $backup->started_at?->format('d M Y H:i') }}</td>
                                <td>{{ $backup->completed_at?->format('d M Y H:i') ?? '—' }}</td>
                                <td>{{ $backup->size_bytes ? number_format($backup->size_bytes / 1024, 1).' KB' : '—' }}</td>
                                <td class="row-actions">
                                    @if ($backup->status->value === 'completed')
                                        <a href="{{ tally_route('books.tally.utilities.backup.download', $backup) }}">Download</a>
                                        <a href="{{ tally_route('books.tally.utilities.backup.restore', $backup) }}">Restore</a>
                                    @elseif ($backup->error_message)
                                        <span>{{ $backup->error_message }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <h2>Restore activity</h2>
        @if ($restores->isEmpty())
            <p>No restore has been run.</p>
        @else
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Backup</th>
                            <th>Status</th>
                            <th>Confirmation</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($restores as $restore)
                            <tr>
                                <td>{{ $restore->started_at?->format('d M Y H:i') }}</td>
                                <td>{{ $restore->backup?->filename ?? 'Removed backup' }}</td>
                                <td>{{ $restore->status->label() }}</td>
                                <td>{{ $restore->confirmation }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-shell.page>
</x-layouts.app>
