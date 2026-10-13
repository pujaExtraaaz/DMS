<x-tally::layouts.app title="Salary structures">
    <x-tally::shell.page title="Salary structures" section="Payroll" :description="$company->name">
        <form method="POST" action="{{ tally_route('books.tally.salary-structures.store') }}" class="form-grid panel">
            @csrf
            <x-tally::form.field name="name" label="Name" required><x-tally::form.input name="name" required /></x-tally::form.field>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Add</button></div>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Name</th><th>Lines</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($structures as $row)
                        <tr>
                            <td><a href="{{ tally_route('books.tally.salary-structures.show', $row) }}">{{ $row->name }}</a></td>
                            <td>{{ $row->lines_count }}</td>
                            <td>{{ $row->is_active ? 'Active' : 'Inactive' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3">No salary structures.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $structures->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
