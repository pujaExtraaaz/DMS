<x-tally::layouts.app title="Import Data">
    <x-tally::shell.page title="Import Data" section="Utilities" description="Choose the type of data, the file, and the format. A file with an invalid row is rejected and nothing is saved.">
        <form class="tally-config" method="GET" action="{{ tally_route('books.tally.utilities.import') }}">
            <label class="tally-config-row">
                <span>Type of Import</span>
                <select id="dataset" name="dataset" class="input" onchange="this.form.submit()">
                    @foreach ($definitions as $item)
                        <option value="{{ $item->key() }}" @selected($dataset === $item->key())>{{ $item->label() }}</option>
                    @endforeach
                </select>
            </label>
            <noscript><button class="btn" type="submit">Show</button></noscript>
        </form>

        <p>{{ $definition->description() }}</p>
        @if ($definition->requiresCompany())
            <p>Current company: <strong>{{ $company->name ?? 'None selected' }}</strong></p>
        @else
            <p>Companies are created for the whole application. Other imports use the company selected in the workspace.</p>
        @endif
        <p>Format: Excel (Spreadsheet) or CSV. Behaviour: reject the file when any row is invalid.</p>
        <p>
            <a href="{{ tally_route('books.tally.utilities.import.template', ['dataset' => $definition->key(), 'format' => 'csv']) }}">CSV template</a>
            <a href="{{ tally_route('books.tally.utilities.import.template', ['dataset' => $definition->key(), 'format' => 'xlsx']) }}">Excel template</a>
        </p>

        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Required</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($definition->columns() as $column)
                        <tr>
                            <td>{{ $column['key'] }}</td>
                            <td>{{ $column['required'] ? 'Yes' : 'No' }}</td>
                            <td>{{ $column['hint'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <form class="panel tally-config" method="POST" action="{{ tally_route('books.tally.utilities.import.store', $definition->key()) }}" enctype="multipart/form-data">
            @csrf
            <label class="tally-config-row">
                <span>File Name</span>
                <input id="file" class="input" type="file" name="file" accept=".csv,.txt,.xlsx,.xls" required>
            </label>
            <button class="btn btn-primary" type="submit">Import</button>
        </form>

        <form class="panel tally-config" method="POST" action="{{ tally_route('books.tally.utilities.import.tally') }}" enctype="multipart/form-data">
            @csrf
            <label class="tally-config-row">
                <span>TallyPrime data</span>
                <input id="tally_file" class="input" type="file" name="tally_file" accept=".xml,.txt" required>
            </label>
            <p class="form-note">XML exported from TallyPrime. Ledgers are created under the matching group. Payment, Receipt, Contra, and Journal vouchers are posted. Sales and purchase vouchers are listed and left for the invoice screens.</p>
            <button class="btn" type="submit">Import Tally file</button>
        </form>

        @if ($importErrors !== [])
            <p class="flash is-error" role="alert">Nothing was imported. Fix every row and upload the file again.</p>
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>Row</th>
                            <th>Error</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (array_slice($importErrors, 0, 200) as $error)
                            <tr>
                                <td>{{ $error['row'] === 0 ? 'File' : $error['row'] }}</td>
                                <td>{{ $error['message'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if (count($importErrors) > 200)
                <p>Showing the first 200 errors of {{ count($importErrors) }}.</p>
            @endif
        @endif
    </x-shell.page>
</x-layouts.app>
