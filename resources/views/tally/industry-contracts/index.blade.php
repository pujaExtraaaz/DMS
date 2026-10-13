<x-tally::layouts.app title="Industry contracts">
    <x-tally::shell.page title="Industry contracts" section="Industry" :description="$company->name">
        <form method="POST" action="{{ tally_route('books.tally.industry-contracts.store') }}" class="form-grid panel">
            @csrf
            <x-tally::form.field name="industry" label="Industry" required>
                <select class="input" name="industry" required>
                    @foreach (['construction','subscription','retail','education','nonprofit','franchise'] as $ind)
                        <option value="{{ $ind }}">{{ ucfirst($ind) }}</option>
                    @endforeach
                </select>
            </x-tally::form.field>
            <x-tally::form.field name="contract_number" label="Contract #" required><x-tally::form.input name="contract_number" required /></x-tally::form.field>
            <x-tally::form.field name="name" label="Name" required><x-tally::form.input name="name" required /></x-tally::form.field>
            <x-tally::form.field name="contract_value" label="Value"><x-tally::form.input name="contract_value" value="0" /></x-tally::form.field>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Add</button></div>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Number</th><th>Name</th><th>Industry</th><th class="money">Value</th></tr></thead>
                <tbody>
                    @forelse ($contracts as $contract)
                        <tr>
                            <td><a href="{{ tally_route('books.tally.industry-contracts.show', $contract) }}">{{ $contract->contract_number }}</a></td>
                            <td>{{ $contract->name }}</td>
                            <td>{{ $contract->industry }}</td>
                            <td class="money">{{ $contract->contract_value }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No contracts.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $contracts->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
