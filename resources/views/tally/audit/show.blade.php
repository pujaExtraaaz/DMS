<x-tally::layouts.app title="Audit detail">
    <x-tally::shell.page :title="$entry->description ?: 'Audit detail'" section="Settings" :description="$entry->created_at->format('d M Y H:i')">
        <x-slot:actions>
            <a class="btn" href="{{ tally_route('books.tally.audit.index') }}">Back</a>
        </x-slot:actions>
        <dl class="sheet">
            <div><dt>User</dt><dd>{{ $entry->user->name ?? '—' }}</dd></div>
            <div><dt>Action</dt><dd>{{ str_replace('_', ' ', $entry->action) }}</dd></div>
            <div><dt>Module</dt><dd>{{ $entry->module }}</dd></div>
            <div><dt>Entity</dt><dd>{{ $entry->entityName() }}</dd></div>
            <div><dt>Company</dt><dd>{{ $entry->company->name ?? '—' }}</dd></div>
            <div><dt>Branch</dt><dd>{{ $entry->branch->name ?? '—' }}</dd></div>
            <div><dt>Financial year</dt><dd>{{ $entry->financialYear->name ?? '—' }}</dd></div>
            <div><dt>Source</dt><dd>{{ $entry->source }}</dd></div>
            <div><dt>IP</dt><dd>{{ $entry->ip ?: '—' }}</dd></div>
            <div><dt>Browser</dt><dd>{{ $entry->user_agent ?: '—' }}</dd></div>
        </dl>
        <h2>Previous and new values</h2>
        @if ($keys === [])
            <p>This event has no field comparison. Credentials are never stored here.</p>
        @else
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Field</th><th>Previous</th><th>New</th></tr></thead>
                    <tbody>
                        @foreach ($keys as $key)
                            <tr>
                                <td>{{ $key }}</td>
                                <td>{{ is_scalar($entry->previous_values[$key] ?? null) ? ($entry->previous_values[$key] ?? '—') : json_encode($entry->previous_values[$key] ?? null) }}</td>
                                <td>{{ is_scalar($entry->new_values[$key] ?? null) ? ($entry->new_values[$key] ?? '—') : json_encode($entry->new_values[$key] ?? null) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-shell.page>
</x-layouts.app>
