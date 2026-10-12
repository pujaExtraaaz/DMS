<x-tally::layouts.app title="Projects">
    <x-tally::shell.page title="Projects" section="Masters" :description="$company->name">
        <form method="POST" action="{{ tally_route('books.tally.projects.store') }}" class="form-grid panel">
            @csrf
            <x-tally::form.field name="name" label="Name" required><x-tally::form.input name="name" required /></x-tally::form.field>
            <x-tally::form.field name="code" label="Code"><x-tally::form.input name="code" /></x-tally::form.field>
            <x-tally::form.field name="contract_value" label="Contract value"><x-tally::form.input name="contract_value" value="0" /></x-tally::form.field>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Add project</button></div>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Name</th><th>Code</th><th>Customer</th><th class="money">Value</th><th></th></tr></thead>
                <tbody>
                    @forelse ($projects as $project)
                        <tr>
                            <td><a href="{{ tally_route('books.tally.projects.show', $project) }}">{{ $project->name }}</a></td>
                            <td>{{ $project->code }}</td>
                            <td>{{ $project->customer?->name }}</td>
                            <td class="money">{{ $project->contract_value }}</td>
                            <td><a class="btn" href="{{ tally_route('books.tally.projects.show', $project) }}">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No projects yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $projects->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
