<x-tally::layouts.app title="Close checklists">
    <x-tally::shell.page title="Close checklists" section="Close" :description="$company->name">
        <form method="POST" action="{{ tally_route('books.tally.close-checklists.store') }}" class="form-grid panel">
            @csrf
            <x-tally::form.field name="period_label" label="Period label" required><x-tally::form.input name="period_label" placeholder="2026-04" required /></x-tally::form.field>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Create monthly checklist</button></div>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Period</th><th>Kind</th><th>Tasks</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($checklists as $row)
                        <tr>
                            <td><a href="{{ tally_route('books.tally.close-checklists.show', $row) }}">{{ $row->period_label }}</a></td>
                            <td>{{ ucfirst($row->kind) }}</td>
                            <td>{{ $row->tasks_count }}</td>
                            <td>{{ ucfirst($row->status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No checklists.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $checklists->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
