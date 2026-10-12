<x-tally::layouts.app title="Payment terms">
    <x-tally::shell.page title="Payment terms" section="Masters" :description="$company->name">
        <form method="POST" action="{{ tally_route('books.tally.payment-terms.store') }}" class="form-grid panel">
            @csrf
            <x-tally::form.field name="name" label="Name" required><x-tally::form.input name="name" required /></x-tally::form.field>
            <x-tally::form.field name="days" label="Days"><x-tally::form.input type="number" name="days" min="0" value="30" /></x-tally::form.field>
            <label class="check"><input type="checkbox" name="due_on_receipt" value="1"> Due on receipt</label>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Add</button></div>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Name</th><th>Days</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($terms as $term)
                        <tr>
                            <td colspan="4">
                                <form method="POST" action="{{ tally_route('books.tally.payment-terms.update', $term) }}" class="filters" style="border:0;padding:0.4rem 0">
                                    @csrf @method('PUT')
                                    <input class="input" name="name" value="{{ $term->name }}" required>
                                    <input class="input" name="days" type="number" value="{{ $term->days }}">
                                    <label class="check"><input type="checkbox" name="due_on_receipt" value="1" @checked($term->due_on_receipt)> Due on receipt</label>
                                    <label class="check"><input type="checkbox" name="is_active" value="1" @checked($term->is_active)> Active</label>
                                    <button class="btn" type="submit">Save</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No payment terms yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $terms->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
