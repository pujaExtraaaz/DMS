<x-tally::layouts.app title="Close checklist">
    <x-tally::shell.page title="Close · {{ $checklist->period_label }}" section="Close" :description="$company->name">
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>#</th><th>Task</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach ($checklist->tasks as $task)
                        <tr>
                            <td>{{ $task->sort_order }}</td>
                            <td>{{ $task->title }}</td>
                            <td>{{ ucfirst($task->status) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-tally::shell.page>
</x-tally::layouts.app>
