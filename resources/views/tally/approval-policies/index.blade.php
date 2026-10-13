<x-tally::layouts.app title="Approval policies">
    <x-tally::shell.page title="Approval policies" section="Controls" :description="$company->name">
        <form method="POST" action="{{ tally_route('books.tally.approval-policies.store') }}" class="form-grid panel">
            @csrf
            <x-tally::form.field name="name" label="Name" required><x-tally::form.input name="name" required /></x-tally::form.field>
            <x-tally::form.field name="document_type" label="Document" required>
                <select class="input" name="document_type" required>
                    @foreach (['voucher','payment_batch','purchase_order','expense_claim'] as $type)
                        <option value="{{ $type }}">{{ str_replace('_',' ', ucfirst($type)) }}</option>
                    @endforeach
                </select>
            </x-tally::form.field>
            <x-tally::form.field name="min_amount" label="Min amount"><x-tally::form.input name="min_amount" value="0" /></x-tally::form.field>
            <x-tally::form.field name="max_amount" label="Max amount"><x-tally::form.input name="max_amount" /></x-tally::form.field>
            <x-tally::form.field name="levels" label="Levels"><x-tally::form.input type="number" name="levels" value="1" min="1" /></x-tally::form.field>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Add</button></div>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Name</th><th>Document</th><th class="money">Min</th><th class="money">Max</th><th>Levels</th></tr></thead>
                <tbody>
                    @forelse ($policies as $policy)
                        <tr>
                            <td>{{ $policy->name }}</td>
                            <td>{{ $policy->document_type }}</td>
                            <td class="money">{{ $policy->min_amount }}</td>
                            <td class="money">{{ $policy->max_amount }}</td>
                            <td>{{ $policy->levels }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No policies.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $policies->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
