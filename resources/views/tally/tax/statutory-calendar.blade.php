<x-tally::layouts.app title="Statutory calendar">
    <x-tally::shell.page title="Statutory calendar" section="Tax" :description="$company->name">
        <x-slot:actions>
            <form method="POST" action="{{ tally_route('books.tally.tax.statutory-calendar.seed') }}" class="filters">
                @csrf
                <x-tally::form.field name="period" label="Seed period">
                    <x-tally::form.input name="period" value="{{ now()->format('Y-m') }}" required />
                </x-tally::form.field>
                <button class="btn" type="submit">Seed month</button>
            </form>
        </x-slot:actions>
        <form method="POST" action="{{ tally_route('books.tally.tax.statutory-calendar.store') }}" class="form-grid panel">
            @csrf
            <x-tally::form.field name="obligation" label="Obligation" required><x-tally::form.input name="obligation" value="{{ old('obligation') }}" required /></x-tally::form.field>
            <x-tally::form.field name="category" label="Category" required>
                <select class="input" name="category" required>
                    @foreach (['gst', 'tds', 'income_tax', 'other'] as $category)
                        <option value="{{ $category }}" @selected(old('category', 'gst') === $category)>{{ str_replace('_', ' ', ucfirst($category)) }}</option>
                    @endforeach
                </select>
            </x-tally::form.field>
            <x-tally::form.field name="due_date" label="Due date" required><x-tally::form.input type="date" name="due_date" value="{{ old('due_date') }}" required /></x-tally::form.field>
            <x-tally::form.field name="period_label" label="Period label"><x-tally::form.input name="period_label" value="{{ old('period_label') }}" /></x-tally::form.field>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Add due date</button></div>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Due</th><th>Obligation</th><th>Category</th><th>Period</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($dues as $due)
                        <tr>
                            <td>{{ $due->due_date?->format('d M Y') }}</td>
                            <td>{{ $due->obligation }}</td>
                            <td>{{ $due->category }}</td>
                            <td>{{ $due->period_label }}</td>
                            <td>{{ $due->status }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No statutory due dates. Seed a month to start.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $dues->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
