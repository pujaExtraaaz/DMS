<x-tally::layouts.app title="Payroll provisions">
    <x-tally::shell.page title="Payroll provisions" section="Payroll" :description="$company->name">
        <form method="POST" action="{{ tally_route('books.tally.payroll-provisions.store') }}" class="form-grid panel">
            @csrf
            <x-tally::form.field name="kind" label="Kind" required>
                <select class="input" name="kind" required>
                    @foreach (['gratuity','leave','bonus','encashment'] as $kind)
                        <option value="{{ $kind }}">{{ ucfirst($kind) }}</option>
                    @endforeach
                </select>
            </x-tally::form.field>
            <x-tally::form.field name="period" label="Period"><x-tally::form.input name="period" placeholder="2026-04" /></x-tally::form.field>
            <x-tally::form.field name="amount" label="Amount" required><x-tally::form.input name="amount" required /></x-tally::form.field>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Add</button></div>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Kind</th><th>Period</th><th class="money">Amount</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($provisions as $row)
                        <tr>
                            <td>{{ ucfirst($row->kind) }}</td>
                            <td>{{ $row->period }}</td>
                            <td class="money">{{ $row->amount }}</td>
                            <td>{{ ucfirst($row->status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No provisions.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $provisions->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
