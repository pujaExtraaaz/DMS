<x-tally::layouts.app title="Export">
    <x-tally::shell.page title="Export" section="Utilities" description="Choose the type of data and the file format for the current company.">
        @if (! $company)
            <x-tally::ui.empty-state title="No company" message="Select or create a company before exporting.">
                <a class="btn" href="{{ tally_route('books.tally.companies.index') }}">View companies</a>
            </x-ui.empty-state>
        @else
            <p>Company: <strong>{{ $company->name }}</strong>
                @if ($year)
                    · Financial year: <strong>{{ $year->name }}</strong>
                @else
                    · No financial year is selected, so voucher and report exports stay unavailable.
                @endif
            </p>
            <form class="tally-config" method="GET" action="{{ url('/utilities/export/ledgers') }}" data-export-form>
                <label class="tally-config-row">
                    <span>Type of Export</span>
                    <select class="input" name="dataset" data-export-dataset>
                        @foreach ($options as $option)
                            <option value="{{ $option['key'] }}" @disabled($option['needs_year'] && ! $year)>{{ $option['label'] }} — {{ $option['description'] }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="tally-config-row">
                    <span>Format</span>
                    <select class="input" name="format">
                        <option value="xlsx">Excel (Spreadsheet)</option>
                        <option value="csv">CSV</option>
                    </select>
                </label>
                <button class="btn btn-primary" type="submit">Export</button>
            </form>
            <script>
                document.querySelector('[data-export-form]')?.addEventListener('submit', (event) => {
                    const form = event.currentTarget;
                    const dataset = form.querySelector('[data-export-dataset]').value;
                    form.action = @json(url('/utilities/export')) + '/' + encodeURIComponent(dataset);
                });
            </script>
        @endif
    </x-shell.page>
</x-layouts.app>
